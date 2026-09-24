<?php

namespace App\Services\AIChat;

use App\Contracts\AIChatProvider;
use App\Models\User;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\Recommendation\RecommendationInput;
use App\Services\Recommendation\RecommendationService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AIChatService
{
    public function __construct(
        private readonly ConversationStore $conversations,
        private readonly AIChatProvider $provider,
        private readonly AIChatProviderOutputNormalizer $normalizer,
        private readonly AIChatPrivacyFilter $privacy,
        private readonly FallbackIntentClassifier $fallback,
        private readonly RestaurantContextBuilder $restaurant,
        private readonly MenuContextBuilder $menu,
        private readonly CustomerContextBuilder $customer,
        private readonly SupportKnowledgeCatalog $knowledge,
        private readonly AIChatActionFactory $actions,
        private readonly CustomerCartService $cart,
        private readonly RecommendationService $recommendations,
        private readonly TypedSystemSettingResolver $settings,
    ) {}

    /** @return array<string,mixed> */
    public function respond(Request $request, string $message): array
    {
        $state = $this->conversations->read($request->session());
        $providerOutput = null;
        $gemini = $this->settings->gemini();
        if ($gemini['enabled'] && is_string($gemini['api_key']) && $gemini['api_key'] !== '') {
            $providerResult = $this->provider->classify(
                new AIChatProviderInput(
                    app()->getLocale(),
                    $this->providerMessages($state, $message),
                    [
                        'restaurant' => $this->restaurant->build(),
                        'menu' => $this->menu->catalog(),
                    ],
                ),
            );
            $providerOutput = $this->normalizer->normalize($providerResult);
            if (! $providerOutput->valid) {
                Log::warning('AI chat provider fallback.', [
                    'provider' => 'gemini',
                    'category' => $providerOutput->failureCategory,
                    'http_status' => $providerResult->httpStatus,
                    'duration_ms' => $providerResult->durationMs,
                ]);
            }
        }

        $fallbackContext = $this->fallback->extractRecommendationContext($message);
        $fallbackIntent = $this->fallback->classify($message);
        $intent = $providerOutput?->valid === true ? $providerOutput->intent : $fallbackIntent;
        $intent ??= AIChatIntent::Unsupported;
        if (preg_match('/\b(?:zalo|facebook|messenger)\b/iu', $message) === 1) {
            $intent = AIChatIntent::ContactInfo;
        }
        if ($this->isMedicalQuestion($message)) {
            $intent = AIChatIntent::Unsupported;
        } elseif (
            $fallbackIntent === AIChatIntent::ContactInfo
            && preg_match('/google map|bản đồ|chỉ đường/iu', $message) === 1
        ) {
            $intent = AIChatIntent::ContactInfo;
        } elseif (
            $fallbackIntent === AIChatIntent::MenuSearch
            && preg_match('/gợi ý.*(?:các|những) món|gợi ý.*món về|mực nướng/iu', $message) === 1
        ) {
            $intent = AIChatIntent::MenuSearch;
        }
        if (
            $intent === AIChatIntent::Unsupported
            && ! $this->isMedicalQuestion($message)
            && ($state['last_intent'] ?? null) === AIChatIntent::Recommendation->value
            && array_intersect(array_keys($fallbackContext), ['party_size', 'budget', 'budget_skipped', 'preferences']) !== []
        ) {
            $intent = AIChatIntent::Recommendation;
        }
        $extracted = array_replace(
            (array) ($state['context'] ?? []),
            $fallbackContext,
            $providerOutput?->valid === true ? $providerOutput->extractedContext : [],
        );
        $response = $this->dispatch($request, $message, $intent, $extracted, $providerOutput);
        $stored = $this->conversations->append(
            $request->session(),
            $message,
            $response['message'],
            $intent,
            $extracted,
            collect($response)->only([
                'products',
                'recommendation',
                'actions',
                'links',
                'handoff',
                'requires_login',
            ])->all(),
        );
        $response['conversation'] = $this->conversationMetadata($stored);

        return $response;
    }

    /** @return array<string,mixed> */
    public function state(Request $request): array
    {
        $state = $this->conversations->read($request->session());
        $actionSet = $this->actions->build(['view_menu', 'make_reservation', 'open_contact']);
        $message =
            ($state['messages'] ?? []) === []
                ? __('ai_chat.empty_conversation').' '.__('ai_chat.greeting')
                : __('ai_chat.greeting');

        return [
            'message' => $message,
            'intent' => AIChatIntent::Greeting->value,
            'products' => [],
            'recommendation' => null,
            'actions' => $actionSet['actions'],
            'links' => $actionSet['links'],
            'requires_login' => false,
            'handoff' => null,
            'conversation' => $this->conversationMetadata($state, true),
        ];
    }

    /** @return array<string,mixed> */
    public function reset(Request $request): array
    {
        $this->conversations->reset($request->session());
        $response = $this->state($request);
        $response['message'] = __('ai_chat.reset_success').' '.__('ai_chat.greeting');

        return $response;
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    private function dispatch(
        Request $request,
        string $message,
        AIChatIntent $intent,
        array $context,
        ?NormalizedAIChatProviderResult $providerOutput,
    ): array {
        $contact = $this->restaurant->build();
        $products = [];
        $recommendation = null;
        $requiresLogin = false;
        $handoff = null;
        $actionTypes = $this->defaultActions($intent);
        $reply = match ($intent) {
            AIChatIntent::Greeting => $providerOutput?->valid === true
                ? $providerOutput->reply
                : __('ai_chat.greeting'),
            AIChatIntent::RestaurantInfo,
            AIChatIntent::ContactInfo,
            AIChatIntent::OpeningHours => $this->restaurantReply($intent, $message, $contact),
            AIChatIntent::MenuSearch, AIChatIntent::ProductQuestion => $this->menuReply($message, $products),
            AIChatIntent::Recommendation => $this->recommendationReply(
                $request,
                $context,
                $products,
                $recommendation,
                $actionTypes,
            ),
            AIChatIntent::CartQuestion => $this->cartReply($request),
            AIChatIntent::ReservationLookup => $this->customerReply(
                $request->user(),
                true,
                $message,
                $requiresLogin,
                $actionTypes,
            ),
            AIChatIntent::OrderLookup => $this->customerReply(
                $request->user(),
                false,
                $message,
                $requiresLogin,
                $actionTypes,
            ),
            AIChatIntent::OrderingGuide,
            AIChatIntent::ReservationGuide,
            AIChatIntent::PickupGuide,
            AIChatIntent::DeliveryGuide,
            AIChatIntent::PaymentGuide,
            AIChatIntent::VoucherGuide,
            AIChatIntent::AccountHelp => $this->knowledge->answer(
                $intent,
                $this->restaurant->deliveryFee(),
                $this->restaurant->vietQrAvailable(),
            ),
            AIChatIntent::Unsupported => match (true) {
                $this->isMedicalQuestion($message) => __('ai_chat.medical_boundary'),
                $providerOutput !== null && ! $providerOutput->valid => __('ai_chat.provider_unavailable'),
                default => __('ai_chat.handoff.message'),
            },
        };
        if (
            in_array($intent, [AIChatIntent::RestaurantInfo, AIChatIntent::ContactInfo], true) &&
            preg_match('/(?:đặt|gọi)\s*món|order\s*food|点餐/iu', $message) === 1
        ) {
            $reply .= "\n".$this->knowledge->answer(
                AIChatIntent::OrderingGuide,
                $this->restaurant->deliveryFee(),
                $this->restaurant->vietQrAvailable(),
            );
            $actionTypes[] = 'view_menu';
        }
        if ($intent === AIChatIntent::Unsupported && ! $this->isMedicalQuestion($message)) {
            $handoff = $this->handoff($contact, $this->handoffReason($message, $providerOutput));
        } elseif ($intent === AIChatIntent::Unsupported) {
            $actionTypes = [];
        }
        $approvedSuggested = $this->isMedicalQuestion($message) ? [] : array_intersect(
            $providerOutput?->suggestedActions ?? [],
            $this->allowedSuggestedActions($intent),
        );
        $actionSet = $this->actions->build(
            array_values(array_unique([...$actionTypes, ...$approvedSuggested])),
            $products,
            $recommendation,
            $contact,
        );
        if ($intent === AIChatIntent::ContactInfo) {
            foreach (['facebook_url' => 'facebook', 'zalo_url' => 'zalo'] as $key => $type) {
                $url = $contact[$key] ?? null;
                if (is_string($url) && str_contains($reply, $url)) {
                    $actionSet['links'][] = ['type' => $type, 'label' => ucfirst($type), 'url' => $url];
                }
            }
            if (is_string($contact['map_url'] ?? null) && str_contains($reply, $contact['map_url'])) {
                $actionSet['links'][] = ['type' => 'map', 'label' => __('ai_chat.contact.map_url'), 'url' => $contact['map_url']];
            }
        }

        return [
            'message' => mb_substr((string) $reply, 0, (int) config('ai_chat.limits.reply_characters', 1500)),
            'intent' => $intent->value,
            'products' => $products,
            'recommendation' => $recommendation,
            'actions' => $actionSet['actions'],
            'links' => $actionSet['links'],
            'requires_login' => $requiresLogin,
            'handoff' => $handoff,
            'conversation' => [],
        ];
    }

    /** @param array<string,mixed> $contact */
    private function restaurantReply(AIChatIntent $intent, string $message, array $contact): string
    {
        $question = mb_strtolower($message);
        if ($intent === AIChatIntent::ContactInfo && preg_match('/google map|bản đồ|chỉ đường/iu', $question) === 1) {
            if (is_string($contact['map_url'] ?? null)) {
                return __('ai_chat.map_available', ['url' => $contact['map_url']]);
            }

            return is_string($contact['address'] ?? null)
                ? __('ai_chat.map_unavailable', ['address' => $contact['address']])
                : __('ai_chat.restaurant_missing');
        }
        $keys = match ($intent) {
            AIChatIntent::OpeningHours => ['opening_hours'],
            AIChatIntent::ContactInfo => match (true) {
                str_contains($question, 'zalo') => ['zalo_url'],
                str_contains($question, 'facebook') || str_contains($question, 'messenger') => ['facebook_url'],
                default => ['address', 'phone', 'email', 'facebook_url', 'zalo_url'],
            },
            default => ['address', 'phone', 'email', 'opening_hours'],
        };
        $lines = [];
        foreach ($keys as $key) {
            if (is_string($contact[$key] ?? null)) {
                $lines[] = __('ai_chat.contact.'.$key).': '.$contact[$key];
            }
        }

        return $lines === [] ? __('ai_chat.restaurant_missing') : __('ai_chat.contact_intro')."\n".implode("\n", $lines);
    }

    /** @param list<array<string,mixed>> $products */
    private function menuReply(string $message, array &$products): string
    {
        $products = $this->menu->search($message);

        if ($products === []) {
            return __('ai_chat.product_not_found');
        }
        if (preg_match('/xịn|chất lượng|tươi|nguồn gốc|organic|cao cấp/iu', $message) === 1) {
            $product = $products[0];
            $description = trim((string) ($product['short_description'] ?: $product['description'] ?: ''));

            return $description !== ''
                ? __('ai_chat.product_quality_described', ['name' => $product['name'], 'description' => $description])
                : __('ai_chat.product_quality_unknown', ['name' => $product['name']]);
        }

        return trans_choice('ai_chat.products_found', count($products), ['count' => count($products)]);
    }

    private function isMedicalQuestion(string $message): bool
    {
        return preg_match('/ung thư|bị ung|dị ứng|ngộ độc|bệnh (?:gì|nào)|có thai.*ăn/iu', $message) === 1;
    }

    /**
     * @param  array<string,mixed>  $context
     * @param  list<array<string,mixed>>  $products
     * @param  array<string,mixed>|null  $recommendation
     * @param  list<string>  $actions
     */
    private function recommendationReply(
        Request $request,
        array $context,
        array &$products,
        ?array &$recommendation,
        array &$actions,
    ): string {
        if (! isset($context['party_size'])) {
            $actions = [];

            return __('ai_chat.recommendation_clarification');
        }
        if (! isset($context['budget']) && ($context['budget_skipped'] ?? false) !== true) {
            $actions = [];

            return __('ai_chat.recommendation_budget_clarification');
        }
        try {
            $cartRows = $this->cart->rows($request);
        } catch (ValidationException) {
            $cartRows = [];
        }
        $cartItems = collect($cartRows)
            ->filter(fn (array $row): bool => ($row['available'] ?? false) === true)
            ->map(
                fn (array $row): array => [
                    'product_id' => (int) $row['product_id'],
                    'quantity' => (int) $row['quantity'],
                ],
            )
            ->values()
            ->all();
        $recommendation = $this->recommendations
            ->recommend(
                new RecommendationInput(
                    (int) $context['party_size'],
                    isset($context['budget']) ? (int) $context['budget'] : null,
                    array_values((array) ($context['preferences'] ?? [])),
                    null,
                ),
                $request->user() instanceof User ? $request->user() : null,
                $cartItems,
            )
            ->toArray();
        if (($recommendation['items'] ?? []) === []) {
            $recommendation = null;
            $actions = [];

            return __('ai_chat.recommendation_unavailable');
        }
        $products = $this->menu->byIds(collect($recommendation['items'])->pluck('product_id')->all());
        $actions = ['view_product', 'add_product_to_cart', 'add_recommendation_set_to_cart'];

        return __('ai_chat.recommendation_ready');
    }

    private function cartReply(Request $request): string
    {
        try {
            $rows = $this->cart->rows($request);
        } catch (ValidationException) {
            return __('ai_chat.cart_invalid');
        }
        if ($rows === []) {
            return __('ai_chat.cart_empty');
        }
        $quantity = collect($rows)->sum('quantity');
        $subtotal = collect($rows)->where('available', true)->sum('line_total');

        return __('ai_chat.cart_summary', [
            'lines' => count($rows),
            'quantity' => $quantity,
            'subtotal' => number_format($subtotal),
        ]);
    }

    /** @param list<string> $actions */
    private function customerReply(
        mixed $user,
        bool $reservations,
        string $message,
        bool &$requiresLogin,
        array &$actions,
    ): string {
        $code = $this->extractCode($message);
        $context = $reservations
            ? $this->customer->reservations($user instanceof User ? $user : null, $code)
            : $this->customer->orders($user instanceof User ? $user : null, $code);
        if (! $context['authenticated']) {
            $requiresLogin = true;
            $actions = ['login'];

            return __('ai_chat.login_required');
        }
        if ($context['records'] === []) {
            return $reservations ? __('ai_chat.reservations_empty') : __('ai_chat.orders_empty');
        }

        return ($reservations ? __('ai_chat.reservations_intro') : __('ai_chat.orders_intro')).
            "\n".
            collect($context['records'])
                ->map(
                    fn (array $record): string => implode(
                        ' · ',
                        array_filter([
                            $record['code'] ?? null,
                            $record['placed_at'] ?? ($record['date'] ?? null),
                            $record['status'] ?? null,
                            isset($record['total']) ? number_format((int) $record['total']).' VND' : null,
                        ]),
                    ),
                )
                ->implode("\n");
    }

    /** @param array<string,mixed> $state @return list<array{role:string,content:string}> */
    private function providerMessages(array $state, string $message): array
    {
        $messages = collect($state['messages'] ?? [])
            ->take(-10)
            ->map(
                fn (array $item): array => [
                    'role' => ($item['role'] ?? null) === 'assistant' ? 'assistant' : 'user',
                    'content' => $this->privacy->redact((string) ($item['content'] ?? '')),
                ],
            )
            ->values()
            ->all();
        $messages[] = ['role' => 'user', 'content' => $this->privacy->redact($message)];

        return $messages;
    }

    /** @return list<string> */
    private function defaultActions(AIChatIntent $intent): array
    {
        return match ($intent) {
            AIChatIntent::Greeting => ['view_menu', 'make_reservation', 'open_contact'],
            AIChatIntent::RestaurantInfo => ['open_map', 'call_hotline'],
            AIChatIntent::OpeningHours => [],
            AIChatIntent::ContactInfo => ['call_hotline'],
            AIChatIntent::MenuSearch, AIChatIntent::ProductQuestion => [
                'view_product',
                'add_product_to_cart',
            ],
            AIChatIntent::OrderingGuide, AIChatIntent::CartQuestion => ['view_cart', 'view_menu'],
            AIChatIntent::ReservationGuide => ['make_reservation'],
            AIChatIntent::PickupGuide,
            AIChatIntent::DeliveryGuide,
            AIChatIntent::PaymentGuide,
            AIChatIntent::VoucherGuide => ['view_cart'],
            AIChatIntent::AccountHelp => ['login'],
            AIChatIntent::ReservationLookup => ['view_reservations'],
            AIChatIntent::OrderLookup => ['view_orders'],
            AIChatIntent::Recommendation => [],
            AIChatIntent::Unsupported => ['open_contact', 'call_hotline'],
        };
    }

    /** @return list<string> */
    private function allowedSuggestedActions(AIChatIntent $intent): array
    {
        return $this->defaultActions($intent);
    }

    /** @param array<string,mixed> $contact @return array<string,mixed> */
    private function handoff(array $contact, string $reason): array
    {
        $links = $this->actions->build(['open_contact', 'call_hotline'], contact: $contact)['links'];

        return [
            'recommended' => true,
            'reason' => $reason,
            'phone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
            'contact_url' => route('customer.contact', absolute: false),
            'phone_url' => is_string($contact['phone'] ?? null)
                ? 'tel:'.preg_replace('/[^0-9+]/', '', $contact['phone'])
                : null,
            'email_url' => is_string($contact['email'] ?? null) ? 'mailto:'.$contact['email'] : null,
            'links' => $links,
        ];
    }

    private function handoffReason(string $message, ?NormalizedAIChatProviderResult $provider): string
    {
        $text = mb_strtolower($message);
        if (str_contains($text, 'nhân viên') || str_contains($text, 'staff')) {
            return 'requested_staff';
        }
        if ($provider !== null && ! $provider->valid) {
            return 'provider_unavailable';
        }

        return 'unsupported';
    }

    private function extractCode(string $message): ?string
    {
        return preg_match('/\b[A-Z]{2,}(?:-[A-Z0-9]+)+\b/', mb_strtoupper($message), $matches) === 1
            ? $matches[0]
            : null;
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private function conversationMetadata(array $state, bool $includeMessages = false): array
    {
        $metadata = [
            'message_count' => count($state['messages'] ?? []),
            'can_reset' => ($state['messages'] ?? []) !== [],
            'updated_at' => $state['updated_at'] ?? null,
            'suggested_prompts' => __('ai_chat.suggested_prompts'),
        ];
        if ($includeMessages) {
            $metadata['messages'] = $state['messages'] ?? [];
        }

        return $metadata;
    }
}

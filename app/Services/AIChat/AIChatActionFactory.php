<?php

namespace App\Services\AIChat;

class AIChatActionFactory
{
    /**
     * @param  list<string>  $types
     * @param  list<array<string,mixed>>  $products
     * @param  array<string,mixed>|null  $recommendation
     * @param  array<string,mixed>  $contact
     * @return array{actions:list<array<string,mixed>>,links:list<array<string,mixed>>}
     */
    public function build(array $types, array $products = [], ?array $recommendation = null, array $contact = []): array
    {
        $actions = [];
        $links = [];
        foreach (array_values(array_unique($types)) as $type) {
            if (! in_array($type, AIChatCatalog::ACTIONS, true)) {
                continue;
            }
            if (in_array($type, ['view_product', 'add_product_to_cart'], true)) {
                foreach ($products as $product) {
                    $productId = (int) ($product['id'] ?? 0);
                    if ($productId < 1) {
                        continue;
                    }
                    $payload = ['product_id' => $productId];
                    $url = $product['detail_url'] ?? null;
                    if ($type === 'add_product_to_cart') {
                        $payload['quantity'] = 1;
                        $url = route('customer.cart.items.store', absolute: false);
                    }
                    $actions[] = ['type' => $type, 'label' => __('ai_chat.actions.'.$type), 'payload' => $payload];
                    if (is_string($url) && $url !== '') {
                        $links[] = [
                            'type' => $type,
                            'label' => __('ai_chat.actions.'.$type),
                            'url' => $url,
                            'product_id' => $productId,
                        ];
                    }
                }

                continue;
            }
            $payload = [];
            $url = $this->routeFor($type);
            if ($type === 'add_recommendation_set_to_cart' && $recommendation !== null) {
                $payload = [
                    'items' => collect($recommendation['items'] ?? [])
                        ->map(
                            fn (array $item): array => [
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                            ],
                        )
                        ->values()
                        ->all(),
                ];
                $url = route('customer.cart.items.batch-store', absolute: false);
            } elseif ($type === 'open_map' && is_string($contact['map_url'] ?? null)) {
                $url = $contact['map_url'];
            } elseif ($type === 'call_hotline' && is_string($contact['phone'] ?? null)) {
                $payload = ['phone' => $contact['phone']];
                $url = 'tel:'.preg_replace('/[^0-9+]/', '', $contact['phone']);
            }
            if ($type === 'add_recommendation_set_to_cart' && ($payload['items'] ?? []) === []) {
                continue;
            }
            if ($type === 'open_map' && $url === null) {
                continue;
            }
            if ($type === 'call_hotline' && $payload === []) {
                continue;
            }
            $actions[] = [
                'type' => $type,
                'label' => __('ai_chat.actions.'.$type),
                'payload' => $payload,
            ];
            if ($url !== null) {
                $links[] = ['type' => $type, 'label' => __('ai_chat.actions.'.$type), 'url' => $url];
            }
            if ($type === 'login') {
                $links[] = [
                    'type' => 'registration',
                    'label' => __('ai_chat.links.registration'),
                    'url' => route('customer.registration.create', absolute: false),
                ];
            }
        }

        return ['actions' => $actions, 'links' => $links];
    }

    private function routeFor(string $type): ?string
    {
        return match ($type) {
            'view_menu' => route('customer.menu.index', absolute: false),
            'view_cart' => route('customer.cart.index', absolute: false),
            'make_reservation' => route('customer.reservations.create', absolute: false),
            'view_reservations' => route('customer.reservations.index', absolute: false),
            'view_orders' => route('customer.orders.history', absolute: false),
            'login' => route('login', absolute: false),
            'open_contact' => route('customer.contact', absolute: false),
            'new_conversation' => route('customer.ai-chat.reset', absolute: false),
            default => null,
        };
    }
}

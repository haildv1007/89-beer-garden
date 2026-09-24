<?php

namespace App\Services\AIChat;

final readonly class NormalizedAIChatProviderResult
{
    /**
     * @param  array<string, mixed>  $extractedContext
     * @param  list<int>  $productIds
     * @param  list<string>  $suggestedActions
     */
    private function __construct(
        public bool $valid,
        public ?AIChatIntent $intent,
        public ?string $reply,
        public array $extractedContext,
        public ?string $requestedTool,
        public array $productIds,
        public array $suggestedActions,
        public ?string $failureCategory,
    ) {}

    /** @param array<string,mixed> $context @param list<int> $ids @param list<string> $actions */
    public static function success(
        AIChatIntent $intent,
        string $reply,
        array $context,
        ?string $tool,
        array $ids,
        array $actions,
    ): self {
        return new self(true, $intent, $reply, $context, $tool, $ids, $actions, null);
    }

    public static function invalid(string $category): self
    {
        return new self(false, null, null, [], null, [], [], $category);
    }
}

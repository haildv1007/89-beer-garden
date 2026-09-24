<?php

namespace App\Services\AIChat;

use Illuminate\Contracts\Session\Session;

class ConversationStore
{
    public const SESSION_KEY = 'ai_chat_conversation';

    private const VERSION = 1;

    /** @return array<string,mixed> */
    public function read(Session $session): array
    {
        $state = $session->get(self::SESSION_KEY);
        if (! is_array($state) || ($state['version'] ?? null) !== self::VERSION || ! is_array($state['messages'] ?? null)) {
            return $this->empty();
        }

        return $state;
    }

    /** @param array<string,mixed> $context @param array<string,mixed> $presentation */
    public function append(
        Session $session,
        string $userMessage,
        string $assistantMessage,
        AIChatIntent $intent,
        array $context,
        array $presentation,
    ): array {
        $state = $this->read($session);
        foreach ($state['messages'] as &$storedMessage) {
            unset($storedMessage['presentation']);
        }
        unset($storedMessage);
        $timestamp = now()->toIso8601String();
        $state['messages'][] = ['role' => 'user', 'content' => $userMessage, 'timestamp' => $timestamp];
        $state['messages'][] = [
            'role' => 'assistant',
            'content' => $assistantMessage,
            'intent' => $intent->value,
            'presentation' => $presentation,
            'timestamp' => $timestamp,
        ];
        $state['context'] = $context;
        $state['last_intent'] = $intent->value;
        $state['last_recommendation'] = $presentation['recommendation'] ?? null;
        $state['updated_at'] = $timestamp;
        $state['messages'] = $this->trim($state['messages']);
        $session->put(self::SESSION_KEY, $state);

        return $state;
    }

    public function reset(Session $session): void
    {
        $session->forget(self::SESSION_KEY);
    }

    /** @return array<string,mixed> */
    public function empty(): array
    {
        return [
            'version' => self::VERSION,
            'messages' => [],
            'context' => [],
            'last_intent' => null,
            'last_recommendation' => null,
            'updated_at' => null,
        ];
    }

    /** @param list<array<string,mixed>> $messages @return list<array<string,mixed>> */
    private function trim(array $messages): array
    {
        $maxMessages = (int) config('ai_chat.limits.messages', 20);
        $maxCharacters = (int) config('ai_chat.limits.context_characters', 12000);
        $messages = array_slice($messages, -$maxMessages);
        while (
            count($messages) > 1 &&
            array_sum(array_map(fn (array $message): int => mb_strlen((string) ($message['content'] ?? '')), $messages)) > $maxCharacters
        ) {
            array_shift($messages);
        }

        return array_values($messages);
    }
}

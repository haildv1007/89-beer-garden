@php
    $supportContact = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->contactInformation();
    $supportPhoneUrl = $supportContact['phone']
        ? 'tel:' . preg_replace('/[^0-9+]/', '', $supportContact['phone'])
        : null;
    $aiChatConfig = [
        'endpoints' => [
            'state' => route('customer.ai-chat.state'),
            'messages' => route('customer.ai-chat.messages.store'),
            'reset' => route('customer.ai-chat.reset'),
            'cartItem' => route('customer.cart.items.store'),
            'cartBatch' => route('customer.cart.items.batch-store'),
            'cart' => route('customer.cart.index'),
        ],
        'labels' => __('ai_chat.widget'),
        'locale' => app()->getLocale(),
        'maxLength' => 1500,
    ];
@endphp

<div class="ai-chat" data-ai-chat-widget>
    <button class="ai-chat__launcher" type="button" data-ai-chat-launcher aria-expanded="false" aria-controls="aiChatPanel"
        aria-label="{{ __('ai_chat.widget.launcher') }}">
        <span class="ai-chat__launcher-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M20 11.5a8 8 0 0 1-8.5 8 9 9 0 0 1-3.7-.9L3 20l1.4-4.1A8 8 0 1 1 20 11.5Z" />
            </svg>
        </span>
        <span class="ai-chat__launcher-status" aria-hidden="true"></span>
    </button>

    <div class="ai-chat__backdrop" data-ai-chat-backdrop hidden></div>
    <section class="ai-chat__panel" id="aiChatPanel" data-ai-chat-panel role="dialog" aria-modal="false"
        aria-labelledby="aiChatTitle" hidden>
        <header class="ai-chat__header">
            <span class="ai-chat__avatar" aria-hidden="true">89</span>
            <div class="ai-chat__identity">
                <h2 id="aiChatTitle">
                    {{ __('ai_chat.widget.title') }}
                    <span class="ai-chat__title-status" aria-hidden="true"></span>
                </h2>
                <p>{{ __('ai_chat.widget.subtitle') }}</p>
            </div>
            <button class="ai-chat__icon-button" type="button" data-ai-chat-reset
                aria-label="{{ __('ai_chat.widget.new_conversation') }}"
                title="{{ __('ai_chat.widget.new_conversation') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.5M4 4v4.5h4.5" />
                </svg>
            </button>
            <button class="ai-chat__icon-button" type="button" data-ai-chat-close
                aria-label="{{ __('ai_chat.widget.close') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </header>

        <div class="ai-chat__tabs" role="tablist" aria-label="{{ __('ai_chat.widget.tabs_label') }}">
            <button class="ai-chat__tab is-active" id="aiChatTabChat" type="button" role="tab" aria-selected="true"
                aria-controls="aiChatChatPanel" data-ai-chat-tab="chat">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5.5h14v10H9l-4 3v-13Z" /></svg>
                {{ __('ai_chat.widget.chat_tab') }}
            </button>
            <button class="ai-chat__tab" id="aiChatTabSupport" type="button" role="tab" aria-selected="false"
                aria-controls="aiChatSupportPanel" data-ai-chat-tab="support">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5ZM20 13h-3v6h2a1 1 0 0 0 1-1v-5ZM17 19c0 1.1-.9 2-2 2h-3" /></svg>
                {{ __('ai_chat.widget.support_tab') }}
            </button>
        </div>

        <div class="ai-chat__tab-panel is-active" id="aiChatChatPanel" role="tabpanel" aria-labelledby="aiChatTabChat"
            data-ai-chat-tab-panel="chat">
            <div class="ai-chat__conversation" data-ai-chat-conversation aria-live="polite" aria-relevant="additions text">
                <div class="ai-chat__loading" data-ai-chat-loading>
                    <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
                    <span class="visually-hidden">{{ __('ai_chat.widget.loading_history') }}</span>
                </div>
            </div>

            <div class="ai-chat__notice" data-ai-chat-notice role="status" aria-live="polite" hidden></div>
            <form class="ai-chat__composer" data-ai-chat-composer>
                <label class="visually-hidden" for="aiChatMessage">{{ __('ai_chat.widget.placeholder') }}</label>
                <textarea id="aiChatMessage" name="message" rows="1" maxlength="1500"
                    placeholder="{{ __('ai_chat.widget.placeholder') }}" data-ai-chat-input></textarea>
                <span class="ai-chat__counter" data-ai-chat-counter hidden>0/1500</span>
                <button type="submit" data-ai-chat-send aria-label="{{ __('ai_chat.widget.send') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m4 4 17 8-17 8 3-8-3-8Zm3 8h14" />
                    </svg>
                </button>
            </form>
        </div>

        <div class="ai-chat__tab-panel" id="aiChatSupportPanel" role="tabpanel" aria-labelledby="aiChatTabSupport"
            data-ai-chat-tab-panel="support" hidden>
            <div class="ai-chat__support">
                <span class="ai-chat__support-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5ZM20 13h-3v6h2a1 1 0 0 0 1-1v-5ZM17 19c0 1.1-.9 2-2 2h-3" /></svg>
                </span>
                <h3>{{ __('ai_chat.widget.support_title') }}</h3>
                <p>{{ __('ai_chat.widget.support_copy') }}</p>

                <div class="ai-chat__support-list">
                    @if ($supportContact['facebook_url'])
                        <a class="ai-chat__support-link ai-chat__support-link--facebook"
                            href="{{ $supportContact['facebook_url'] }}" target="_blank" rel="noopener noreferrer">
                            <span class="ai-chat__support-icon" aria-hidden="true"><img src="{{ asset('images/social/messenger.svg') }}" alt=""></span>
                            <span><strong>Facebook Messenger</strong><small>{{ __('ai_chat.widget.facebook_copy') }}</small></span>
                            <span aria-hidden="true">↗</span>
                        </a>
                    @endif
                    @if ($supportContact['zalo_url'])
                        <a class="ai-chat__support-link ai-chat__support-link--zalo" href="{{ $supportContact['zalo_url'] }}"
                            target="_blank" rel="noopener noreferrer">
                            <span class="ai-chat__support-icon" aria-hidden="true"><img src="{{ asset('images/social/zalo.svg') }}" alt=""></span>
                            <span><strong>Zalo</strong><small>{{ __('ai_chat.widget.zalo_copy') }}</small></span>
                            <span aria-hidden="true">↗</span>
                        </a>
                    @endif
                    @if ($supportPhoneUrl)
                        <a class="ai-chat__support-link ai-chat__support-link--phone" href="{{ $supportPhoneUrl }}">
                            <span class="ai-chat__support-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M7 3h3l1.5 5-2 1.5a15 15 0 0 0 5 5l1.5-2 5 1.5v3a4 4 0 0 1-4 4C9.3 20.5 3.5 14.7 3 7a4 4 0 0 1 4-4Z" /></svg>
                            </span>
                            <span><strong>{{ $supportContact['phone'] }}</strong><small>{{ __('ai_chat.widget.phone_copy') }}</small></span>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>

                @unless ($supportContact['facebook_url'] || $supportContact['zalo_url'] || $supportPhoneUrl)
                    <p class="ai-chat__support-empty">{{ __('ai_chat.widget.support_empty') }}</p>
                @endunless

                <small class="ai-chat__support-note">{{ __('ai_chat.widget.support_note') }}</small>
            </div>
        </div>
    </section>
    <script type="application/json" data-ai-chat-config>@json($aiChatConfig)</script>
</div>

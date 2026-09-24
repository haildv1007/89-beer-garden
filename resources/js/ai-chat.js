import '../css/ai-chat.css';

const root = document.querySelector('[data-ai-chat-widget]');

if (root instanceof HTMLElement) {
    const configNode = root.querySelector('[data-ai-chat-config]');
    let config = null;
    try {
        config = JSON.parse(configNode?.textContent ?? '{}');
    } catch {
        root.remove();
    }

    if (config) {
        const labels = config.labels ?? {};
        const launcher = root.querySelector('[data-ai-chat-launcher]');
        const panel = root.querySelector('[data-ai-chat-panel]');
        const backdrop = root.querySelector('[data-ai-chat-backdrop]');
        const conversation = root.querySelector('[data-ai-chat-conversation]');
        const composer = root.querySelector('[data-ai-chat-composer]');
        const input = root.querySelector('[data-ai-chat-input]');
        const send = root.querySelector('[data-ai-chat-send]');
        const reset = root.querySelector('[data-ai-chat-reset]');
        const tabs = [...root.querySelectorAll('[data-ai-chat-tab]')];
        const tabPanels = [...root.querySelectorAll('[data-ai-chat-tab-panel]')];
        const notice = root.querySelector('[data-ai-chat-notice]');
        const counter = root.querySelector('[data-ai-chat-counter]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const allowedActions = new Set([
            'view_menu',
            'view_product',
            'view_cart',
            'add_product_to_cart',
            'add_recommendation_set_to_cart',
            'make_reservation',
            'view_reservations',
            'view_orders',
            'login',
            'open_contact',
            'open_map',
            'call_hotline',
            'new_conversation',
        ]);
        const mutationActions = new Set(['add_product_to_cart', 'add_recommendation_set_to_cart']);
        let loaded = false;
        let busy = false;
        let messageCount = 0;
        let lastFocus = null;
        let activeTab = 'chat';

        const selectTab = (name, focus = false) => {
            if (!['chat', 'support'].includes(name)) return;
            activeTab = name;
            root.dataset.activeTab = name;
            tabs.forEach((tab) => {
                const selected = tab.dataset.aiChatTab === name;
                tab.classList.toggle('is-active', selected);
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.tabIndex = selected ? 0 : -1;
                if (selected && focus) tab.focus();
            });
            tabPanels.forEach((tabPanel) => {
                const selected = tabPanel.dataset.aiChatTabPanel === name;
                tabPanel.classList.toggle('is-active', selected);
                tabPanel.hidden = !selected;
            });
            reset.hidden = name !== 'chat';
            if (name === 'chat' && focus) input.focus();
        };

        const money = (value) =>
            new Intl.NumberFormat(config.locale || 'vi', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0,
            }).format(Number(value || 0));

        const interpolate = (text, values) => String(text ?? '').replace(/:([a-z_]+)/gi, (_, key) => values[key] ?? '');
        const scrollEnd = () =>
            requestAnimationFrame(() => {
                conversation.scrollTop = conversation.scrollHeight;
            });
        const setNotice = (message = '', kind = 'error') => {
            notice.textContent = message;
            notice.hidden = !message;
            notice.dataset.kind = kind;
        };
        const showRetry = (message, callback) => {
            notice.replaceChildren(document.createTextNode(message + ' '));
            const button = element('button', 'ai-chat__notice-retry', labels.retry);
            button.type = 'button';
            button.addEventListener('click', callback, { once: true });
            notice.append(button);
            notice.hidden = false;
            notice.dataset.kind = 'error';
        };
        const setBusy = (value) => {
            busy = value;
            input.disabled = value;
            send.disabled = value;
            root.querySelectorAll('[data-ai-chat-prompt]').forEach((button) => {
                button.disabled = value;
            });
        };
        const element = (tag, className, text) => {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined && text !== null) node.textContent = String(text);
            return node;
        };
        const safeUrl = (raw, type) => {
            if (typeof raw !== 'string' || raw === '') return null;
            try {
                const url = new URL(raw, window.location.origin);
                if (['http:', 'https:'].includes(url.protocol)) return url.href;
                if (type === 'call_hotline' && url.protocol === 'tel:') return url.href;
                if (type === 'email' && url.protocol === 'mailto:') return url.href;
            } catch {
                return null;
            }
            return null;
        };
        const findLink = (links, type, productId = null) =>
            (Array.isArray(links) ? links : []).find(
                (link) => link?.type === type && (productId === null || Number(link.product_id) === Number(productId)),
            );

        const renderBubble = (role, content, timestamp = null) => {
            const row = element('article', `ai-chat__message ai-chat__message--${role}`);
            row.dataset.aiChatMessage = '';
            const bubble = element('div', 'ai-chat__bubble', content);
            row.append(bubble);
            if (timestamp) {
                const time = element('time', 'ai-chat__time');
                const date = new Date(timestamp);
                if (!Number.isNaN(date.getTime())) {
                    time.dateTime = date.toISOString();
                    time.textContent = new Intl.DateTimeFormat(config.locale, {
                        hour: '2-digit',
                        minute: '2-digit',
                    }).format(date);
                    row.append(time);
                }
            }
            conversation.append(row);
            return row;
        };

        const renderWelcome = (prompts = []) => {
            conversation.replaceChildren();
            const welcome = element('section', 'ai-chat__welcome');
            const icon = element('span', 'ai-chat__welcome-mark', '89');
            icon.setAttribute('aria-hidden', 'true');
            welcome.append(icon, element('p', '', labels.greeting), element('small', '', labels.empty));
            if (Array.isArray(prompts) && prompts.length) {
                welcome.append(element('h3', '', labels.suggested_title));
                const list = element('div', 'ai-chat__prompts');
                prompts.slice(0, 5).forEach((prompt) => {
                    if (typeof prompt !== 'string' || !prompt.trim()) return;
                    const button = element('button', 'ai-chat__prompt', prompt);
                    button.type = 'button';
                    button.dataset.aiChatPrompt = prompt;
                    list.append(button);
                });
                welcome.append(list);
            }
            conversation.append(welcome);
        };

        const actionButton = (action, links, handler) => {
            if (!action || !allowedActions.has(action.type)) return null;
            if (mutationActions.has(action.type) || action.type === 'new_conversation') {
                const button = element('button', 'ai-chat__action', action.label);
                button.type = 'button';
                button.addEventListener('click', () => handler(button));
                return button;
            }
            const link = findLink(links, action.type, action.payload?.product_id ?? null);
            const href = safeUrl(link?.url, action.type);
            if (!href) return null;
            const anchor = element('a', 'ai-chat__action', action.label);
            anchor.href = href;
            if (action.type === 'open_map') {
                anchor.target = '_blank';
                anchor.rel = 'noopener noreferrer';
            }
            return anchor;
        };

        const request = async (url, options = {}) => {
            const controller = new AbortController();
            const timer = window.setTimeout(() => controller.abort(), 20000);
            try {
                const response = await fetch(url, {
                    ...options,
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(options.headers ?? {}),
                    },
                });
                const type = response.headers.get('content-type') ?? '';
                const payload = type.includes('application/json') ? await response.json().catch(() => null) : null;
                if (!response.ok || !payload || typeof payload !== 'object') {
                    const error = new Error(payload?.message || labels.generic_error);
                    error.status = response.status;
                    error.payload = payload;
                    throw error;
                }
                return payload;
            } catch (error) {
                if (error.name === 'AbortError') error.code = 'timeout';
                throw error;
            } finally {
                window.clearTimeout(timer);
            }
        };

        const errorMessage = (error) => {
            if (!navigator.onLine) return labels.offline;
            if (error.code === 'timeout') return labels.timeout;
            if (error.status === 419) return labels.csrf_error;
            if (error.status === 422) return error.payload?.message || labels.validation_error;
            if (error.status === 429) return labels.rate_limit;
            if (error.status === 404) return labels.feature_unavailable;
            return labels.generic_error;
        };

        const cartMutation = async (button, url, items, batch = false) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            try {
                const body = batch ? { items } : { product_id: items[0].product_id, variant_id: items[0].variant_id ?? null, quantity: items[0].quantity };
                const payload = await request(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });
                document.dispatchEvent(new CustomEvent('customer:cart-updated', { detail: payload }));
                const feedback = renderBubble('system', batch ? labels.set_added : labels.cart_added);
                if (batch) {
                    const href = safeUrl(config.endpoints.cart, 'view_cart');
                    if (href) {
                        const openCart = element('a', 'ai-chat__action', labels.open_cart);
                        openCart.href = href;
                        feedback.append(openCart);
                    }
                }
                scrollEnd();
            } catch {
                renderBubble('error', batch ? labels.set_failed : labels.cart_failed);
                scrollEnd();
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        };

        const renderProducts = (products, actions, links, container) => {
            if (!Array.isArray(products) || !products.length) return;
            const cards = element('div', 'ai-chat__products');
            products.forEach((product) => {
                if (!product || !Number.isInteger(Number(product.id)) || typeof product.name !== 'string') return;
                const card = element('article', 'ai-chat__product');
                card.dataset.aiChatProductCard = '';
                card.dataset.productId = product.id;
                card.dataset.productName = product.name;
                card.dataset.productCategory = product.category?.name ?? '';
                card.dataset.productDescription = product.description || product.short_description || '—';
                card.dataset.productPrice = money(product.price);
                card.dataset.productImage = product.image_url || '';
                card.dataset.productMedia = JSON.stringify(Array.isArray(product.media) ? product.media : []);
                card.dataset.productAvailable = '1';
                card.dataset.productStatus = labels.available;
                card.dataset.productUrl = product.detail_url || '#';
                const open = element('button', 'ai-chat__product-open');
                open.type = 'button';
                open.dataset.productModal = '';
                open.setAttribute('aria-label', product.name);
                if (product.image_url) {
                    const image = element('img', 'ai-chat__product-image');
                    image.src = product.image_url;
                    image.alt = '';
                    image.loading = 'lazy';
                    image.addEventListener('error', () =>
                        image.replaceWith(element('span', 'ai-chat__product-fallback', '89')),
                    );
                    open.append(image);
                } else open.append(element('span', 'ai-chat__product-fallback', '89'));
                const copy = element('div', 'ai-chat__product-copy');
                copy.append(element('small', '', product.category?.name ?? ''), element('h3', '', product.name));
                copy.append(element('strong', 'ai-chat__price', money(product.price)));
                const controls = element('div', 'ai-chat__card-actions');
                const addAction = actions.find(
                    (item) =>
                        item.type === 'add_product_to_cart' && Number(item.payload?.product_id) === Number(product.id),
                );
                const add = actionButton(addAction, links, (button) =>
                    cartMutation(button, config.endpoints.cartItem, [
                        { product_id: Number(product.id), variant_id: product.variants?.[0]?.id ?? null, quantity: Number(addAction.payload?.quantity || 1) },
                    ]),
                );
                if (add) {
                    add.textContent = '+';
                    add.classList.add('ai-chat__product-add');
                    add.setAttribute('aria-label', `${labels.add_item}: ${product.name}`);
                    controls.append(add);
                }
                open.append(copy);
                card.append(open, controls);
                cards.append(card);
            });
            if (cards.childElementCount) container.append(cards);
        };

        const renderRecommendation = (recommendation, products, actions, links, container) => {
            if (!recommendation || !Array.isArray(recommendation.items) || !recommendation.items.length) return;
            const card = element('section', 'ai-chat__recommendation');
            card.append(element('h3', '', labels.recommendation_title));
            if (recommendation.summary)
                card.append(element('p', 'ai-chat__recommendation-summary', recommendation.summary));
            const list = element('ul', 'ai-chat__recommendation-items');
            recommendation.items.forEach((item) => {
                if (!item || !Number.isInteger(Number(item.product_id))) return;
                const row = element('li', 'ai-chat__recommendation-item');
                const product = (Array.isArray(products) ? products : []).find(
                    (candidate) => Number(candidate.id) === Number(item.product_id),
                );
                const heading = element('button', 'ai-chat__recommendation-line');
                heading.type = 'button';
                if (product?.image_url) {
                    const image = element('img', 'ai-chat__recommendation-image');
                    image.src = product.image_url;
                    image.alt = '';
                    image.loading = 'lazy';
                    heading.append(image);
                } else {
                    heading.append(element('span', 'ai-chat__recommendation-fallback', '89'));
                }
                const copy = element('span', 'ai-chat__recommendation-copy');
                copy.append(
                    element('strong', '', item.name ?? ''),
                    element('small', '', product?.short_description || item.reason || ''),
                    element('em', '', `${Number(item.quantity || 1)} × ${money(item.price || product?.price)}`),
                );
                heading.append(copy);
                row.append(heading);
                const controls = element('div', 'ai-chat__card-actions');
                controls.append(element('strong', 'ai-chat__recommendation-price', money(item.line_total)));
                const addAction = actions.find(
                    (action) =>
                        action.type === 'add_product_to_cart' &&
                        Number(action.payload?.product_id) === Number(item.product_id),
                );
                const add = actionButton(addAction, links, (button) =>
                    cartMutation(button, config.endpoints.cartItem, [
                        { product_id: Number(item.product_id), variant_id: item.variant_id ?? null, quantity: Number(item.quantity || 1) },
                    ]),
                );
                if (add) {
                    add.textContent = '+';
                    add.classList.add('ai-chat__product-add');
                    add.setAttribute('aria-label', `${labels.add_item}: ${item.name ?? ''}`);
                    controls.append(add);
                }
                if (product) {
                    row.dataset.aiChatProductCard = '';
                    row.dataset.productId = product.id;
                    row.dataset.productName = product.name;
                    row.dataset.productCategory = product.category?.name ?? '';
                    row.dataset.productDescription = product.description || product.short_description || '—';
                    row.dataset.productPrice = money(product.price);
                    row.dataset.productImage = product.image_url || '';
                    row.dataset.productMedia = JSON.stringify(Array.isArray(product.media) ? product.media : []);
                    row.dataset.productAvailable = '1';
                    row.dataset.productStatus = labels.available;
                    row.dataset.productUrl = product.detail_url || '#';
                    heading.dataset.productModal = '';
                }
                row.append(controls);
                list.append(row);
            });
            card.append(list);
            const total = element('div', 'ai-chat__recommendation-total');
            total.append(
                element('span', '', labels.estimated_total),
                element('strong', '', money(recommendation.estimated_total)),
            );
            card.append(total);
            if (recommendation.budget_status && recommendation.budget_status !== 'not_provided') {
                const fits = ['within_budget', 'fits_budget', 'under_budget'].includes(recommendation.budget_status);
                const status = element(
                    'span',
                    `ai-chat__budget ai-chat__budget--${fits ? 'fit' : 'over'}`,
                    fits ? labels.budget_fit : labels.budget_over,
                );
                card.append(status);
            }
            const batchAction = actions.find((action) => action.type === 'add_recommendation_set_to_cart');
            if (batchAction && Array.isArray(batchAction.payload?.items)) {
                const addSet = actionButton(batchAction, links, (button) => {
                    const items = batchAction.payload.items
                        .map((item) => ({ product_id: Number(item.product_id), variant_id: item.variant_id ?? null, quantity: Number(item.quantity) }))
                        .filter(
                            (item) =>
                                Number.isInteger(item.product_id) &&
                                item.product_id > 0 &&
                                Number.isInteger(item.quantity) &&
                                item.quantity > 0,
                        );
                    const count = items.reduce((sum, item) => sum + item.quantity, 0);
                    if (items.length && window.confirm(interpolate(labels.batch_confirmation, { count }))) {
                        cartMutation(button, config.endpoints.cartBatch, items, true);
                    }
                });
                if (addSet) {
                    addSet.classList.add('ai-chat__action--primary');
                    addSet.textContent = labels.add_set;
                    card.append(addSet);
                }
            }
            container.append(card);
        };

        const renderHandoff = (handoff, container) => {
            if (!handoff?.recommended) return;
            const card = element('aside', 'ai-chat__handoff');
            card.append(element('h3', '', labels.handoff_title));
            const actions = element('div', 'ai-chat__card-actions');
            [
                [handoff.phone_url, 'call_hotline', labels.phone],
                [handoff.email_url, 'email', labels.email],
                [handoff.contact_url, 'open_contact', null],
            ].forEach(([url, type, label]) => {
                const href = safeUrl(url, type);
                if (!href) return;
                const link = element('a', 'ai-chat__action', label || labels.handoff_title);
                link.href = href;
                actions.append(link);
            });
            card.append(actions);
            container.append(card);
        };

        const renderResponse = (payload) => {
            const block = element('div', 'ai-chat__response');
            const row = renderBubble('assistant', payload.message);
            row.append(block);
            const actions = Array.isArray(payload.actions)
                ? payload.actions.filter((action) => allowedActions.has(action?.type))
                : [];
            const links = Array.isArray(payload.links) ? payload.links : [];
            const call = actions.find((action) => action.type === 'call_hotline');
            const phone = call?.payload?.phone;
            const callHref = safeUrl(findLink(links, 'call_hotline')?.url, 'call_hotline');
            const inlineLinks = [];
            if (typeof phone === 'string' && phone && callHref) inlineLinks.push({ text: phone, href: callHref });
            links.filter((link) => ['facebook', 'zalo', 'map'].includes(link?.type)).forEach((link) => {
                const href = safeUrl(link.url, link.type);
                if (href) inlineLinks.push({ text: link.url, href });
            });
            if (inlineLinks.length) {
                const bubble = row.querySelector('.ai-chat__bubble');
                const message = String(payload.message ?? '');
                const nodes = [];
                let position = 0;
                while (position < message.length) {
                    const next = inlineLinks
                        .map((link) => ({ ...link, index: message.indexOf(link.text, position) }))
                        .filter((link) => link.index >= 0)
                        .sort((first, second) => first.index - second.index)[0];
                    if (!next) break;
                    nodes.push(document.createTextNode(message.slice(position, next.index)));
                    const anchor = element('a', 'ai-chat__phone-link', next.text);
                    anchor.href = next.href;
                    if (next.href.startsWith('https:')) {
                        anchor.target = '_blank';
                        anchor.rel = 'noopener noreferrer';
                    }
                    nodes.push(anchor);
                    position = next.index + next.text.length;
                }
                nodes.push(document.createTextNode(message.slice(position)));
                bubble.replaceChildren(...nodes);
            }
            if (!payload.recommendation) renderProducts(payload.products, actions, links, block);
            renderRecommendation(payload.recommendation, payload.products, actions, links, block);
            renderHandoff(payload.handoff, block);
            const general = element('div', 'ai-chat__actions');
            actions
                .filter(
                    (action) =>
                        !['view_product', 'add_product_to_cart', 'add_recommendation_set_to_cart', 'call_hotline'].includes(
                            action.type,
                        ),
                )
                .forEach((action) => {
                    const control = actionButton(action, links, () => resetConversation());
                    if (control) general.append(control);
                });
            if (general.childElementCount) block.append(general);
        };

        const loadState = async () => {
            conversation.innerHTML = '<div class="ai-chat__loading"><span></span><span></span><span></span></div>';
            try {
                const payload = await request(config.endpoints.state);
                const messages = payload.conversation?.messages;
                messageCount = Number(payload.conversation?.message_count || 0);
                if (Array.isArray(messages) && messages.length) {
                    conversation.replaceChildren();
                    messages.forEach((message) => {
                        if (message.role === 'assistant' && message.presentation) {
                            renderResponse({ message: message.content ?? '', ...message.presentation });
                            return;
                        }
                        renderBubble(
                            message.role === 'user' ? 'user' : 'assistant',
                            message.content ?? '',
                            message.timestamp,
                        );
                    });
                    scrollEnd();
                } else {
                    renderWelcome(payload.conversation?.suggested_prompts ?? []);
                    conversation.scrollTop = 0;
                }
                loaded = true;
                setNotice();
            } catch (error) {
                conversation.replaceChildren();
                showRetry(errorMessage(error), loadState);
                if (error.status === 404) disableWidget();
            }
        };

        const sendMessage = async (message) => {
            const text = String(message ?? '').trim();
            if (!text || busy || text.length > Number(config.maxLength || 1500)) return;
            input.value = '';
            updateCounter();
            setBusy(true);
            setNotice();
            renderBubble('user', text);
            const typing = renderBubble('typing', labels.typing);
            typing.setAttribute('aria-label', labels.typing);
            scrollEnd();
            try {
                const payload = await request(config.endpoints.messages, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text }),
                });
                typing.remove();
                renderResponse(payload);
                messageCount = Number(payload.conversation?.message_count || messageCount + 2);
                scrollEnd();
            } catch (error) {
                typing.remove();
                setNotice(errorMessage(error));
                if (error.status === 404) disableWidget();
            } finally {
                setBusy(false);
                input.focus();
            }
        };

        const resetConversation = async () => {
            if (busy || (messageCount > 0 && !window.confirm(labels.reset_confirmation))) return;
            setBusy(true);
            setNotice();
            try {
                const payload = await request(config.endpoints.reset, { method: 'POST' });
                messageCount = 0;
                renderWelcome(payload.conversation?.suggested_prompts ?? []);
                scrollEnd();
            } catch (error) {
                setNotice(errorMessage(error));
                if (error.status === 404) disableWidget();
            } finally {
                setBusy(false);
                input.focus();
            }
        };

        const disableWidget = () => {
            closePanel();
            root.hidden = true;
        };
        const focusable = () => [
            ...panel.querySelectorAll('button:not([disabled]), a[href], textarea:not([disabled])'),
        ];
        const openPanel = async () => {
            lastFocus = document.activeElement;
            panel.hidden = false;
            backdrop.hidden = false;
            panel.setAttribute('aria-modal', window.matchMedia('(max-width: 767.98px)').matches ? 'true' : 'false');
            launcher.setAttribute('aria-expanded', 'true');
            document.body.classList.add('ai-chat-open');
            if (!loaded) await loadState();
            input.focus();
        };
        const closePanel = () => {
            panel.hidden = true;
            backdrop.hidden = true;
            launcher.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('ai-chat-open');
            (lastFocus instanceof HTMLElement ? lastFocus : launcher).focus();
        };
        const updateCounter = () => {
            const length = input.value.length;
            counter.textContent = `${length}/${config.maxLength}`;
            counter.hidden = length < Number(config.maxLength) * 0.8;
        };

        launcher.addEventListener('click', () => (panel.hidden ? openPanel() : closePanel()));
        root.querySelector('[data-ai-chat-close]')?.addEventListener('click', closePanel);
        backdrop.addEventListener('click', closePanel);
        reset.addEventListener('click', resetConversation);
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab.dataset.aiChatTab, true));
            tab.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const direction = event.key === 'ArrowRight' ? 1 : -1;
                const next = tabs[(index + direction + tabs.length) % tabs.length];
                selectTab(next.dataset.aiChatTab, true);
            });
        });
        composer.addEventListener('submit', (event) => {
            event.preventDefault();
            sendMessage(input.value);
        });
        input.addEventListener('input', updateCounter);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                composer.requestSubmit();
            }
        });
        conversation.addEventListener('click', (event) => {
            const prompt = event.target.closest('[data-ai-chat-prompt]');
            if (prompt instanceof HTMLButtonElement && !busy) sendMessage(prompt.dataset.aiChatPrompt);
        });
        document.addEventListener('keydown', (event) => {
            if (panel.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closePanel();
                return;
            }
            if (event.key === 'Tab' && window.matchMedia('(max-width: 767.98px)').matches) {
                const items = focusable();
                const first = items[0];
                const last = items.at(-1);
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first?.focus();
                }
            }
        });
        selectTab(activeTab);
    }
}

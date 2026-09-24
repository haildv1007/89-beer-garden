import './bootstrap';
import { Modal } from 'bootstrap';

const menuCategoryScrollKey = 'menu-category-scroll-left';
let savedMenuCategoryScroll = null;

try {
    const storedScroll = sessionStorage.getItem(menuCategoryScrollKey);
    savedMenuCategoryScroll = storedScroll === null ? null : Number(storedScroll);
    sessionStorage.removeItem(menuCategoryScrollKey);
} catch {
    savedMenuCategoryScroll = null;
}

const revealActiveMenuCategory = () => {
    document.querySelectorAll('.category-pills-scroll').forEach((rail) => {
        const activeCategory = rail.querySelector('.category-pill.active');
        if (!(rail instanceof HTMLElement) || !(activeCategory instanceof HTMLElement)) return;

        const categoryLeft = activeCategory.getBoundingClientRect().left
            - rail.getBoundingClientRect().left
            + rail.scrollLeft;

        if (window.matchMedia('(max-width: 767.98px)').matches) {
            rail.scrollLeft = Math.max(0, categoryLeft);
            return;
        }

        if (Number.isFinite(savedMenuCategoryScroll)) {
            rail.scrollLeft = savedMenuCategoryScroll;
            return;
        }

        const targetLeft = categoryLeft - (rail.clientWidth - activeCategory.offsetWidth) / 2;
        rail.scrollTo({ left: Math.max(0, targetLeft), behavior: 'instant' });
    });
};

document.addEventListener('click', (event) => {
    const menuLink = event.target.closest('.category-pills a');
    const rail = menuLink?.closest('.category-pills')?.querySelector('.category-pills-scroll');
    if (!(rail instanceof HTMLElement)) return;

    if (menuLink.matches('[data-menu-category-link]')) {
        const targetSelector = menuLink.getAttribute('href');
        const target = targetSelector?.startsWith('#') ? document.querySelector(targetSelector) : null;

        if (target instanceof HTMLElement) {
            event.preventDefault();
            rail.querySelectorAll('[data-menu-category-link]').forEach((link) => {
                link.classList.toggle('active', link === menuLink);
            });
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.replaceState(null, '', targetSelector);
            return;
        }

        // The horizontal rail is navigation only. Never turn a missing section
        // into a server-side category filter or a full page reload.
        event.preventDefault();
        return;
    }

    try {
        sessionStorage.setItem(menuCategoryScrollKey, String(rail.scrollLeft));
    } catch {
        // Filtering still works when storage is unavailable.
    }
});

requestAnimationFrame(revealActiveMenuCategory);
document.fonts?.ready.then(revealActiveMenuCategory);
window.addEventListener('pageshow', revealActiveMenuCategory);

const menuSections = [...document.querySelectorAll('.menu-section[id^="menu-category-"]')];
if (menuSections.length > 0 && 'IntersectionObserver' in window) {
    const categoryObserver = new IntersectionObserver((entries) => {
        const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (!visible) return;

        const activeLink = document.querySelector(`[data-menu-category-link][href="#${visible.target.id}"]`);
        if (!(activeLink instanceof HTMLElement)) return;
        document.querySelectorAll('[data-menu-category-link]').forEach((link) => {
            link.classList.toggle('active', link === activeLink);
        });
    }, { rootMargin: '-22% 0px -68% 0px', threshold: 0 });

    menuSections.forEach((section) => categoryObserver.observe(section));
}

document.querySelectorAll('.category-pills-scroll').forEach((rail) => {
    if (!(rail instanceof HTMLElement)) return;
    const previousButton = rail.parentElement?.querySelector('.category-scroll-prev');
    const nextButton = rail.parentElement?.querySelector('.category-scroll-next');
    const categories = [...rail.querySelectorAll('.category-pill')];

    const updateScrollButtons = () => {
        const maxScroll = Math.max(0, rail.scrollWidth - rail.clientWidth);
        [previousButton, nextButton].forEach((button) => {
            if (button instanceof HTMLButtonElement) button.hidden = maxScroll < 2;
        });
        if (previousButton instanceof HTMLButtonElement) previousButton.disabled = rail.scrollLeft <= 2;
        if (nextButton instanceof HTMLButtonElement) nextButton.disabled = rail.scrollLeft >= maxScroll - 2;
    };

    const moveByCategories = (direction) => {
        const railRect = rail.getBoundingClientRect();
        const firstVisibleIndex = categories.findIndex((category) => category.getBoundingClientRect().right > railRect.left + 2);
        const currentIndex = Math.max(0, firstVisibleIndex);
        const targetIndex = Math.min(Math.max(0, currentIndex + direction * 3), categories.length - 1);
        const target = categories[targetIndex];
        if (!(target instanceof HTMLElement)) return;
        const targetLeft = target.getBoundingClientRect().left - railRect.left + rail.scrollLeft;
        rail.scrollTo({ left: Math.max(0, targetLeft), behavior: 'smooth' });
    };

    previousButton?.addEventListener('click', () => moveByCategories(-1));
    nextButton?.addEventListener('click', () => moveByCategories(1));

    let pointerId = null;
    let startX = 0;
    let startScrollLeft = 0;
    let dragged = false;
    let suppressClick = false;

    rail.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        pointerId = event.pointerId;
        startX = event.clientX;
        startScrollLeft = rail.scrollLeft;
        dragged = false;
    });
    window.addEventListener('pointermove', (event) => {
        if (pointerId !== event.pointerId) return;
        const distance = event.clientX - startX;
        if (!dragged && Math.abs(distance) > 8) {
            dragged = true;
            rail.classList.add('is-dragging');
        }
        if (!dragged) return;
        event.preventDefault();
        rail.scrollLeft = startScrollLeft - distance;
    }, { passive: false });
    window.addEventListener('pointerup', (event) => {
        if (pointerId !== event.pointerId) return;
        suppressClick = dragged;
        pointerId = null;
        dragged = false;
        rail.classList.remove('is-dragging');
        window.setTimeout(() => { suppressClick = false; }, 0);
    });
    rail.addEventListener('click', (event) => {
        if (!suppressClick) return;
        event.preventDefault();
        event.stopPropagation();
    }, true);
    rail.addEventListener('dragstart', (event) => event.preventDefault());

    rail.addEventListener('scroll', updateScrollButtons, { passive: true });
    window.addEventListener('resize', updateScrollButtons);
    requestAnimationFrame(updateScrollButtons);
    rail.addEventListener('wheel', (event) => {
        if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;
        const previous = rail.scrollLeft;
        rail.scrollLeft += event.deltaY;
        if (rail.scrollLeft !== previous) event.preventDefault();
    }, { passive: false });
});

document.addEventListener('click', (event) => {
    const filter = event.target.closest('[data-timeline-filter]');
    if (!filter) return;
    const modal = filter.closest('.session-timeline-modal');
    if (!modal) return;
    const category = filter.dataset.timelineFilter;
    modal.querySelectorAll('[data-timeline-filter]').forEach((button) => button.classList.toggle('is-active', button === filter));
    let visible = 0;
    modal.querySelectorAll('[data-timeline-category]').forEach((item) => {
        const show = category === 'all' || item.dataset.timelineCategory === category;
        item.hidden = !show;
        if (show) visible += 1;
    });
    const empty = modal.querySelector('[data-timeline-empty]');
    if (empty) empty.hidden = visible > 0;
});

if (document.querySelector('[data-post-editor]')) {
    import('./post-editor');
}

document.querySelectorAll('[data-media-slot]').forEach((slot) => {
    const input = slot.querySelector('[data-media-input]');
    const preview = slot.querySelector('[data-media-preview]');
    const dropzone = slot.querySelector('[data-media-dropzone]');
    const removeInput = slot.querySelector('[data-media-remove-input]');
    let objectUrl = null;

    const updateCount = () => {
        const grid = slot.closest('[data-media-grid]');
        const count = grid?.closest('.admin-product-media')?.querySelector('[data-media-count]');
        if (count) count.textContent = `${grid.querySelectorAll('[data-media-preview].has-media').length}/5`;
    };

    const showClientError = (message) => {
        slot.querySelector('[data-media-client-error]')?.remove();
        const error = document.createElement('div');
        error.className = 'admin-media-client-error';
        error.dataset.mediaClientError = '';
        error.textContent = message;
        slot.append(error);
    };

    if (!(input instanceof HTMLInputElement) || !(preview instanceof HTMLElement) || !(dropzone instanceof HTMLElement))
        return;

    const showFile = (file) => {
        if (!(file instanceof File) || (!file.type.startsWith('image/') && !file.type.startsWith('video/'))) return;
        slot.querySelector('[data-media-client-error]')?.remove();
        if (file.size > 5 * 1024 * 1024) {
            input.value = '';
            showClientError(
                `Tệp ${file.name} có dung lượng ${(file.size / 1024 / 1024).toFixed(2)} MB, vượt giới hạn 5 MB.`,
            );
            return;
        }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        preview.innerHTML = file.type.startsWith('video/')
            ? `<video src="${objectUrl}" controls muted preload="metadata"></video><span class="admin-media-type">Video</span>`
            : `<img src="${objectUrl}" alt="Media vừa chọn">`;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'admin-media-remove';
        remove.dataset.mediaRemove = '';
        remove.setAttribute('aria-label', 'Bỏ media vừa chọn');
        remove.textContent = '×';
        preview.append(remove);
        preview.classList.add('has-media');
        dropzone.classList.add('d-none');
        slot.setAttribute('draggable', 'true');
        slot.querySelector('[data-media-file-meta]')?.remove();
        const meta = document.createElement('span');
        meta.className = 'admin-media-file-meta';
        meta.dataset.mediaFileMeta = '';
        meta.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        preview.after(meta);
        if (!slot.querySelector('.admin-media-drag-hint')) {
            const hint = document.createElement('span');
            hint.className = 'admin-media-drag-hint';
            hint.textContent = '⋮⋮ Kéo đổi vị trí';
            preview.after(hint);
        }
        updateCount();
    };

    const clearSlot = () => {
        input.value = '';
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        preview.replaceChildren();
        preview.classList.remove('has-media');
        dropzone.classList.remove('d-none');
        slot.removeAttribute('draggable');
        slot.querySelector('.admin-media-drag-hint')?.remove();
        slot.querySelector('[data-media-file-meta]')?.remove();
        if (removeInput instanceof HTMLInputElement) removeInput.checked = true;
        slot.querySelector('[data-media-client-error]')?.remove();
        updateCount();
    };

    input.addEventListener('change', () => showFile(input.files?.[0]));
    preview.addEventListener('click', (event) => {
        if (event.target.closest('[data-media-remove]')) clearSlot();
    });
    ['dragenter', 'dragover'].forEach((name) =>
        dropzone.addEventListener(name, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        }),
    );
    ['dragleave', 'drop'].forEach((name) =>
        dropzone.addEventListener(name, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        }),
    );
    dropzone.addEventListener('drop', (event) => {
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });
});

document.querySelectorAll('[data-media-grid]').forEach((grid) => {
    let draggedSlot = null;

    const refreshPositions = () => {
        grid.querySelectorAll('[data-media-slot]').forEach((slot, index) => {
            const position = index + 1;
            const heading = slot.querySelector('.admin-media-slot__heading strong');
            const positionLabel = slot.querySelector('[data-media-position-label]');
            const input = slot.querySelector('[data-media-input]');
            const dropzone = slot.querySelector('[data-media-dropzone]');
            if (heading) heading.textContent = `Vị trí ${position}`;
            if (positionLabel) positionLabel.textContent = position === 1 ? 'Ảnh đại diện' : '';
            if (input instanceof HTMLInputElement) {
                input.name = `media_slots[${position}]`;
                input.id = `media_slot_${position}`;
            }
            if (dropzone instanceof HTMLLabelElement) dropzone.htmlFor = `media_slot_${position}`;
        });
    };

    grid.addEventListener('dragstart', (event) => {
        const slot = event.target.closest('[data-media-slot][draggable="true"]');
        if (!(slot instanceof HTMLElement)) return;
        draggedSlot = slot;
        slot.classList.add('is-sorting');
        event.dataTransfer.effectAllowed = 'move';
    });
    grid.addEventListener('dragover', (event) => {
        const target = event.target.closest('[data-media-slot]');
        if (!(target instanceof HTMLElement) || !draggedSlot || target === draggedSlot) return;
        event.preventDefault();
        grid.querySelectorAll('.is-sort-target').forEach((slot) => slot.classList.remove('is-sort-target'));
        target.classList.add('is-sort-target');
    });
    grid.addEventListener('drop', (event) => {
        const target = event.target.closest('[data-media-slot]');
        if (!(target instanceof HTMLElement) || !draggedSlot || target === draggedSlot) return;
        event.preventDefault();
        const slots = [...grid.querySelectorAll('[data-media-slot]')];
        slots.indexOf(draggedSlot) < slots.indexOf(target) ? target.after(draggedSlot) : target.before(draggedSlot);
        refreshPositions();
    });
    grid.addEventListener('dragend', () => {
        grid.querySelectorAll('.is-sorting,.is-sort-target').forEach((slot) =>
            slot.classList.remove('is-sorting', 'is-sort-target'),
        );
        draggedSlot = null;
    });
});

document.querySelectorAll('[data-product-variants]').forEach((section) => {
    const toggle = section.querySelector('[data-variants-toggle]');
    const fields = section.querySelector('[data-variants-fields]');
    const empty = section.querySelector('[data-variants-empty]');
    if (!(toggle instanceof HTMLInputElement) || !(fields instanceof HTMLElement)) return;

    const syncVariantState = () => {
        const enabled = toggle.checked;
        fields.hidden = !enabled;
        if (empty instanceof HTMLElement) empty.hidden = enabled;
        fields.querySelectorAll('input, select, textarea').forEach((input) => {
            input.disabled = !enabled;
        });
        section.classList.toggle('is-enabled', enabled);
    };

    toggle.addEventListener('change', syncVariantState);
    syncVariantState();
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const confirmation = form.dataset.confirm;
    if (confirmation && !window.confirm(confirmation)) {
        event.preventDefault();
        return;
    }

    if (form.classList.contains('js-submit-once')) {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    }
});

document.querySelectorAll('[data-hero-video]').forEach((video) => {
    if (!(video instanceof HTMLVideoElement)) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const saveData = navigator.connection?.saveData === true;
    if (reducedMotion.matches || saveData) return;

    const activate = () => {
        if (video.src || !video.dataset.src) return;
        video.src = video.dataset.src;
        video.load();
        video.play().catch(() => {});
    };
    const schedule = () => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(activate, { timeout: 1600 });
        } else {
            window.setTimeout(activate, 350);
        }
    };

    if (document.readyState === 'complete') schedule();
    else window.addEventListener('load', schedule, { once: true });

    document.addEventListener('visibilitychange', () => {
        if (!video.src) return;
        if (document.hidden) video.pause();
        else video.play().catch(() => {});
    });
});

document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');

    if (!(track instanceof HTMLElement)) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const updateControls = () => {
        const maxScroll = track.scrollWidth - track.clientWidth;
        carousel.classList.toggle('is-static', maxScroll <= 2);
        previous?.toggleAttribute('disabled', track.scrollLeft <= 2);
        next?.toggleAttribute('disabled', track.scrollLeft >= maxScroll - 2 || maxScroll <= 2);
    };
    const getColumnStep = () => {
        const items = Array.from(track.children).filter((item) => item instanceof HTMLElement);
        if (items.length >= 3) {
            const distance = items[2].offsetLeft - items[0].offsetLeft;
            if (distance > 0) return distance;
        }
        return Math.max(track.clientWidth * 0.82, 280);
    };
    const move = (direction) => {
        const distance = carousel.hasAttribute('data-carousel-column')
            ? getColumnStep()
            : Math.max(track.clientWidth * 0.82, 280);
        track.scrollBy({
            left: direction * distance,
            behavior: reducedMotion ? 'auto' : 'smooth',
        });
    };

    previous?.addEventListener('click', () => move(-1));
    next?.addEventListener('click', () => move(1));
    track.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls);
    updateControls();
});

document.querySelectorAll('.menu-search').forEach((form) => {
    form.addEventListener('submit', () => {
        if (!window.matchMedia('(max-width: 767.98px)').matches) return;
        const category = form.elements.namedItem('category');
        if (category instanceof HTMLSelectElement) category.disabled = true;
    });
});

document.querySelectorAll('.cart-quantity-form').forEach((form) => {
    const quantity = form.querySelector('input[name="quantity"]');
    const minus = form.querySelector('[data-cart-quantity-minus]');
    const plus = form.querySelector('[data-cart-quantity-plus]');

    if (!(form instanceof HTMLFormElement) || !(quantity instanceof HTMLInputElement)) return;

    const money = (value) => `${new Intl.NumberFormat('vi-VN').format(value)} ₫`;
    let confirmedQuantity = Number(quantity.value || 1);
    const changeQuantity = (amount) => {
        const current = Number(quantity.value || 1);
        const next = Math.min(1000, Math.max(1, current + amount));
        if (next === current) return;
        quantity.value = String(next);
        form.requestSubmit();
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const buttons = form.querySelectorAll('button');
        buttons.forEach((button) => (button.disabled = true));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Unable to update cart.');
            quantity.value = String(data.quantity);
            confirmedQuantity = Number(data.quantity);
            const line = form.closest('[data-cart-line]');
            const lineTotal = line?.querySelector('[data-cart-line-total]');
            if (lineTotal) lineTotal.textContent = money(data.line_total);
            document.querySelectorAll('.cart-count').forEach((count) => (count.textContent = String(data.cart_count)));
            const subtotal = document.querySelector('[data-cart-subtotal]');
            const discount = document.querySelector('[data-cart-discount]');
            const total = document.querySelector('[data-cart-total]');
            if (subtotal) subtotal.textContent = money(data.summary.subtotal);
            if (discount) discount.textContent = `− ${money(data.summary.discount)}`;
            if (total) total.textContent = money(data.summary.total);
        } catch (error) {
            quantity.value = String(confirmedQuantity);
            window.alert(error.message);
        } finally {
            buttons.forEach((button) => (button.disabled = false));
        }
    });

    minus?.addEventListener('click', () => changeQuantity(-1));
    plus?.addEventListener('click', () => changeQuantity(1));
});

document.querySelectorAll('[data-fulfillment-form]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;
    const money = (value) => `${new Intl.NumberFormat('vi-VN').format(value)} ₫`;
    const takeawayToggle = form.querySelector('[data-takeaway-toggle]');
    const takeawayMethods = form.querySelector('[data-takeaway-methods]');
    takeawayToggle?.addEventListener('click', () => {
        const open = takeawayMethods?.hasAttribute('hidden');
        takeawayMethods?.toggleAttribute('hidden', !open);
        takeawayToggle.setAttribute('aria-expanded', String(open));
        if (open) {
            const selected = takeawayMethods?.querySelector('input:checked');
            const preferred = takeawayMethods?.querySelector('input[value="delivery"]');
            const target = selected || preferred;
            target?.focus();
            if (!selected && preferred instanceof HTMLInputElement) {
                preferred.checked = true;
                preferred.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    });
    form.querySelectorAll('input[name="fulfillment_type"]').forEach((input) => {
        input.addEventListener('change', async () => {
            const options = form.querySelectorAll('.fulfillment-option');
            const payload = new FormData(form);
            options.forEach((option) => option.classList.toggle('is-selected', option.contains(input)));
            takeawayToggle?.classList.toggle('is-selected', ['pickup', 'delivery'].includes(input.value));
            if (!['pickup', 'delivery'].includes(input.value)) {
                takeawayMethods?.setAttribute('hidden', '');
                takeawayToggle?.setAttribute('aria-expanded', 'false');
            }
            options.forEach((option) => option.querySelectorAll('input').forEach((radio) => (radio.disabled = true)));

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload,
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to update fulfillment.');
                const empty = document.querySelector('[data-fulfillment-empty]');
                const pickupCheckout = document.querySelector('[data-pickup-checkout]');
                const shipping = document.querySelector('[data-cart-shipping]');
                const shippingRow = document.querySelector('[data-shipping-row]');
                const total = document.querySelector('[data-cart-total]');
                  const deliveryCheckout = document.querySelector('[data-delivery-checkout]');
                  const dineInCheckout = document.querySelector('[data-dine-in-checkout]');
                  const atTableCheckout = document.querySelector('[data-at-table-checkout]');
                empty?.classList.add('d-none');
                pickupCheckout?.classList.toggle('d-none', data.fulfillment_type !== 'pickup');
                deliveryCheckout?.classList.toggle('d-none', data.fulfillment_type !== 'delivery');
                  dineInCheckout?.classList.toggle('d-none', data.fulfillment_type !== 'dine_in');
                  atTableCheckout?.classList.toggle('d-none', data.fulfillment_type !== 'at_table');
                shippingRow?.classList.toggle('d-none', data.fulfillment_type !== 'delivery');
                if (shipping && data.summary) shipping.textContent = money(data.summary.shipping_fee);
                if (total && data.summary) total.textContent = money(data.summary.total);
            } catch (error) {
                window.alert(error.message);
                window.location.reload();
            } finally {
                options.forEach((option) =>
                    option.querySelectorAll('input').forEach((radio) => {
                        radio.disabled = option.classList.contains('is-disabled');
                    }),
                );
            }
        });
    });
});

const closeVariantDropdowns = (except = null) => {
    document.querySelectorAll('.variant-dropdown.is-open').forEach((dropdown) => {
        if (dropdown === except) return;
        dropdown.classList.remove('is-open');
        dropdown.querySelector('.variant-dropdown__trigger')?.setAttribute('aria-expanded', 'false');
    });
};

const refreshVariantDropdown = (select) => {
    if (!(select instanceof HTMLSelectElement)) return;
    let dropdown = select.parentElement?.querySelector(':scope > .variant-dropdown');
    if (!(dropdown instanceof HTMLElement)) {
        dropdown = document.createElement('div');
        dropdown.className = 'variant-dropdown';
        dropdown.innerHTML = '<button class="variant-dropdown__trigger" type="button" aria-expanded="false"><span></span><i aria-hidden="true"></i></button><div class="variant-dropdown__menu" role="listbox"></div>';
        select.insertAdjacentElement('afterend', dropdown);
        select.classList.add('variant-native-select');
        dropdown.querySelector('.variant-dropdown__trigger')?.addEventListener('click', () => {
            const opening = !dropdown.classList.contains('is-open');
            closeVariantDropdowns(opening ? dropdown : null);
            dropdown.classList.toggle('is-open', opening);
            dropdown.querySelector('.variant-dropdown__trigger')?.setAttribute('aria-expanded', String(opening));
            dropdown.classList.remove('opens-up');
            if (opening) {
                const menu = dropdown.querySelector('.variant-dropdown__menu');
                if (menu instanceof HTMLElement && menu.getBoundingClientRect().bottom > window.innerHeight - 76) {
                    dropdown.classList.add('opens-up');
                }
            }
        });
    }
    const label = dropdown.querySelector('.variant-dropdown__trigger span');
    const menu = dropdown.querySelector('.variant-dropdown__menu');
    if (!(label instanceof HTMLElement) || !(menu instanceof HTMLElement)) return;
    label.textContent = select.selectedOptions[0]?.textContent ?? select.dataset.variantPlaceholder ?? '';
    menu.replaceChildren();
    [...select.options].forEach((option, index) => {
        if (option.value === '') return;
        const item = document.createElement('button');
        const [variantName, variantPrice] = option.textContent.split('·').map((part) => part.trim());
        const name = document.createElement('span');
        const price = document.createElement('small');
        item.type = 'button';
        item.className = `variant-dropdown__option${option.selected ? ' is-selected' : ''}`;
        name.textContent = variantName;
        price.textContent = variantPrice ?? '';
        item.append(name, price);
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', String(option.selected));
        item.addEventListener('click', () => {
            select.selectedIndex = index;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            refreshVariantDropdown(select);
            closeVariantDropdowns();
        });
        menu.append(item);
    });
};

const selectedVariantPrice = (select) => {
    if (!(select instanceof HTMLSelectElement)) return null;
    const value = Number(select.selectedOptions[0]?.dataset.price);
    return Number.isFinite(value) && value > 0 ? `${new Intl.NumberFormat('vi-VN').format(value)}đ` : null;
};

document.querySelectorAll('.product-variant-select select').forEach(refreshVariantDropdown);
document.querySelector('#variant_id')?.addEventListener('change', (event) => {
    const price = document.querySelector('[data-product-detail-price]');
    if (!(price instanceof HTMLElement) || !(event.currentTarget instanceof HTMLSelectElement)) return;
    price.textContent = selectedVariantPrice(event.currentTarget) ?? price.dataset.defaultPrice ?? '';
});
document.addEventListener('click', (event) => {
    if (!event.target.closest('.variant-dropdown')) closeVariantDropdowns();
});

const quickViewElement = document.querySelector('#productQuickView');

if (quickViewElement instanceof HTMLElement) {
    const quickView = Modal.getOrCreateInstance(quickViewElement);
    const image = quickViewElement.querySelector('[data-product-modal-image]');
    const video = quickViewElement.querySelector('[data-product-modal-video]');
    const mediaFrame = quickViewElement.querySelector('.product-modal-media');
    const content = quickViewElement.querySelector('.product-modal-content');
    const thumbnails = quickViewElement.querySelector('[data-product-modal-thumbnails]');
    const mediaCount = quickViewElement.querySelector('[data-product-modal-media-count]');
    const mediaCountText = quickViewElement.querySelector('[data-product-modal-media-count-text]');
    const name = quickViewElement.querySelector('[data-product-modal-name]');
    const category = quickViewElement.querySelector('[data-product-modal-category]');
    const description = quickViewElement.querySelector('[data-product-modal-description]');
    const descriptionToggle = quickViewElement.querySelector('[data-product-modal-description-toggle]');
    const price = quickViewElement.querySelector('[data-product-modal-price]');
    const status = quickViewElement.querySelector('[data-product-modal-status]');
    const tags = quickViewElement.querySelector('[data-product-modal-tags]');
    const productId = quickViewElement.querySelector('[data-product-modal-id]');
    const variantWrap = quickViewElement.querySelector('[data-product-modal-variant-wrap]');
    const variantSelect = quickViewElement.querySelector('[data-product-modal-variant]');
    const quantity = quickViewElement.querySelector('[data-product-modal-quantity]');
    const submit = quickViewElement.querySelector('[data-product-modal-submit]');
    const detail = quickViewElement.querySelector('[data-product-modal-detail]');
    let activeMedia = [];
    let activeMediaIndex = 0;
    let activeProductName = '';
    let fullDescription = '';
    let touchStartX = 0;
    let mouseStartX = 0;
    let draggingMedia = false;
    const descriptionLimit = 180;

    const showMedia = (media, productName) => {
        const isVideo = media?.type === 'video';
        if (image instanceof HTMLImageElement) {
            image.hidden = isVideo;
            image.src = isVideo ? '' : (media?.url ?? '');
            image.alt = productName;
        }
        if (video instanceof HTMLVideoElement) {
            video.pause();
            video.hidden = !isVideo;
            video.src = isVideo ? (media?.url ?? '') : '';
        }
        thumbnails
            ?.querySelectorAll('.product-modal-thumbnail')
            .forEach((node, index) => node.classList.toggle('is-active', index === activeMediaIndex));
        if (mediaCountText) mediaCountText.textContent = `${activeMediaIndex + 1}/${activeMedia.length}`;
    };

    const selectMedia = (index) => {
        if (activeMedia.length === 0) return;
        activeMediaIndex = (index + activeMedia.length) % activeMedia.length;
        showMedia(activeMedia[activeMediaIndex], activeProductName);
    };

    const renderGallery = (card) => {
        let media = [];
        try {
            media = JSON.parse(card.dataset.productMedia ?? '[]');
        } catch {
            media = [];
        }
        if (!Array.isArray(media) || media.length === 0) {
            media = [{ type: 'image', url: card.dataset.productImage ?? '' }];
        }
        activeMedia = media;
        activeMediaIndex = 0;
        activeProductName = card.dataset.productName ?? '';
        showMedia(activeMedia[0], activeProductName);
        if (!(thumbnails instanceof HTMLElement)) return;
        thumbnails.replaceChildren();
        thumbnails.hidden = media.length < 2;
        mediaCount?.toggleAttribute('hidden', media.length < 2);
        media.forEach((item, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `product-modal-thumbnail${index === 0 ? ' is-active' : ''}`;
            button.setAttribute('aria-label', `${card.dataset.productName ?? ''} ${index + 1}`);
            button.addEventListener('click', () => {
                selectMedia(index);
            });
            thumbnails.append(button);
        });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-product-modal]');

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const card = trigger.closest('.product-card, [data-ai-chat-product-card]');
        if (!(card instanceof HTMLElement)) {
            return;
        }

        event.preventDefault();
        const available = card.dataset.productAvailable === '1';

        renderGallery(card);
        if (name) name.textContent = card.dataset.productName ?? '';
        if (category) category.textContent = card.dataset.productCategory ?? '';
        fullDescription = card.dataset.productDescription ?? '';
        if (description)
            description.textContent =
                fullDescription.length > descriptionLimit
                    ? `${fullDescription.slice(0, descriptionLimit).trimEnd()}…`
                    : fullDescription;
        if (descriptionToggle instanceof HTMLButtonElement) {
            descriptionToggle.hidden = fullDescription.length <= descriptionLimit;
            descriptionToggle.dataset.expanded = '0';
            descriptionToggle.textContent = quickViewElement.dataset.moreLabel ?? '';
        }
        if (price) price.textContent = card.dataset.productPrice ?? '';
        if (price instanceof HTMLElement) price.dataset.defaultPrice = card.dataset.productPrice ?? '';
        if (status) {
            status.textContent = available ? '' : (card.dataset.productStatus ?? '');
            status.hidden = available;
            status.classList.toggle('is-unavailable', !available);
        }
        if (tags instanceof HTMLElement) {
            let productTags = [];
            try { productTags = JSON.parse(card.dataset.productTags ?? '[]'); } catch { productTags = []; }
            tags.replaceChildren();
            tags.hidden = !available || productTags.length === 0;
            if (available) productTags.forEach((tag) => {
                const badge = document.createElement('span');
                badge.style.setProperty('--tag-color', tag.color || '#17633f');
                badge.style.setProperty('--tag-bg', tag.background || '#eaf7ef');
                if (tag.icon) {
                    const icon = document.createElement('i');
                    icon.className = tag.icon;
                    icon.setAttribute('aria-hidden', 'true');
                    badge.append(icon);
                }
                badge.append(document.createTextNode(tag.name || ''));
                tags.append(badge);
            });
        }
        if (productId instanceof HTMLInputElement) productId.value = card.dataset.productId ?? '';
        let variants = [];
        try { variants = JSON.parse(card.dataset.productVariants ?? '[]'); } catch { variants = []; }
        if (variantSelect instanceof HTMLSelectElement && variantWrap instanceof HTMLElement) {
            variantSelect.replaceChildren();
            variantWrap.hidden = variants.length === 0;
            variantSelect.required = variants.length > 0;
            if (variants.length > 0) {
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = variantSelect.dataset.variantPlaceholder ?? '';
                variantSelect.append(placeholder);
                variants.forEach((variant) => {
                    const option = document.createElement('option');
                    option.value = String(variant.id);
                    option.dataset.price = String(variant.price);
                    option.textContent = `${variant.name} · ${new Intl.NumberFormat('vi-VN').format(variant.price)}đ`;
                    variantSelect.append(option);
                });
            }
            refreshVariantDropdown(variantSelect);
        }
        if (quantity instanceof HTMLInputElement) quantity.value = '1';
        if (submit instanceof HTMLButtonElement) {
            submit.disabled = !available;
            submit.toggleAttribute('aria-disabled', !available);
        }
        if (detail instanceof HTMLAnchorElement) detail.href = card.dataset.productUrl ?? '#';

        quickView.show();
    });

    quickViewElement.addEventListener('hidden.bs.modal', () => {
        closeVariantDropdowns();
        if (video instanceof HTMLVideoElement) {
            video.pause();
            video.removeAttribute('src');
        }
    });

    mediaFrame?.addEventListener(
        'touchstart',
        (event) => {
            touchStartX = event.changedTouches[0]?.clientX ?? 0;
        },
        { passive: true },
    );
    mediaFrame?.addEventListener(
        'touchend',
        (event) => {
            const distance = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
            if (activeMedia.length > 1 && Math.abs(distance) >= 45)
                selectMedia(activeMediaIndex + (distance < 0 ? 1 : -1));
        },
        { passive: true },
    );
    mediaFrame?.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.target instanceof HTMLVideoElement) return;
        event.preventDefault();
        mouseStartX = event.clientX;
        draggingMedia = true;
        mediaFrame.setPointerCapture(event.pointerId);
        mediaFrame.classList.add('is-dragging');
    });
    mediaFrame?.addEventListener('pointermove', (event) => {
        if (!draggingMedia || !(image instanceof HTMLImageElement)) return;
        const distance = Math.max(-70, Math.min(70, event.clientX - mouseStartX));
        image.style.transform = `translateX(${distance}px)`;
    });
    mediaFrame?.addEventListener('pointerup', (event) => {
        if (!draggingMedia || event.pointerType !== 'mouse') return;
        const distance = event.clientX - mouseStartX;
        draggingMedia = false;
        mediaFrame.classList.remove('is-dragging');
        if (image instanceof HTMLImageElement) image.style.transform = '';
        if (mediaFrame.hasPointerCapture(event.pointerId)) mediaFrame.releasePointerCapture(event.pointerId);
        if (activeMedia.length > 1 && Math.abs(distance) >= 45) selectMedia(activeMediaIndex + (distance < 0 ? 1 : -1));
    });
    mediaFrame?.addEventListener('pointercancel', () => {
        draggingMedia = false;
        mediaFrame.classList.remove('is-dragging');
        if (image instanceof HTMLImageElement) image.style.transform = '';
    });
    mediaFrame?.addEventListener(
        'wheel',
        (event) => {
            if (
                activeMedia.length < 2 ||
                Math.abs(event.deltaX) <= Math.abs(event.deltaY) ||
                Math.abs(event.deltaX) < 20
            )
                return;
            event.preventDefault();
            selectMedia(activeMediaIndex + (event.deltaX > 0 ? 1 : -1));
        },
        { passive: false },
    );
    quickViewElement.addEventListener('keydown', (event) => {
        if (activeMedia.length < 2) return;
        if (event.key === 'ArrowLeft') selectMedia(activeMediaIndex - 1);
        if (event.key === 'ArrowRight') selectMedia(activeMediaIndex + 1);
    });

    const syncModalColumns = () => {
        if (!(mediaFrame instanceof HTMLElement) || !(content instanceof HTMLElement)) return;
        content.style.maxHeight = window.matchMedia('(min-width: 768px)').matches
            ? `${mediaFrame.getBoundingClientRect().height}px`
            : '';
    };
    const mediaResizeObserver = new ResizeObserver(syncModalColumns);
    if (mediaFrame instanceof HTMLElement) mediaResizeObserver.observe(mediaFrame);
    window.addEventListener('resize', syncModalColumns);
    descriptionToggle?.addEventListener('click', () => {
        if (!(description instanceof HTMLElement) || !(descriptionToggle instanceof HTMLButtonElement)) return;
        const expanded = descriptionToggle.dataset.expanded === '1';
        description.textContent = expanded
            ? `${fullDescription.slice(0, descriptionLimit).trimEnd()}…`
            : fullDescription;
        descriptionToggle.dataset.expanded = expanded ? '0' : '1';
        descriptionToggle.textContent = expanded
            ? (quickViewElement.dataset.moreLabel ?? '')
            : (quickViewElement.dataset.lessLabel ?? '');
    });

    quickViewElement.querySelector('[data-quantity-minus]')?.addEventListener('click', () => {
        if (quantity instanceof HTMLInputElement) quantity.value = String(Math.max(1, Number(quantity.value || 1) - 1));
    });
    quickViewElement.querySelector('[data-quantity-plus]')?.addEventListener('click', () => {
        if (quantity instanceof HTMLInputElement)
            quantity.value = String(Math.min(1000, Number(quantity.value || 1) + 1));
    });
    variantSelect?.addEventListener('change', () => {
        if (!(price instanceof HTMLElement) || !(variantSelect instanceof HTMLSelectElement)) return;
        price.textContent = selectedVariantPrice(variantSelect) ?? price.dataset.defaultPrice ?? '';
    });
}

document.querySelectorAll('[data-detail-gallery]').forEach((gallery) => {
    const image = gallery.querySelector('[data-detail-image]');
    const video = gallery.querySelector('[data-detail-video]');
    const stage = gallery.querySelector('.product-detail-stage');
    const count = gallery.querySelector('[data-detail-count]');
    const mediaButtons = [...gallery.querySelectorAll('[data-detail-media]')];
    let activeIndex = 0;
    let startX = 0;

    const select = (index) => {
        if (mediaButtons.length === 0) return;
        activeIndex = (index + mediaButtons.length) % mediaButtons.length;
        const button = mediaButtons[activeIndex];
        const isVideo = button.dataset.mediaType === 'video';
        if (image instanceof HTMLImageElement) {
            image.hidden = isVideo;
            image.src = isVideo ? '' : (button.dataset.mediaUrl ?? '');
        }
        if (video instanceof HTMLVideoElement) {
            video.pause();
            video.hidden = !isVideo;
            video.src = isVideo ? (button.dataset.mediaUrl ?? '') : '';
        }
        mediaButtons.forEach((item, itemIndex) => item.classList.toggle('is-active', itemIndex === activeIndex));
        if (count) count.textContent = `${activeIndex + 1}/${mediaButtons.length}`;
    };

    mediaButtons.forEach((button, index) => button.addEventListener('click', () => select(index)));
    stage?.addEventListener('pointerdown', (event) => {
        if (event.target instanceof HTMLVideoElement) return;
        startX = event.clientX;
        stage.setPointerCapture(event.pointerId);
    });
    stage?.addEventListener('pointerup', (event) => {
        const distance = event.clientX - startX;
        if (mediaButtons.length > 1 && Math.abs(distance) >= 45) select(activeIndex + (distance < 0 ? 1 : -1));
    });
});

document.querySelector('[data-detail-quantity-minus]')?.addEventListener('click', () => {
    const quantity = document.querySelector('.product-detail-order input[name="quantity"]');
    if (quantity instanceof HTMLInputElement) quantity.value = String(Math.max(1, Number(quantity.value || 1) - 1));
});
document.querySelector('[data-detail-quantity-plus]')?.addEventListener('click', () => {
    const quantity = document.querySelector('.product-detail-order input[name="quantity"]');
    if (quantity instanceof HTMLInputElement) quantity.value = String(Math.min(1000, Number(quantity.value || 1) + 1));
});

const miniCart = document.querySelector('[data-mini-cart]');
if (miniCart instanceof HTMLElement) {
    const toggle = miniCart.querySelector('[data-mini-cart-toggle]');
    const panel = miniCart.querySelector('[data-mini-cart-panel]');
    const body = miniCart.querySelector('[data-mini-cart-body]');
    const floatingCart = document.querySelector('[data-mini-cart-floating]');
    const mobileCart = document.querySelector('[data-mini-cart-mobile]');
    const backdrop = document.querySelector('[data-mini-cart-backdrop]');
    const panelTitle = panel?.querySelector('h2');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    let loaded = false;
    let openMode = null;
    const panelAnchor = document.createComment('mini-cart-panel-anchor');
    if (panel instanceof HTMLElement) panel.after(panelAnchor);

    const money = (value) =>
        new Intl.NumberFormat(document.documentElement.lang || 'vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));

    const updateCount = (count) => {
        document.querySelectorAll('[data-cart-count], .cart-count').forEach((element) => {
            element.textContent = String(count);
        });
        if (floatingCart instanceof HTMLButtonElement) floatingCart.hidden = Number(count) < 1;
    };

    const animateCart = () => {
        [floatingCart].forEach((element) => {
            if (!(element instanceof HTMLElement)) return;
            element.classList.remove('is-cart-bump');
            void element.offsetWidth;
            element.classList.add('is-cart-bump');
            window.setTimeout(() => element.classList.remove('is-cart-bump'), 750);
        });
    };

    const render = (payload) => {
        if (!(body instanceof HTMLElement)) return;
        body.replaceChildren();
        updateCount(payload.cart_count ?? 0);
        document.querySelectorAll('[data-mini-cart-subtotal]').forEach((element) => {
            element.textContent = money(payload.subtotal);
        });
        loaded = true;

        if (!Array.isArray(payload.items) || payload.items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'mini-cart-empty';
            empty.textContent = miniCart.dataset.emptyLabel ?? '';
            body.append(empty);
            return;
        }

        const list = document.createElement('ul');
        list.className = 'mini-cart-list';
        payload.items.forEach((item) => {
            const row = document.createElement('li');
            row.className = 'mini-cart-item';
            row.dataset.productId = String(item.product_id);
            row.dataset.lineKey = String(item.line_key ?? item.product_id);
            row.dataset.note = item.note ?? '';

            if (item.image_url) {
                const image = document.createElement('img');
                image.src = item.image_url;
                image.alt = '';
                image.loading = 'lazy';
                row.append(image);
            } else {
                const fallback = document.createElement('span');
                fallback.className = 'mini-cart-image-fallback';
                fallback.textContent = '◈';
                fallback.setAttribute('aria-hidden', 'true');
                row.append(fallback);
            }

            const copy = document.createElement('div');
            copy.className = 'mini-cart-item-copy';
            const name = document.createElement('strong');
            name.textContent = item.name;
            if (item.variant_name) name.textContent += ` · ${item.variant_name}`;
            const unitPrice = document.createElement('span');
            unitPrice.textContent = item.available ? money(item.price) : '—';
            const controls = document.createElement('div');
            controls.className = 'mini-cart-controls';
            const minus = document.createElement('button');
            minus.type = 'button';
            minus.dataset.miniCartMinus = '';
            minus.setAttribute('aria-label', 'Giảm số lượng');
            minus.textContent = '−';
            minus.disabled = Number(item.quantity) <= 1;
            const quantity = document.createElement('output');
            quantity.textContent = String(item.quantity);
            const plus = document.createElement('button');
            plus.type = 'button';
            plus.dataset.miniCartPlus = '';
            plus.setAttribute('aria-label', 'Tăng số lượng');
            plus.textContent = '+';
            plus.disabled = Number(item.quantity) >= 1000;
            controls.append(minus, quantity, plus);
            copy.append(name, unitPrice);

            const end = document.createElement('div');
            end.className = 'mini-cart-line-end';
            const lineTotal = document.createElement('strong');
            lineTotal.textContent = item.line_total === null ? '—' : money(item.line_total);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'mini-cart-remove';
            remove.dataset.miniCartRemove = '';
            remove.textContent = miniCart.dataset.removeLabel ?? '';
            end.append(lineTotal, remove);
            row.append(copy, controls, end);
            list.append(row);
        });

        const footer = document.createElement('div');
        footer.className = 'mini-cart-footer';
        const subtotal = document.createElement('div');
        subtotal.className = 'mini-cart-subtotal';
        const subtotalLabel = document.createElement('span');
        subtotalLabel.textContent = miniCart.dataset.subtotalLabel ?? '';
        const subtotalValue = document.createElement('strong');
        subtotalValue.textContent = money(payload.subtotal);
        subtotal.append(subtotalLabel, subtotalValue);
        const checkout = document.createElement('a');
        checkout.className = 'mini-cart-checkout';
        checkout.href = miniCart.dataset.cartUrl ?? '/cart';
        checkout.textContent = miniCart.dataset.checkoutLabel ?? '';
        footer.append(subtotal, checkout);
        body.append(list, footer);
    };

    document.addEventListener('customer:cart-updated', (event) => {
        const payload = event.detail?.mini_cart ?? event.detail;
        if (payload && typeof payload === 'object') {
            render(payload);
            animateCart();
        }
    });

    const showError = (message) => {
        if (!(body instanceof HTMLElement)) return;
        const error = document.createElement('p');
        error.className = 'mini-cart-error';
        error.textContent = message || 'Không thể cập nhật giỏ món.';
        body.replaceChildren(error);
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers ?? {}) },
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(
                payload.message || Object.values(payload.errors ?? {})[0]?.[0] || 'Không thể cập nhật giỏ món.',
            );
        }
        return payload;
    };

    const load = async () => {
        try {
            render(await request(miniCart.dataset.endpoint ?? '/cart/mini'));
        } catch (error) {
            showError(error.message);
        }
    };

    const restorePanel = () => {
        if (!(panel instanceof HTMLElement) || !panelAnchor.parentNode || panel.parentNode === panelAnchor.parentNode)
            return;
        panelAnchor.parentNode.insertBefore(panel, panelAnchor);
    };

    const setOpen = async (open, mode = 'header') => {
        if (!(panel instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) return;
        if (!open) {
            panel.hidden = true;
            panel.classList.remove('mini-cart-panel--floating');
            if (panelTitle instanceof HTMLElement) panelTitle.textContent = miniCart.dataset.headerTitle ?? '';
            panel.removeAttribute('role');
            panel.removeAttribute('aria-modal');
            if (backdrop instanceof HTMLElement) backdrop.hidden = true;
            document.body.classList.remove('mini-cart-modal-open');
            restorePanel();
            toggle.setAttribute('aria-expanded', 'false');
            mobileCart?.setAttribute('aria-expanded', 'false');
            miniCart.classList.remove('is-open');
            openMode = null;
            return;
        }

        openMode = mode;
        if (mode === 'floating') {
            document.body.append(panel);
            panel.classList.add('mini-cart-panel--floating');
            if (panelTitle instanceof HTMLElement) panelTitle.textContent = miniCart.dataset.floatingTitle ?? '';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            if (backdrop instanceof HTMLElement) backdrop.hidden = false;
            document.body.classList.add('mini-cart-modal-open');
            toggle.setAttribute('aria-expanded', 'false');
            mobileCart?.setAttribute('aria-expanded', 'true');
        } else {
            restorePanel();
            panel.classList.remove('mini-cart-panel--floating');
            if (panelTitle instanceof HTMLElement) panelTitle.textContent = miniCart.dataset.headerTitle ?? '';
            panel.removeAttribute('role');
            panel.removeAttribute('aria-modal');
            if (backdrop instanceof HTMLElement) backdrop.hidden = true;
            document.body.classList.remove('mini-cart-modal-open');
            toggle.setAttribute('aria-expanded', 'true');
            mobileCart?.setAttribute('aria-expanded', 'false');
        }
        panel.hidden = false;
        miniCart.classList.add('is-open');
        if (open && !loaded) await load();
    };

    toggle?.addEventListener('click', () =>
        setOpen(panel instanceof HTMLElement && (panel.hidden || openMode !== 'header'), 'header'),
    );
    floatingCart?.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(true, 'floating');
    });
    mobileCart?.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel instanceof HTMLElement && (panel.hidden || openMode !== 'floating'), 'floating');
    });
    miniCart.querySelector('[data-mini-cart-close]')?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));
    document.addEventListener('click', (event) => {
        if (
            !miniCart.contains(event.target) &&
            !panel?.contains(event.target) &&
            !floatingCart?.contains(event.target) &&
            !mobileCart?.contains(event.target) &&
            panel instanceof HTMLElement &&
            !panel.hidden
        )
            setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });

    body?.addEventListener('click', async (event) => {
        const action = event.target.closest('[data-mini-cart-minus], [data-mini-cart-plus], [data-mini-cart-remove]');
        const row = action?.closest('.mini-cart-item');
        if (!(action instanceof HTMLButtonElement) || !(row instanceof HTMLElement)) return;
        const lineKey = row.dataset.lineKey ?? row.dataset.productId;
        const quantity = Number(row.querySelector('output')?.textContent ?? 1);
        const removing = action.hasAttribute('data-mini-cart-remove');
        const nextQuantity = action.hasAttribute('data-mini-cart-minus') ? quantity - 1 : quantity + 1;
        row.classList.add('is-busy');
        try {
            const form = new FormData();
            form.set('_token', csrf);
            form.set('_method', removing ? 'DELETE' : 'PATCH');
            if (!removing) {
                form.set('quantity', String(nextQuantity));
                form.set('note', row.dataset.note ?? '');
            }
            const payload = await request(`${miniCart.dataset.itemsUrl ?? '/cart/items'}/${encodeURIComponent(lineKey)}`, {
                method: 'POST',
                body: form,
            });
            render(payload.mini_cart ?? payload);
        } catch (error) {
            row.classList.remove('is-busy');
            showError(error.message);
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-add-to-cart]')) return;
        event.preventDefault();
        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
        buttons.forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
        try {
            const payload = await request(form.action, { method: 'POST', body: new FormData(form) });
            render(payload);
            const modalElement = form.closest('.modal');
            if (modalElement instanceof HTMLElement) Modal.getInstance(modalElement)?.hide();
            animateCart();
        } catch (error) {
            showError(error.message);
            await setOpen(true);
        } finally {
            buttons.forEach((button) => {
                button.disabled = false;
                button.removeAttribute('aria-disabled');
            });
        }
    });

    if (floatingCart instanceof HTMLButtonElement && !floatingCart.hidden) load();
}

if (document.querySelector('[data-ai-chat-widget]')) {
    import('./ai-chat.js');
}

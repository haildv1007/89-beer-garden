<div class="walkin-customer-picker" data-walkin-customer-picker data-search-url="{{ route($tableRoutePrefix . '.tables.customers.search') }}">
    <label class="form-label" for="walkin-customer-search">{{ __('dining_session.fields.customer') }}</label>
    <input type="hidden" name="customer_id" value="{{ old('customer_id') }}" data-customer-id>
    <div class="walkin-customer-selected" data-customer-selected>
        <span data-customer-name>{{ old('customer_id') ? 'Khách đã chọn' : __('dining_session.anonymous') }}</span>
        <button type="button" data-customer-clear @if(!old('customer_id')) hidden @endif aria-label="Bỏ chọn khách">×</button>
    </div>
    <input class="form-control" id="walkin-customer-search" type="search" placeholder="Tìm tên hoặc số điện thoại khách..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="walkin-customer-results" data-customer-search>
    <div class="walkin-customer-results" id="walkin-customer-results" role="listbox" data-customer-results hidden></div>
</div>
<script>
(() => {
    const picker = document.querySelector('[data-walkin-customer-picker]');
    if (!picker) return;
    const search = picker.querySelector('[data-customer-search]');
    const results = picker.querySelector('[data-customer-results]');
    const id = picker.querySelector('[data-customer-id]');
    const name = picker.querySelector('[data-customer-name]');
    const clear = picker.querySelector('[data-customer-clear]');
    let timer;
    let controller;
    let activeIndex = -1;
    const close = () => { results.hidden = true; search.setAttribute('aria-expanded', 'false'); activeIndex = -1; };
    const select = customer => {
        id.value = customer.id;
        name.textContent = customer.name + (customer.phone ? ' - ' + customer.phone : '');
        clear.hidden = false;
        search.value = '';
        close();
    };
    clear.addEventListener('click', () => {
        id.value = '';
        name.textContent = @json(__('dining_session.anonymous'));
        clear.hidden = true;
        search.focus();
    });
    search.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        const query = search.value.trim();
        if (query.length < 2) { close(); return; }
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const response = await fetch(picker.dataset.searchUrl + '?q=' + encodeURIComponent(query), {
                    headers: { Accept: 'application/json' }, signal: controller.signal,
                });
                if (!response.ok) throw new Error('Không tìm được khách hàng.');
                const data = await response.json();
                results.replaceChildren();
                if (!data.customers.length) {
                    const empty = document.createElement('div');
                    empty.className = 'walkin-customer-empty';
                    empty.textContent = 'Không tìm thấy khách phù hợp';
                    results.append(empty);
                }
                data.customers.forEach(customer => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.setAttribute('role', 'option');
                    option.textContent = customer.name + (customer.phone ? ' - ' + customer.phone : '');
                    option.addEventListener('click', () => select(customer));
                    results.append(option);
                });
                results.hidden = false;
                search.setAttribute('aria-expanded', 'true');
            } catch (error) {
                if (error.name !== 'AbortError') close();
            }
        }, 250);
    });
    search.addEventListener('keydown', event => {
        const options = [...results.querySelectorAll('[role="option"]')];
        if (event.key === 'Escape') { close(); return; }
        if (results.hidden || !options.length) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = (activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
            options.forEach((option, index) => option.classList.toggle('is-active', index === activeIndex));
        }
        if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); options[activeIndex].click(); }
    });
    document.addEventListener('pointerdown', event => { if (!picker.contains(event.target)) close(); });
})();
</script>

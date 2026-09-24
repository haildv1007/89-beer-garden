@props(['code'])
<span class="display-code" title="{{ $code }}"
    aria-label="{{ $code }}">{{ \App\Support\DisplayCode::short($code) }}</span>

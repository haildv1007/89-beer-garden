@extends('layouts.customer')
@section('title', __('customer_ui.contact_title') . ' - ' . __('app.name'))
@section('meta_description', __('customer_ui.contact_intro'))
@section('content')
    @php
        $phoneHref = isset($contact['phone'])
            ? preg_replace('/[^0-9+]/', '', $contact['phone'])
            : null;
        $hasContact = collect($contact)->filter(fn ($value) => is_string($value) && $value !== '')->isNotEmpty();
    @endphp
    <div class="contact-page">
        <header class="contact-hero">
            <div class="contact-hero-copy">
                <span class="contact-eyebrow">{{ mb_strtoupper($configuredSiteName) }}</span>
                <h1>{{ __('customer_ui.contact_title') }}</h1>
                <p>{{ __('customer_ui.contact_intro') }}</p>
                <div class="contact-hero-actions">
                    @if ($contact['phone'])
                        <a class="btn btn-reservation" href="tel:{{ $phoneHref }}">{{ __('customer_ui.contact_phone') }}</a>
                    @endif
                    @if ($contact['map_url'])
                        <a class="contact-directions-link" href="{{ $contact['map_url'] }}" target="_blank"
                            rel="noopener noreferrer">{{ __('customer_ui.contact_directions') }} <span
                                aria-hidden="true">↗</span></a>
                    @endif
                </div>
            </div>
            <figure><img src="{{ asset('images/brand/atmosphere-evening.jpg') }}"
                    alt="{{ __('customer_ui.contact_image_alt') }}"></figure>
        </header>

        @if ($hasContact)
            <section class="contact-grid" aria-label="{{ __('customer_ui.contact_details') }}">
                @if ($contact['address'])
                    <article class="contact-card contact-card--address">
                        <span class="contact-card-icon" aria-hidden="true">⌖</span>
                        <div class="contact-card-copy">
                            <span>{{ __('customer_ui.contact_address') }}</span>
                            <h2>{{ $contact['address'] }}</h2>
                            @if ($contact['map_url'])
                                <a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener noreferrer">
                                    {{ __('customer_ui.contact_directions') }} <span aria-hidden="true">↗</span>
                                </a>
                            @endif
                        </div>
                    </article>
                @endif
                @if ($contact['phone'])
                    <article class="contact-card">
                        <span class="contact-card-icon" aria-hidden="true">☎</span>
                        <div class="contact-card-copy">
                            <span>{{ __('customer_ui.contact_phone') }}</span>
                            <h2><a href="tel:{{ $phoneHref }}">{{ $contact['phone'] }}</a></h2>
                            <p>{{ __('customer_ui.contact_phone_copy') }}</p>
                        </div>
                    </article>
                @endif
                @if ($contact['email'])
                    <article class="contact-card">
                        <span class="contact-card-icon" aria-hidden="true">✉</span>
                        <div class="contact-card-copy">
                            <span>{{ __('customer_ui.contact_email') }}</span>
                            <h2><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></h2>
                            <p>{{ __('customer_ui.contact_email_copy') }}</p>
                        </div>
                    </article>
                @endif
                @if ($contact['opening_hours'])
                    <article class="contact-card">
                        <span class="contact-card-icon" aria-hidden="true">◷</span>
                        <div class="contact-card-copy">
                            <span>{{ __('customer_ui.contact_opening_hours') }}</span>
                            <p class="contact-hours">{!! nl2br(e($contact['opening_hours'])) !!}</p>
                        </div>
                    </article>
                @endif
            </section>
        @else
            <section class="contact-empty">
                <h2>{{ __('customer_ui.contact_pending_title') }}</h2>
                <p>{{ __('customer_ui.contact_pending_copy') }}</p>
            </section>
        @endif

        <section class="contact-cta">
            <div>
                <span>{{ __('customer_ui.footer_reservation') }}</span>
                <h2>{{ __('customer_ui.contact_reservation_title') }}</h2>
            </div>
            <a class="btn btn-reservation" href="{{ route('customer.reservations.create') }}">
                {{ __('reservation.customer.make') }}
            </a>
        </section>
    </div>
@endsection

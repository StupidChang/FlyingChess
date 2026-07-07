@extends('layouts.app')
@section('title', __('premium.result_title'))
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section" style="text-align:center;padding-top:60px">
    @if($order && $order->isPaid())
        <h1 style="color:#5fd080;margin-bottom:16px">{{ __('premium.result_success_h1') }}</h1>
        <p style="margin-bottom:8px">{{ __('premium.result_order_no', ['no' => $order->order_no]) }}</p>
        <p style="color:var(--text-dim);margin-bottom:28px">{{ __('premium.result_success_body') }}</p>
        @if(env('GOOGLE_GA4_ID'))
        {{-- Load GA4 inline — layout skips GA4 for premium users, but we need payment_success --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_GA4_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ env('GOOGLE_GA4_ID') }}');
            gtag('event', 'payment_success', {
                order_no: '{{ $order->order_no }}',
                value: {{ $order->amount }},
                currency: 'TWD'
            });
        </script>
        @endif
    @elseif($order && $order->status === 'failed')
        <h1 style="color:#f06080;margin-bottom:16px">{{ __('premium.result_failed_h1') }}</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">{{ __('premium.result_failed_body') }}</p>
    @elseif($order && $order->isPending())
        <h1 style="color:var(--gold);margin-bottom:16px">{{ __('premium.result_pending_h1') }}</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">{{ __('premium.result_pending_body') }}</p>
    @else
        <h1 style="color:var(--text-dim);margin-bottom:16px">{{ __('premium.result_not_found_h1') }}</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">{{ __('premium.result_not_found_body') }}</p>
    @endif

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">{{ __('premium.back_to_premium') }}</a>
        <a href="{{ route('home') }}" class="btn btn-gold">{{ __('premium.back_to_home') }}</a>
    </div>
</div>
@endsection

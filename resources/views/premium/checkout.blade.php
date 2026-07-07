@extends('layouts.app')
@section('title', __('premium.checkout_title'))
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section" style="text-align:center;padding-top:60px">
    <h1 style="color:var(--gold);margin-bottom:20px">{{ __('premium.checkout_h1') }}</h1>
    <p style="color:var(--text-dim);margin-bottom:28px">{{ __('premium.checkout_no_redirect') }}</p>

    <form id="ecpay-form" action="{{ $actionUrl }}" method="POST">
        @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="btn btn-gold btn-xl">{{ __('premium.checkout_btn_ecpay') }}</button>
    </form>
</div>

<script>
    document.getElementById('ecpay-form').submit();
</script>
@endsection

@extends('layouts.app')
@section('title', __('legal.terms_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('legal.terms_meta_description'))
@section('canonical', route('legal.terms'))

@section('styles')
<style>
.legal-page{max-width:800px;margin:0 auto;padding:40px 16px}
.legal-page h1{color:var(--gold);margin-bottom:8px}
.legal-updated{color:var(--text-dim);font-size:.9rem;margin-bottom:24px}
.legal-intro{margin-bottom:28px;line-height:1.8}
.legal-toc{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:36px}
.legal-toc-title{font-size:1rem;color:var(--gold);margin-bottom:10px;font-weight:700}
.legal-toc ol{margin:0;padding-left:0;list-style:none}
.legal-toc li{margin:4px 0}
.legal-toc a{color:var(--text);font-size:.95rem;transition:color .15s}
.legal-toc a:hover{color:var(--accent)}
.legal-section{margin-bottom:36px;scroll-margin-top:80px}
.legal-section h2{font-size:1.2rem;margin-bottom:12px;color:var(--text);border-left:3px solid var(--accent);padding-left:10px}
.legal-section p{line-height:1.8}
.legal-section p + p{margin-top:8px}
.legal-list{margin:12px 0 0 20px;line-height:1.8;list-style:disc}
.legal-list li{margin-bottom:6px}
.legal-note{margin-top:12px;color:var(--text-dim);font-size:.95rem}
.legal-email{margin-top:8px;color:var(--text-dim)}
.legal-email a{color:var(--accent)}
</style>
@endsection

@section('content')
@php
    $toc = [
        'service'    => __('legal.terms.service_title'),
        'account'    => __('legal.terms.account_title'),
        'ugc'        => __('legal.terms.ugc_title'),
        'payment'    => __('legal.terms.payment_title'),
        'disclaimer' => __('legal.terms.disclaimer_title'),
        'age'        => __('legal.terms.age_title'),
        'changes'    => __('legal.terms.changes_title'),
        'law'        => __('legal.terms.law_title'),
        'contact'    => __('legal.terms.contact_title'),
    ];
@endphp
<div class="legal-page">
    <h1>{{ __('legal.terms_title') }}</h1>
    <p class="legal-updated">{{ __('legal.last_updated') }}</p>

    <p class="legal-intro">{{ __('legal.terms.intro') }}</p>

    <nav class="legal-toc" aria-label="{{ __('legal.toc_title') }}">
        <p class="legal-toc-title">{{ __('legal.toc_title') }}</p>
        <ol>
            @foreach($toc as $anchor => $label)
                <li><a href="#{{ $anchor }}">{{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>

    <section id="service" class="legal-section">
        <h2>{{ __('legal.terms.service_title') }}</h2>
        <p>{{ __('legal.terms.service_body') }}</p>
    </section>

    <section id="account" class="legal-section">
        <h2>{{ __('legal.terms.account_title') }}</h2>
        <ul class="legal-list">
            @foreach(__('legal.terms.account_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <section id="ugc" class="legal-section">
        <h2>{{ __('legal.terms.ugc_title') }}</h2>
        <p>{{ __('legal.terms.ugc_intro') }}</p>
        <ul class="legal-list">
            @foreach(__('legal.terms.ugc_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="legal-note">{{ __('legal.terms.ugc_note') }}</p>
    </section>

    <section id="payment" class="legal-section">
        <h2>{{ __('legal.terms.payment_title') }}</h2>
        <ul class="legal-list">
            @foreach(__('legal.terms.payment_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <section id="disclaimer" class="legal-section">
        <h2>{{ __('legal.terms.disclaimer_title') }}</h2>
        <ul class="legal-list">
            @foreach(__('legal.terms.disclaimer_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <section id="age" class="legal-section">
        <h2>{{ __('legal.terms.age_title') }}</h2>
        <p>{{ __('legal.terms.age_body') }}</p>
    </section>

    <section id="changes" class="legal-section">
        <h2>{{ __('legal.terms.changes_title') }}</h2>
        <p>{{ __('legal.terms.changes_body') }}</p>
    </section>

    <section id="law" class="legal-section">
        <h2>{{ __('legal.terms.law_title') }}</h2>
        <p>{{ __('legal.terms.law_body') }}</p>
    </section>

    <section id="contact" class="legal-section">
        <h2>{{ __('legal.terms.contact_title') }}</h2>
        <p>{{ __('legal.terms.contact_body') }}</p>
        <p class="legal-email"><a href="mailto:{{ __('legal.contact_email') }}">{{ __('legal.contact_email') }}</a></p>
    </section>
</div>
@endsection

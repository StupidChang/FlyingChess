@extends('layouts.app')
@section('title', __('legal.privacy_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('legal.privacy_meta_description'))
@section('canonical', route('legal.privacy'))

@section('styles')
<style>
.legal-page{max-width:800px;margin:0 auto;padding:40px 16px}
.legal-page h1{color:var(--gold);margin-bottom:8px}
.legal-updated{color:var(--text-dim);font-size:.9rem;margin-bottom:24px}
.legal-intro{margin-bottom:28px;line-height:1.8}
.legal-toc{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:36px}
.legal-toc-title{font-size:1rem;color:var(--gold);margin-bottom:10px;font-weight:700}
.legal-toc ol{margin:0;padding-left:0;counter-reset:toc;list-style:none}
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
.legal-footnote{font-size:.85rem;color:var(--text-dim);border-top:1px solid var(--border);padding-top:16px;line-height:1.7}
</style>
@endsection

@section('content')
@php
    $toc = [
        'collect'     => __('legal.privacy.collect_title'),
        'cookies'     => __('legal.privacy.cookies_title'),
        'third-party' => __('legal.privacy.third_title'),
        'retention'   => __('legal.privacy.retention_title'),
        'rights'      => __('legal.privacy.rights_title'),
        'adult'       => __('legal.privacy.adult_title'),
        'security'    => __('legal.privacy.security_title'),
        'changes'     => __('legal.privacy.changes_title'),
        'contact'     => __('legal.privacy.contact_title'),
    ];
@endphp
<div class="legal-page">
    <h1>{{ __('legal.privacy_title') }}</h1>
    <p class="legal-updated">{{ __('legal.last_updated') }}</p>

    <p class="legal-intro">{{ __('legal.privacy.intro') }}</p>

    <nav class="legal-toc" aria-label="{{ __('legal.toc_title') }}">
        <p class="legal-toc-title">{{ __('legal.toc_title') }}</p>
        <ol>
            @foreach($toc as $anchor => $label)
                <li><a href="#{{ $anchor }}">{{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>

    <section id="collect" class="legal-section">
        <h2>{{ __('legal.privacy.collect_title') }}</h2>
        <p>{{ __('legal.privacy.collect_intro') }}</p>
        <ul class="legal-list">
            @foreach(__('legal.privacy.collect_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <section id="cookies" class="legal-section">
        <h2>{{ __('legal.privacy.cookies_title') }}</h2>
        <p>{{ __('legal.privacy.cookies_body') }}</p>
    </section>

    <section id="third-party" class="legal-section">
        <h2>{{ __('legal.privacy.third_title') }}</h2>
        <p>{{ __('legal.privacy.third_intro') }}</p>
        <ul class="legal-list">
            @foreach(__('legal.privacy.third_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="legal-note">{{ __('legal.privacy.third_note') }}</p>
    </section>

    <section id="retention" class="legal-section">
        <h2>{{ __('legal.privacy.retention_title') }}</h2>
        <p>{{ __('legal.privacy.retention_body') }}</p>
    </section>

    <section id="rights" class="legal-section">
        <h2>{{ __('legal.privacy.rights_title') }}</h2>
        <p>{{ __('legal.privacy.rights_intro') }}</p>
        <ul class="legal-list">
            @foreach(__('legal.privacy.rights_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="legal-note">{{ __('legal.privacy.rights_note') }}</p>
    </section>

    <section id="adult" class="legal-section">
        <h2>{{ __('legal.privacy.adult_title') }}</h2>
        <p>{{ __('legal.privacy.adult_body') }}</p>
    </section>

    <section id="security" class="legal-section">
        <h2>{{ __('legal.privacy.security_title') }}</h2>
        <p>{{ __('legal.privacy.security_body') }}</p>
    </section>

    <section id="changes" class="legal-section">
        <h2>{{ __('legal.privacy.changes_title') }}</h2>
        <p>{{ __('legal.privacy.changes_body') }}</p>
    </section>

    <section id="contact" class="legal-section">
        <h2>{{ __('legal.privacy.contact_title') }}</h2>
        <p>{{ __('legal.privacy.contact_body') }}</p>
        <p class="legal-email"><a href="mailto:{{ __('legal.contact_email') }}">{{ __('legal.contact_email') }}</a></p>
    </section>
</div>
@endsection

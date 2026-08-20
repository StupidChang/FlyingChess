@extends('layouts.app')

@section('title', '定價管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:820px">
        <h1 style="margin-bottom:6px">定價管理</h1>
        <p style="color:var(--text-dim);font-size:.88rem;margin-bottom:24px">
            設定各方案在各幣別的價格,以及每個語系(地區)顯示哪一種幣別。留白的金額會沿用程式預設值。
        </p>

        @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:20px">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-error" style="margin-bottom:20px">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.pricing.update') }}">
            @csrf
            @method('PATCH')

            {{-- 金額:方案 × 幣別 --}}
            <h2 style="font-size:1rem;margin:0 0 12px">各方案價格</h2>
            <div style="overflow-x:auto;margin-bottom:32px">
                <table class="admin-price-table">
                    <thead>
                        <tr>
                            <th>方案</th>
                            @foreach($currencies as $code => $meta)
                            <th>{{ $code }} <span style="color:var(--text-dim)">{{ $meta['symbol'] }}</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $planKey => $plan)
                        <tr>
                            <td><strong>{{ $planKey }}</strong><br><span style="color:var(--text-dim);font-size:.76rem">{{ $plan['days'] }} 天</span></td>
                            @foreach($currencies as $code => $meta)
                            <td>
                                <input type="number" name="amounts[{{ $planKey }}][{{ $code }}]"
                                       value="{{ $plan['amounts'][$code] ?? '' }}"
                                       step="{{ ($meta['decimals'] ?? 0) > 0 ? '0.01' : '1' }}" min="0"
                                       inputmode="decimal" class="admin-price-input"
                                       aria-label="{{ $planKey }} {{ $code }}">
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 語系 → 幣別 --}}
            <h2 style="font-size:1rem;margin:0 0 6px">各語系顯示的幣別</h2>
            <p style="color:var(--text-dim);font-size:.8rem;margin:0 0 14px">
                「使用預設」= 顯示 {{ $defaultCurrency }}。⚠ 接上真的金流之前這只影響顯示;上線收款時,
                金流商必須能結算這裡選的幣別,否則請改回「使用預設」。
            </p>
            <div class="admin-locale-grid">
                @foreach($locales as $code => $meta)
                <label class="admin-locale-row">
                    <span>{{ $meta['native'] ?? $code }} <span style="color:var(--text-dim);font-size:.76rem">{{ $code }}</span></span>
                    <select name="locale_currency[{{ $code }}]" class="admin-price-input">
                        <option value="">使用預設 ({{ $defaultCurrency }})</option>
                        @foreach($currencies as $cur => $curMeta)
                        <option value="{{ $cur }}" @selected(($localeCurrency[$code] ?? '') === $cur)>{{ $cur }}（{{ $curMeta['symbol'] }}）</option>
                        @endforeach
                    </select>
                </label>
                @endforeach
            </div>

            <div style="margin-top:28px">
                <button type="submit" class="btn btn-primary">儲存定價</button>
            </div>
        </form>
    </div>
</section>

<style>
.admin-price-table{width:100%;border-collapse:collapse;font-size:.88rem}
.admin-price-table th,.admin-price-table td{padding:10px 12px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
.admin-price-table th{font-size:.76rem;color:var(--text-dim);font-weight:600}
.admin-price-input{width:100%;min-width:90px;padding:8px 10px;background:var(--bg);border:1px solid var(--border);
    border-radius:8px;color:var(--text);font-size:.88rem}
.admin-price-input:focus{outline:2px solid var(--accent);outline-offset:1px}
.admin-locale-grid{display:grid;grid-template-columns:1fr;gap:10px}
@media(min-width:560px){ .admin-locale-grid{grid-template-columns:1fr 1fr} }
.admin-locale-row{display:flex;flex-direction:column;gap:6px;font-size:.85rem}
</style>
@endsection

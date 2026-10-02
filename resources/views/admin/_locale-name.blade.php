{{-- 語系代碼 → 顯示名稱(繁體中文／English…)。沒有記錄的顯示「未記錄」。 --}}
@php $localeCfg = \App\Support\LocaleHelper::supported()[$locale ?? ''] ?? null; @endphp
@if($localeCfg){{ $localeCfg['name'] }}@else<span style="color:var(--text-dim)">未記錄</span>@endif

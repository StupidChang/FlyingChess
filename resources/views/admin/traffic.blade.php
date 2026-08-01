@extends('layouts.app')
@section('title', '流量分析 — 管理後台')
@section('robots', 'noindex,nofollow')
@section('content')

@include('admin._nav')

<section class="section section--sm">
<div class="container">
    <div class="tf-head">
        <div>
            <h1>流量分析</h1>
            <div class="admin-filter-tabs" style="margin-top:12px">
                {{-- 不勾就是四站合計。熱門頁面的 path 已經去掉語系前綴,
                     所以合計看到的是同一頁在四個語系的總和,不是只有中文站。 --}}
                @include('admin._filter-clear', ['params' => ['locale']])
                @foreach(['zh_TW' => '繁體中文', 'zh_CN' => '簡體中文', 'ja' => '日本語', 'en' => 'English'] as $k => $v)
                @include('admin._filter-tab', ['param' => 'locale', 'value' => $k, 'label' => $v])
                @endforeach
            </div>
            <p class="tf-sub">
                自己記的站內瀏覽,不經過 Google Analytics。不存 IP 與 UA 原文,
                訪客數是用「當天有效」的雜湊算的,隔天同一個人會被算成新訪客。
            </p>
        </div>
        <div class="tf-range">
            @foreach([1 => '今天', 7 => '7 天', 30 => '30 天', 90 => '90 天'] as $d => $label)
                <a href="{{ request()->fullUrlWithQuery(['days' => $d]) }}"
                   class="tf-range-btn {{ $days === $d ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if(! $oldestRecord)
        <div class="tf-empty">
            還沒有任何資料。統計是從這個功能上線後才開始記的,之前的流量沒有回溯。
            等一段時間再回來看。
        </div>
    @endif

    <div class="tf-kpis">
        <div class="tf-kpi"><span class="tf-kpi-n">{{ number_format($totalViews) }}</span><span class="tf-kpi-l">瀏覽數</span></div>
        <div class="tf-kpi"><span class="tf-kpi-n">{{ number_format($totalVisitors) }}</span><span class="tf-kpi-l">不重複訪客</span></div>
        <div class="tf-kpi">
            <span class="tf-kpi-n">{{ $totalViews ? round($loggedInViews / $totalViews * 100) : 0 }}%</span>
            <span class="tf-kpi-l">來自已登入</span>
        </div>
        <div class="tf-kpi">
            <span class="tf-kpi-n">{{ $totalVisitors ? round($totalViews / $totalVisitors, 1) : 0 }}</span>
            <span class="tf-kpi-l">人均頁數</span>
        </div>
    </div>

    {{-- 每日趨勢。用純 CSS 長條圖,不引入圖表函式庫 —— 後台多一份 JS 依賴
         不值得,而且這裡要看的只是「有沒有在長」。 --}}
    @php $peak = max(1, collect($trend)->max('views')); @endphp
    <section class="tf-card">
        <h2 class="tf-card-title">每日趨勢</h2>
        <div class="tf-chart">
            @foreach($trend as $row)
                <div class="tf-bar-col" title="{{ $row['date'] }}｜{{ $row['views'] }} 次瀏覽 / {{ $row['visitors'] }} 位訪客">
                    <div class="tf-bar" style="height:{{ max(2, round($row['views'] / $peak * 100)) }}%"></div>
                    <div class="tf-bar-x">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('m/d') }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="tf-card">
        <h2 class="tf-card-title">動線漏斗</h2>
        <p class="tf-note">每一階算的是不重複訪客。同一個人重整十次不會讓那一階變好看。</p>
        @php $funnelTop = max(1, $funnel[0]['value']); @endphp
        @foreach($funnel as $step)
            <div class="tf-funnel-row">
                <span class="tf-funnel-label">{{ $step['label'] }}</span>
                <div class="tf-funnel-track">
                    <div class="tf-funnel-fill" style="width:{{ round($step['value'] / $funnelTop * 100) }}%"></div>
                </div>
                <span class="tf-funnel-val">
                    {{ number_format($step['value']) }}
                    <em>{{ $funnelTop ? round($step['value'] / $funnelTop * 100) : 0 }}%</em>
                </span>
            </div>
        @endforeach
    </section>

    <div class="tf-cols">
        <section class="tf-card">
            <h2 class="tf-card-title">熱門頁面</h2>
            <table class="tf-table">
                <thead><tr><th>路徑</th><th>瀏覽</th><th>訪客</th></tr></thead>
                <tbody>
                @forelse($topPaths as $row)
                    <tr>
                        <td class="tf-path">{{ $row->path }}</td>
                        <td>{{ number_format($row->views) }}</td>
                        <td>{{ number_format($row->visitors) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="tf-none">尚無資料</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <div>
            <section class="tf-card">
                <h2 class="tf-card-title">外部來源</h2>
                <p class="tf-note">站內互連不計入,只留真的從外面連進來的網域。</p>
                <table class="tf-table">
                    <tbody>
                    @forelse($referrers as $row)
                        <tr><td class="tf-path">{{ $row->referer_host }}</td><td>{{ number_format($row->views) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="tf-none">尚無外部來源（直接輸入網址或書籤進來的不會有 referer）</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>

            <section class="tf-card">
                <h2 class="tf-card-title">語系分佈</h2>
                {{-- 這一張刻意不吃上面的語系篩選 —— 它的用途就是「四站各佔多少」,
                     跟著篩選走的話勾了日文就只剩一列,等於沒有用。 --}}
                @php $names = ['zh_TW' => '繁體中文', 'zh_CN' => '簡體中文', 'ja' => '日本語', 'en' => 'English']; @endphp
                <table class="tf-table">
                    <thead><tr><th>語系</th><th>瀏覽</th><th>訪客</th><th>佔比</th></tr></thead>
                    <tbody>
                    @forelse($locales as $row)
                        <tr>
                            <td class="tf-path">{{ $names[$row->locale] ?? ($row->locale ?: '未知') }}</td>
                            <td>{{ number_format($row->views) }}</td>
                            <td>{{ number_format($row->visitors) }}</td>
                            <td>{{ $localeTotal ? round($row->views / $localeTotal * 100) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="tf-none">尚無資料</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>
</section>
@endsection

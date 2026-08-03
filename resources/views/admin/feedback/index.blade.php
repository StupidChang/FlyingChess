@extends('layouts.app')

@section('title', '回報管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

@php
    use App\Http\Controllers\AdminController;
    use App\Models\Feedback;

    $statusLabels = AdminController::FEEDBACK_STATUS_LABELS;
    $statusColors = [
        Feedback::STATUS_NEW => 'var(--accent)',
        Feedback::STATUS_DOING => 'var(--gold)',
        Feedback::STATUS_DONE => '#34d399',
        Feedback::STATUS_SPAM => 'var(--text-dim)',
    ];
@endphp

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:8px">回報管理</h1>
        <p style="color:var(--text-dim);font-size:.85rem;margin-bottom:24px">
            未處理 {{ $counts[Feedback::STATUS_NEW] ?? 0 }}
            ・處理中 {{ $counts[Feedback::STATUS_DOING] ?? 0 }}
            ・已處理 {{ $counts[Feedback::STATUS_DONE] ?? 0 }}
            ・垃圾 {{ $counts[Feedback::STATUS_SPAM] ?? 0 }}
        </p>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                @include('admin._filter-clear', ['params' => ['type', 'status']])
                @foreach(Feedback::STATUSES as $s)
                    @include('admin._filter-tab', ['param' => 'status', 'value' => $s, 'label' => $statusLabels[$s]])
                @endforeach
                <span style="width:1px;background:var(--border);margin:0 4px"></span>
                @foreach(Feedback::TYPES as $t)
                    @include('admin._filter-tab', ['param' => 'type', 'value' => $t, 'label' => __('feedback.type_'.$t, [], 'zh_TW')])
                @endforeach
            </div>
            <form action="{{ route('admin.feedback') }}" method="GET" class="admin-search">
                @foreach(['type', 'status'] as $keep)
                    @foreach((array) request($keep, []) as $v)
                    <input type="hidden" name="{{ $keep }}[]" value="{{ $v }}">
                    @endforeach
                @endforeach
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋內容、聯絡方式或頁面…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        @include('admin._per-page', ['paginator' => $feedback, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'type', 'label' => '類型'])
                        <th style="min-width:280px">內容</th>
                        <th>聯絡方式</th>
                        <th>來源</th>
                        @include('admin._sort-header', ['key' => 'status', 'label' => '狀態'])
                        @include('admin._sort-header', ['key' => 'created_at', 'label' => '時間'])
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedback as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td style="white-space:nowrap">{{ __('feedback.type_'.$item->type, [], 'zh_TW') }}</td>
                        <td>
                            {{-- 短的直接看完,長的收在 details 裡。回報內容長度落差很大,
                                 一律截斷會看不到重點,一律全展開一頁只放得下三筆。 --}}
                            @if(mb_strlen($item->message) <= 140)
                                <div class="fb-msg">{{ $item->message }}</div>
                            @else
                                <details>
                                    <summary class="fb-msg-sum">{{ mb_substr($item->message, 0, 140) }}…</summary>
                                    <div class="fb-msg" style="margin-top:8px">{{ $item->message }}</div>
                                </details>
                            @endif
                        </td>
                        <td style="font-size:.82rem">
                            @if($item->contact)
                                <span class="fb-contact">{{ $item->contact }}</span>
                            @else
                                <span style="color:var(--text-dim)">—</span>
                            @endif
                            @if($item->user)
                                <div style="color:var(--text-dim);margin-top:3px">會員 #{{ $item->user->id }} {{ $item->user->name }}</div>
                            @endif
                        </td>
                        <td style="font-size:.78rem;color:var(--text-dim);max-width:190px">
                            @if($item->page_path)
                                <div style="word-break:break-all">{{ $item->page_path }}</div>
                            @endif
                            <div>{{ $item->locale }}</div>
                            @if($item->user_agent)
                                <details><summary style="cursor:pointer">UA</summary>
                                    <div style="word-break:break-all;margin-top:4px">{{ $item->user_agent }}</div>
                                </details>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <span style="color:{{ $statusColors[$item->status] ?? 'var(--text)' }};font-weight:600;font-size:.82rem">
                                {{ $statusLabels[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;font-size:.82rem">{{ $item->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @foreach(Feedback::STATUSES as $s)
                                    @continue($s === $item->status)
                                    <form action="{{ route('admin.feedback.status', $item) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $s }}">
                                        <button type="submit" class="btn btn-sm btn-outline">{{ $statusLabels[$s] }}</button>
                                    </form>
                                @endforeach
                                <form action="{{ route('admin.feedback.destroy', $item) }}" method="POST"
                                      onsubmit="return confirm('確定要刪除回報 #{{ $item->id }} 嗎？此操作無法復原。')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:24px">沒有找到回報</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $feedback])
    </div>
</section>

<style>
.fb-msg{white-space:pre-wrap;word-break:break-word;font-size:.85rem;line-height:1.7;max-width:460px}
.fb-msg-sum{cursor:pointer;font-size:.85rem;line-height:1.7;max-width:460px}
.fb-contact{word-break:break-all}
</style>
@endsection

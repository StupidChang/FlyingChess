@extends('layouts.app')

@section('title', __('notifications.page_title') . ' — ' . __('ui.site_name'))
@section('robots', 'noindex,nofollow')

@section('content')
<section class="section section--sm">
    <div class="container nt-page">
        <h1 class="nt-title">{{ __('notifications.page_title') }}</h1>

        @forelse($notifications as $n)
            @php
                $isNew = in_array($n->id, $unreadIds, true);
                // 系統通知(歡迎、推廣)依目前頁面的語系顯示,見 SiteMessage::present()
                $nv = \App\Notifications\SiteMessage::present($n);
                $link = $nv['url'];
            @endphp
            {{-- id 給右上角面板的錨點用(#n-…),點過來會捲到這一則並標亮 --}}
            <article id="n-{{ $n->id }}" @class(['nt-item', 'is-new' => $isNew])>
                <div class="nt-item-head">
                    <strong>{{ $nv['title'] }}</strong>
                    <time datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->diffForHumans() }}</time>
                </div>
                {{-- 純文字輸出,換行照原樣保留(nl2br 吃的是跳脫過的字串) --}}
                <p>{!! nl2br(e($nv['body'])) !!}</p>
                @if($link)
                <a href="{{ route('notifications.open', $n->id) }}" class="btn btn-sm btn-outline nt-item-go">{{ __('notifications.open') }} →</a>
                @endif
            </article>
        @empty
            <div class="nav-notif-empty">
                @include('partials.icon', ['name' => 'bell', 'cls' => 'nav-notif-empty-ico'])
                <p>{{ __('ui.notifications_empty') }}</p>
            </div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</section>
@endsection

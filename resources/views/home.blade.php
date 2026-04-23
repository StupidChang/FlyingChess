@extends('layouts.app')
@section('title', '情侶飛行棋 — 免費線上情趣小遊戲')
@section('meta_description', '情侶專屬飛行棋與真心話大冒險，免費在線玩！自訂棋盤、多種遊戲模式，情侶升溫、派對助興。')
@section('og_title', '情侶飛行棋 — 免費線上情趣小遊戲')
@section('og_description', '情侶專屬飛行棋與真心話大冒險，免費在線玩！自訂棋盤、多種互動遊戲，讓愛情更有趣。')
@section('canonical', route('home'))
@section('content')

{{-- ======================================================
     Hero
     ====================================================== --}}
<section class="hero-section">
    <div class="hero-inner">
        <span class="hero-eyebrow">線上雙人遊戲平台</span>
        <h1 class="hero-title">讓感情<span>更有趣</span></h1>
        <p class="hero-sub">飛行棋對戰、真心話大冒險、自訂棋盤任務，情侶升溫、派對助興，免費開始玩</p>
        <div class="hero-btns">
            <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-xl">立即玩飛行棋</a>
            <a href="{{ route('truth-dare.lobby') }}" class="btn btn-outline-gold btn-xl">真心話大冒險</a>
        </div>
        <div class="hero-trust">
            <span class="hero-trust-item">免費玩，無需下載</span>
            <span class="hero-trust-item">支援手機與電腦</span>
            <span class="hero-trust-item">雙人或多人同樂</span>
        </div>
    </div>
</section>

{{-- 廣告版位：Hero 下方 --}}
@include('partials.ad-unit', ['zone' => 'home_banner'])

{{-- ======================================================
     Game Modes
     ====================================================== --}}
<section class="game-cards-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">遊戲模式</span>
            <h2 class="section-title">選擇你的玩法</h2>
            <p class="section-desc" style="max-width:480px;margin-left:auto;margin-right:auto">五種遊戲模式，從輕鬆對戰到深度互動，總有一款適合你們</p>
        </div>
        <div class="game-cards-grid">
            {{-- 飛行棋 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>飛行棋</h3>
                <p>經典飛行棋對戰，2–4 人或 AI 對手，不用等朋友也能玩</p>
                <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-full">進入大廳</a>
            </article>

            {{-- 真心話大冒險 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>真心話大冒險</h3>
                <p>1–6 人同樂，情侶、派對題庫隨機抽牌，進階題庫等你解鎖</p>
                <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">開始玩</a>
            </article>

            {{-- 情侶撲克牌 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>情侶撲克牌</h3>
                <p>2-6 人抽牌配對，牌大的指揮、牌小的服從，越玩越刺激</p>
                <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">開始玩</a>
            </article>

            {{-- 自訂棋盤 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6ZM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>自訂棋盤</h3>
                <p>雙人同機在自訂棋盤上對玩，格子任務自己設計</p>
                @if($default)
                <a href="{{ route('play.board', $default) }}" class="btn btn-gold btn-full">開始遊玩</a>
                @else
                <a href="{{ route('play') }}" class="btn btn-gold btn-full">開始遊玩</a>
                @endif
            </article>

            {{-- 棋盤編輯器 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                    </svg>
                </div>
                <h3>棋盤編輯器</h3>
                <p>登入後免費建立棋盤，自訂每個格子的文字與類型</p>
                @auth
                <a href="{{ route('boards.index') }}" class="btn btn-gold btn-full">我的棋盤</a>
                @else
                <a href="{{ route('register') }}" class="btn btn-outline-gold btn-full">免費註冊開始建立</a>
                @endauth
            </article>
        </div>
    </div>
</section>

{{-- 廣告版位：遊戲卡片下方 --}}
@include('partials.ad-unit', ['zone' => 'home_mid'])

<hr class="section-divider">

{{-- ======================================================
     Board Templates
     ====================================================== --}}
<section class="boards-section container">
    <div class="section-head">
        <h2>棋盤模板</h2>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <form action="" method="GET" id="share-join-form" style="display:flex;gap:6px">
                <input type="text" id="share-code-input" name="code" class="form-control"
                       placeholder="分享碼" maxlength="10"
                       style="width:120px;text-transform:uppercase;padding:5px 10px;font-size:.82rem">
                <button type="submit" class="btn btn-sm btn-outline-gold">開啟</button>
            </form>
            <a href="{{ route('boards.templates') }}" class="btn btn-sm btn-outline-gold">查看全部</a>
        </div>
    </div>
    <div class="boards-grid">
        @forelse($presetBoards as $board)
        <article class="board-card">
            <div class="board-card-body">
                @php
                    $shape = 'cross';
                    if ($board->canvas_rows == 7 && $board->canvas_cols == 7) $shape = 'square';
                    elseif ($board->canvas_rows == 5 && $board->canvas_cols == 9) $shape = 'rect';
                @endphp
                <div class="board-mini-preview shape-{{ $shape }}">
                    @foreach($board->squares as $sq)
                        <div class="board-dot{{ $sq->position === 0 ? ' dot-start' : '' }}{{ $sq->position === $board->squares->count() - 1 ? ' dot-end' : '' }}"
                             style="grid-row:{{ $sq->grid_row }};grid-column:{{ $sq->grid_col }}"></div>
                    @endforeach
                </div>
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                    <span class="badge-squares">{{ $board->squares_count }} 格</span>
                    @if($board->is_default)<span class="badge-default">預設</span>@endif
                    @if($board->is_premium_template)<span class="badge-premium">Premium</span>@endif
                    @if($board->is_template && !$board->is_premium_template)<span class="badge-free">免費模板</span>@endif
                </div>
            </div>
            <div class="board-card-foot">
                @if($board->is_premium_template && (!auth()->check() || !auth()->user()->isPremium()))
                    <a href="{{ route('premium.index') }}" class="btn btn-sm btn-outline" title="Premium 專屬">升級解鎖</a>
                @else
                    <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">開始玩</a>
                @endif
            </div>
        </article>
        @empty
        <div class="empty-notice">尚無棋盤模板</div>
        @endforelse
    </div>
</section>

{{-- 廣告版位 --}}
@include('partials.ad-unit', ['zone' => 'home_mid'])

{{-- ======================================================
     My Boards (authenticated users)
     ====================================================== --}}
@auth
<section class="boards-section container" style="padding-top:0">
    <div class="section-head">
        <h2>我的棋盤</h2>
        <a href="{{ route('boards.create') }}" class="btn btn-sm btn-outline-gold">新建棋盤</a>
    </div>
    <div class="boards-grid">
        @forelse($myBoards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ $board->squares_count }} 格</span>
                <span class="share-code-badge" title="分享碼" data-code="{{ $board->share_code }}"
                      onclick="copyShareCode(this)" style="cursor:pointer">
                    {{ $board->share_code }}
                </span>
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">玩</a>
                <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">編輯</a>
                <form action="{{ route('boards.destroy', $board) }}" method="POST"
                      onsubmit="return confirm('確定刪除？')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">&times;</button>
                </form>
            </div>
        </article>
        @empty
        <div class="empty-notice">
            還沒有棋盤，
            <a href="{{ route('boards.create') }}" style="color:var(--gold)">立即建立一個</a>
        </div>
        @endforelse
    </div>
</section>
@endauth

<hr class="section-divider">

{{-- ======================================================
     Features
     ====================================================== --}}
<section class="features-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">為什麼選我們</span>
            <h2 class="section-title">為情侶打造，從頭到尾</h2>
            <p class="section-desc" style="max-width:440px;margin-left:auto;margin-right:auto">每個細節都是為了讓你們的相處更輕鬆、更有趣、更親密</p>
        </div>
        <div class="features-grid">
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>免費開始</h3>
                <p>無需下載、無需付費，飛行棋與真心話大冒險免費玩到飽</p>
            </div>
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>情侶升溫</h3>
                <p>為情侶設計的互動遊戲，支援男女差異路徑，讓你們越玩越親密</p>
            </div>
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                    </svg>
                </div>
                <h3>自訂玩法</h3>
                <p>棋盤格子全部可自訂，打造只屬於你們的專屬版本</p>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider">

{{-- ======================================================
     FAQ
     ====================================================== --}}
<section class="faq-section">
    <div class="faq-inner">
        <div class="text-center" style="margin-bottom:40px">
            <span class="section-label">常見問題</span>
            <h2 class="section-title">你可能想知道</h2>
        </div>
        <div class="faq-list">
            <details class="faq-item" open>
                <summary class="faq-question">情侶飛行棋是什麼遊戲？</summary>
                <div class="faq-answer">
                    <p>情侶飛行棋是一款專為情侶設計的線上棋盤遊戲平台，包含經典飛行棋對戰、真心話大冒險、以及可自訂格子任務的互動棋盤模式。免費玩基本遊戲，付費解鎖進階互動題庫。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">需要付費才能玩嗎？</summary>
                <div class="faq-answer">
                    <p>不需要。飛行棋對戰、真心話大冒險基本題庫、自訂棋盤遊玩、棋盤編輯器全部免費使用。Premium 會員（NT${{ config('premium.price') }}/月）可享免廣告、進階互動題庫、私人房間等功能。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">真心話大冒險怎麼玩？</summary>
                <div class="faq-answer">
                    <p>建立房間後邀請朋友加入（1–6人），遊戲開始後輪流選擇類別（真心話、大冒險、情侶題、派對題），系統隨機抽出題目，完成後按下一位繼續。房主為付費會員時，整間房可使用進階題庫。</p>
                </div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">如何自訂棋盤？</summary>
                <div class="faq-answer">
                    <p>免費註冊帳號後，進入棋盤編輯器自由設計每個格子的文字內容、類型與顏色。也可以從棋盤模板一鍵複製開始修改。編輯完成後透過分享碼與對方共用。</p>
                </div>
            </details>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
function copyShareCode(el) {
    var code = el.dataset.code;
    navigator.clipboard.writeText(code).then(function() {
        var orig = el.textContent;
        el.textContent = '已複製！';
        setTimeout(function() { el.textContent = orig; }, 1500);
    });
}

document.getElementById('share-join-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var code = document.getElementById('share-code-input').value.trim().toUpperCase();
    if (code.length < 4) return;
    window.location.href = '/play/share/' + code;
});
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "name": "情侶飛行棋",
  "url": "{{ url('/') }}",
  "description": "情侶專屬線上遊戲平台，飛行棋對戰、真心話大冒險、自訂棋盤，情侶升溫、派對助興",
  "sameAs": []
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "情侶飛行棋是什麼遊戲？",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "情侶飛行棋是一款專為情侶設計的線上棋盤遊戲平台，包含經典飛行棋對戰、真心話大冒險、以及可自訂格子任務的互動棋盤模式。"
      }
    },
    {
      "@@type": "Question",
      "name": "需要付費才能玩嗎？",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "不需要。飛行棋對戰、真心話大冒險基本題庫、自訂棋盤遊玩、棋盤編輯器全部免費使用。Premium 會員可享免廣告、進階互動題庫等功能。"
      }
    },
    {
      "@@type": "Question",
      "name": "真心話大冒險怎麼玩？",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "建立房間後邀請朋友加入（1-6人），遊戲開始後輪流選擇類別，系統隨機抽出題目，完成後按下一位繼續。"
      }
    },
    {
      "@@type": "Question",
      "name": "如何自訂棋盤？",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "免費註冊帳號後，進入棋盤編輯器自由設計每個格子的文字內容、類型與顏色。也可以從棋盤模板一鍵複製開始修改。"
      }
    }
  ]
}
</script>
@endsection

@extends('layouts.app')

@section('title', '飛行棋大廳 — 情侶飛行棋')
@section('meta_description', '情侶飛行棋線下單機遊戲，輸入名字即刻開始！經典 Ludo 飛行棋，支援 AI 對手，免費遊玩。')
@section('og_title', '飛行棋大廳 — 情侶飛行棋')
@section('og_description', '情侶飛行棋線下單機遊戲，輸入名字即刻開始！經典 Ludo 飛行棋，支援 AI 對手。')
@section('canonical', route('games.lobby'))

@section('styles')
<style>
.lobby-page{max-width:720px;margin:0 auto;padding:32px 16px;min-height:calc(100vh - 56px)}
.lobby-hero{text-align:center;margin-bottom:32px}
.lobby-hero h1{font-size:1.6rem;color:var(--gold);margin-bottom:8px}
.lobby-hero p{color:var(--text-dim);font-size:.9rem}

.quick-start{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:32px}
.quick-start h2{color:var(--gold);font-size:1.1rem;margin-bottom:16px;text-align:center}
.quick-start .form-row{display:flex;gap:12px;align-items:end;flex-wrap:wrap}
.quick-start .form-row .form-group{flex:1;min-width:140px;margin-bottom:0}
.quick-start .form-row .btn{white-space:nowrap;height:42px}

.game-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:32px}
.game-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center;transition:border-color .2s,transform .15s}
.game-card:hover{border-color:var(--gold);transform:translateY(-2px)}
.game-card-icon{font-size:2.5rem;margin-bottom:8px}
.game-card h3{color:var(--text);font-size:1rem;margin-bottom:4px}
.game-card p{color:var(--text-dim);font-size:.8rem;margin-bottom:12px;line-height:1.4}
.game-card .btn{width:100%}
</style>
@endsection

@section('content')
<div class="lobby-page">
    <div class="lobby-hero">
        <h1>🎲 飛行棋大廳</h1>
        <p>輸入名稱，選擇遊戲模式，立即開始！</p>
    </div>

    {{-- Quick Start: Classic Flying Chess --}}
    <div class="quick-start">
        <h2>經典飛行棋 — 快速開始</h2>
        <form action="{{ route('games.create') }}" method="POST">
            @csrf
            <input type="hidden" name="solo" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="player_name">你的名稱</label>
                    <input type="text" id="player_name" name="player_name" class="form-control"
                        placeholder="請輸入名稱" maxlength="20" required
                        value="{{ old('player_name', session('player_name', '')) }}">
                </div>
                <button type="submit" class="btn btn-gold btn-xl">
                    🎲 開始對戰 AI
                </button>
            </div>
        </form>
        @if($errors->any())
        <div style="color:#f87171;font-size:.85rem;margin-top:10px;text-align:center">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
        <p style="color:var(--text-dim);font-size:.8rem;margin-top:10px;text-align:center">
            單機模式，與 AI 對手對戰，不需等待其他玩家
        </p>
    </div>

    {{-- Other Games Grid --}}
    <h2 style="color:var(--text);font-size:1.1rem;margin-bottom:16px">更多遊戲</h2>
    <div class="game-grid">
        <div class="game-card">
            <div class="game-card-icon">🎯</div>
            <h3>自訂棋盤</h3>
            <p>使用自訂棋盤進行情侶遊戲，支援各種格子類型</p>
            <a href="{{ route('play') }}" class="btn btn-gold">進入遊戲</a>
        </div>

        <div class="game-card">
            <div class="game-card-icon">🃏</div>
            <h3>情侶撲克牌</h3>
            <p>2-6 人抽牌比大小配對，每回合執行親密任務</p>
            <a href="{{ route('card-game.show') }}" class="btn btn-gold">進入遊戲</a>
        </div>

        <div class="game-card">
            <div class="game-card-icon">💬</div>
            <h3>真心話大冒險</h3>
            <p>經典派對遊戲，輪流抽取真心話或大冒險挑戰</p>
            <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold">進入遊戲</a>
        </div>

        @auth
        <div class="game-card">
            <div class="game-card-icon">✏️</div>
            <h3>我的棋盤</h3>
            <p>建立和編輯專屬自訂棋盤，設計獨一無二的遊戲</p>
            <a href="{{ route('boards.index') }}" class="btn btn-outline">管理棋盤</a>
        </div>
        @endauth
    </div>
</div>

{{-- Lobby sidebar ad --}}
<div class="ad-sidebar-wrap" style="margin-top:24px">
    @include('partials.ad-unit', ['zone' => 'lobby_side'])
</div>
@endsection

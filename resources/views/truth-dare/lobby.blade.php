@extends('layouts.app')
@section('title', '真心話大冒險 — 線上多人派對遊戲')
@section('meta_description', '真心話大冒險線上版，1-6人同樂，情侶升溫、朋友聚會必玩！免費開始，付費解鎖進階題庫。')
@section('robots', 'noindex,nofollow')
@section('content')

<div class="lobby-section">
    <div class="lobby-header">
        <h1>真心話大冒險</h1>
        <button class="btn btn-gold" onclick="document.getElementById('create-modal').classList.add('open')">
            建立房間
        </button>
    </div>

    @include('partials.ad-unit', ['zone' => 'lobby_side'])

    @if($games->isEmpty())
        <div class="empty-notice" style="text-align:center;padding:40px">
            <p style="margin-bottom:16px">目前沒有等待中的房間</p>
            <button class="btn btn-gold" onclick="document.getElementById('create-modal').classList.add('open')">建立第一個房間</button>
        </div>
    @else
        <div class="room-list">
            @foreach($games as $game)
            <div class="room-card">
                <h3>真心話大冒險 #{{ $game->code }}</h3>
                <div class="room-card-info">
                    {{ $game->players_count }} / 6 人
                    @if($game->is_private) <span class="badge-premium">私人</span> @endif
                </div>
                <form action="{{ route('truth-dare.join', $game->code) }}" method="POST" style="display:flex;gap:8px">
                    @csrf
                    <input type="text" name="player_name" class="form-control" placeholder="你的暱稱" required maxlength="20"
                           value="{{ session('player_name', '') }}" style="flex:1">
                    <button type="submit" class="btn btn-gold">加入</button>
                </form>
            </div>
            @endforeach
        </div>
        <div style="margin-top:20px">{{ $games->links() }}</div>
    @endif
</div>

{{-- Create room modal --}}
<div class="modal" id="create-modal">
    <div class="modal-overlay" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="this.closest('.modal').classList.remove('open')">&times;</button>
        <h2>建立真心話大冒險房間</h2>
        <form action="{{ route('truth-dare.create') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="player_name">你的暱稱</label>
                <input type="text" name="player_name" id="player_name" class="form-control"
                       required maxlength="20" value="{{ session('player_name', '') }}">
            </div>
            @auth
                @if(auth()->user()->isPremium())
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_private" value="1">
                        私人房間（不顯示在大廳）
                    </label>
                </div>
                @endif
            @endauth
            <button type="submit" class="btn btn-gold btn-full">建立房間</button>
        </form>
    </div>
</div>

@if($errors->any())
<script>document.getElementById('create-modal').classList.add('open');</script>
@endif
@endsection

@extends('layouts.app')

@section('title', '飛行棋大廳 — 情侶飛行棋')
@section('meta_description', '加入或建立飛行棋房間，與伴侶即時對戰。免費遊玩，無需下載，支援 2 至 4 人房間。')
@section('og_title', '飛行棋大廳 — 情侶飛行棋')
@section('og_description', '加入或建立飛行棋房間，與伴侶即時對戰。免費遊玩，無需下載，支援 2 至 4 人房間。')
@section('canonical', route('games.lobby'))

@section('content')
<div class="page-header">
    <div class="container">
        <h1>遊戲大廳</h1>
        <p>選擇一個等待中的房間加入，或建立新房間</p>
    </div>
</div>

<div class="container">
    <div class="lobby-toolbar">
        <button class="btn btn-primary" onclick="document.getElementById('create-modal').classList.add('open')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
            </svg>
            建立新房間
        </button>
        <span class="rooms-count">共 {{ $games->total() }} 個等待中的房間</span>
    </div>

    @if($games->count() > 0)
    <div class="rooms-table-wrap">
        <table class="rooms-table" role="grid" aria-label="等待中的房間列表">
            <thead>
                <tr>
                    <th scope="col">房間代碼</th>
                    <th scope="col">玩家人數</th>
                    <th scope="col">最多人數</th>
                    <th scope="col">建立時間</th>
                    <th scope="col">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
                    <td><code class="room-code-badge">{{ $game->code }}</code></td>
                    <td>{{ $game->players_count }} 人</td>
                    <td>{{ $game->max_players }} 人</td>
                    <td>{{ $game->created_at->diffForHumans() }}</td>
                    <td>
                        <a href="{{ route('games.show', $game->code) }}" class="btn btn-sm btn-primary">加入</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $games->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;opacity:0.4">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1 0-1.59l.296-.297a1.125 1.125 0 0 0 0-1.59l-.296-.296a1.125 1.125 0 0 1 0-1.591l.296-.296c.256-.256.622-.374.98-.313l1.17.195c.323.054.654-.036.905-.244l1.33-1.108c.32-.267.46-.694.358-1.1a8.7 8.7 0 0 1-2.288-4.04Z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h2>目前沒有等待中的房間</h2>
        <p>成為第一個建立房間的人！</p>
        <button class="btn btn-primary" onclick="document.getElementById('create-modal').classList.add('open')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
            </svg>
            建立房間
        </button>
    </div>
    @endif
</div>

{{-- Create Room Modal --}}
<div id="create-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-overlay" onclick="document.getElementById('create-modal').classList.remove('open')"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('create-modal').classList.remove('open')" aria-label="關閉">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <h2 id="modal-title">建立新房間</h2>
        <form action="{{ route('games.create') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="player_name">你的名稱</label>
                <input type="text" id="player_name" name="player_name" class="form-control"
                    placeholder="請輸入名稱" maxlength="20" required
                    value="{{ old('player_name', session('player_name')) }}">
            </div>
            <div class="form-group">
                <label for="max_players">最多玩家人數</label>
                <select id="max_players" name="max_players" class="form-control">
                    <option value="2" selected>2 人</option>
                    <option value="3">3 人</option>
                    <option value="4">4 人</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full">建立房間</button>
        </form>
    </div>
</div>
@endsection

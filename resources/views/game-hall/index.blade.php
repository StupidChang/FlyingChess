@extends('layouts.app')
@section('title', '遊戲大廳 — 情侶飛行棋線上遊戲合集')
@section('meta_description', '情侶飛行棋遊戲大廳：飛行棋、真心話大冒險、情侶撲克牌、骰子挑戰、國王遊戲、命運轉盤，六種玩法免費暢玩。')
@section('og_title', '遊戲大廳 — 情侶飛行棋')
@section('og_description', '六款情侶互動遊戲，免費線上玩！飛行棋、真心話大冒險、撲克牌、骰子、國王遊戲、命運轉盤。')
@section('canonical', route('game-hall.index'))
@section('content')

<section class="game-cards-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">遊戲大廳</span>
            <h1 class="section-title">全部遊戲</h1>
            <p class="section-desc" style="max-width:520px;margin-left:auto;margin-right:auto">六款情侶互動遊戲，從線上對戰到同機派對，總有一款適合你們今晚的心情</p>
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
                <p>經典飛行棋對戰，2–4 人或 AI 對手，擲骰前進、互相捕捉</p>
                <span class="game-card-tag tag-online">多人線上</span>
                <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-full">開始遊戲</a>
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
                <span class="game-card-tag tag-online">多人線上</span>
                <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">開始遊戲</a>
            </article>

            {{-- 情侶撲克牌 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>情侶撲克牌</h3>
                <p>2–6 人抽牌配對，牌大的指揮、牌小的服從，越玩越刺激</p>
                <span class="game-card-tag tag-party">同機派對</span>
                <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">開始遊戲</a>
            </article>

            {{-- 骰子挑戰 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M3 3h18a1 1 0 011 1v16a1 1 0 01-1 1H3a1 1 0 01-1-1V4a1 1 0 011-1zm4 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm-5 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm-5 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                    </svg>
                </div>
                <h3>骰子挑戰</h3>
                <p>擲出命運骰子，隨機決定動作與對象，派對破冰神器</p>
                <span class="game-card-tag tag-party">同機派對</span>
                <a href="{{ route('dice-game.show') }}" class="btn btn-gold btn-full">開始遊戲</a>
            </article>

            {{-- 國王遊戲 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>國王遊戲</h3>
                <p>國王號令全場，抽到國王的人可以命令其他玩家執行任務</p>
                <span class="game-card-tag tag-party">同機派對</span>
                <a href="{{ route('king-game.show') }}" class="btn btn-gold btn-full">開始遊戲</a>
            </article>

            {{-- 命運轉盤 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 000 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388Zm15.408 3.882a.75.75 0 00-.163.577 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H3.74a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9A9 9 0 0020.694 14.33a.75.75 0 00-1.45-.388.75.75 0 00.919 0Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>命運轉盤</h3>
                <p>轉動命運之輪，隨機指定任務或懲罰，讓緣分來決定</p>
                <span class="game-card-tag tag-party">同機派對</span>
                <a href="{{ route('wheel-game.show') }}" class="btn btn-gold btn-full">開始遊戲</a>
            </article>


        </div>
    </div>
</section>

@endsection

{{-- 上方玩家列的一個座位。id(p{n}-panel / -name / -pos)是 board.js 在找的,不要改。 --}}
<div id="p{{ $n }}-panel" class="player-panel p{{ $n }}{{ $n === 1 ? ' active' : '' }}">
    <div class="pawn pawn-{{ $n }}" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
        </svg>
    </div>
    <div class="player-info">
        <span id="p{{ $n }}-name" class="pname">{{ match ($n) { 1 => __('play.player_1'), 2 => __('play.player_2'), default => __('play.player_name', ['n' => $n]) } }}</span>
        <span id="p{{ $n }}-pos" class="ppos">{{ __('play.start_point') }}</span>
    </div>
</div>

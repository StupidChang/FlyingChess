{{--
    遊玩紀錄的單一列。免費版的清單與付費版的時間軸都用這一支,兩邊的內容才不會
    因為只改了其中一邊而長得不一樣。

    參數:
      $entry   GamePlayer,已經 eager load 過 game 與 game.players
--}}
@php
    $game = $entry->game;
    $isTruthDare = $game->game_type === 'truth_or_dare';

    /* 同場的其他人。名字重複的只留一個 —— 同一個人中途離開再進來會留下兩列
       game_player,列兩次看起來像有兩個同名玩家。 */
    $others = $game->players
        ->reject(fn ($p) => $p->id === $entry->id)
        ->pluck('player_name')
        ->filter()
        ->unique()
        ->values();

    /* 玩了多久。沒有 finished_at 就是還沒打完 —— 不要拿「現在」去減開始時間
       充當時長,那會讓一場三個月前開著沒關的房顯示成玩了三個月。 */
    $minutes = $game->finished_at
        ? max(1, $game->created_at->diffInMinutes($game->finished_at))
        : null;
@endphp
<div class="history-row">
    <div class="history-main">
        <strong>{{ $isTruthDare ? __('games.truth_dare') : __('games.flying_chess') }}</strong>
        <span class="history-code">#{{ $game->code }}</span>
        @if($entry->is_host)
        <span class="badge-squares">{{ __('ui.history_as_host') }}</span>
        @endif

        <div class="history-players">
            @if($others->isNotEmpty())
                {{ __('ui.history_with', ['names' => $others->join('、')]) }}
            @else
                {{ __('ui.history_alone') }}
            @endif
        </div>
    </div>
    <div class="history-meta">
        <span class="history-time">
            {{ $entry->created_at->format('Y/m/d H:i') }}
            @if($minutes)
                <em class="history-duration">{{ __('ui.history_duration', ['minutes' => $minutes]) }}</em>
            @elseif($game->status !== 'finished')
                <em class="history-duration">{{ __('ui.history_ongoing') }}</em>
            @endif
        </span>
        @if($game->status !== 'finished')
        <a href="{{ $isTruthDare ? route('truth-dare.show', $game->code) : route('games.show', $game->code) }}"
           class="btn btn-sm btn-outline">{{ __('ui.play') }}</a>
        @endif
    </div>
</div>

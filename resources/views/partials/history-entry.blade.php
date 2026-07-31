{{--
    遊玩紀錄的單一列。免費版的清單與付費版的時間軸都用這一支,兩邊的內容才不會
    因為只改了其中一邊而長得不一樣。

    參數:
      $entry   GamePlayer,已經 eager load 過 game
--}}
@php
    $game = $entry->game;
    $isTruthDare = $game->game_type === 'truth_or_dare';
@endphp
<div class="history-row">
    <div class="history-main">
        <strong>{{ $isTruthDare ? __('games.truth_dare') : __('games.flying_chess') }}</strong>
        <span class="history-code">#{{ $game->code }}</span>
        @if($entry->is_host)
        <span class="badge-squares">{{ __('ui.history_as_host') }}</span>
        @endif
    </div>
    <div class="history-meta">
        <span class="history-time">{{ $entry->created_at->format('Y/m/d H:i') }}</span>
        @if($game->status !== 'finished')
        <a href="{{ $isTruthDare ? route('truth-dare.show', $game->code) : route('games.show', $game->code) }}"
           class="btn btn-sm btn-outline">{{ __('ui.play') }}</a>
        @endif
    </div>
</div>

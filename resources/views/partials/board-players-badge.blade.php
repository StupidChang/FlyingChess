{{-- 棋盤寫給誰玩。1男1女的題目寫「對方」;多男多女照大冒險多人場的寫法,
     不指定是誰(「在場的異性」「左邊的人」),人數多少都玩得起來。 --}}
@if($board->isGroupPlay())
    <span class="badge-players badge-players-group">{{ __('play.audience_group') }}</span>
@else
    <span class="badge-players badge-players-couple">{{ __('play.audience_couple') }}</span>
@endif

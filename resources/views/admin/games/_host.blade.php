{{--
    開這一場的人。

    註冊會員顯示帳號名並連到會員頁;沒登入的顯示他自己輸入的暱稱加一組訪客代號。
    代號是 session_id 加鹽雜湊後截短的 —— 同一個訪客開的幾場會是同一組代號,
    看得出是不是同一個人,但**不會**把還在生效的 session_id 印在畫面上。

    參數:
      $host  GamePlayer|null(舊資料可能整場都沒有 is_host)
      $game  Game(拿來顯示開場來源)
--}}
@if(! $host)
    <span style="color:var(--text-dim)">—</span>
@elseif($host->user)
    <a href="{{ route('admin.users.edit', $host->user) }}" class="gh-name">{{ $host->user->name }}</a>
    <span class="gh-tag gh-tag--member">會員</span>
    @if($host->player_name && $host->player_name !== $host->user->name)
        <span class="gh-alias">遊戲暱稱：{{ $host->player_name }}</span>
    @endif
@else
    <span class="gh-name">{{ $host->player_name ?: '未命名' }}</span>
    <span class="gh-tag gh-tag--guest">訪客</span>
    <span class="gh-alias"><code>{{ $host->guestCode() }}</code></span>
@endif

@if($game->origin_referer || $game->origin_locale)
    <span class="gh-alias">
        來源：{{ $game->origin_referer ?: '直接進入' }}@if($game->origin_locale) · {{ $game->origin_locale }}@endif
    </span>
@endif

{{--
    「看廣告解鎖付費內容」的提示條。

    放在小遊戲頁面的內容底部,是**常駐**而不是彈窗 —— 這個站是一群人圍著一台
    裝置玩的,遊戲進行中跳出任何東西,毀掉的是整場的氣氛而不只是一個人的體驗。
    所以提示一直在那裡讓人自己按,不主動打斷。

    三種狀態:
      付費會員      → 什麼都不顯示
      解鎖時效內    → 顯示剩餘時間
      其他          → 顯示提示與按鈕

    ⚠ 廣告素材的分級要注意:ExoClick 是成人聯播網,而這個版位會在一群人面前
    被打開。上線前請確認該 zone 的素材可以限定,或這個版位改用非成人聯播網。
--}}
@php
    use App\Support\PremiumAccess;
    $rwUser = auth()->user();
    $rwIsMember = $rwUser?->isPremium() ?? false;
    $rwLeft = PremiumAccess::rewardedSecondsLeft();
    $rwMinutes = PremiumAccess::rewardedMinutes();
@endphp

@unless($rwIsMember)
<div class="rw-bar" id="rw-bar" data-left="{{ $rwLeft }}">
    <div class="rw-text">
        <span class="rw-idle">{{ __('minigame.rewarded_hint', ['minutes' => $rwMinutes]) }}</span>
        <span class="rw-live">{{ __('minigame.rewarded_active', ['time' => '']) }} <b id="rw-clock"></b></span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-gold rw-idle" id="rw-open">
        {{ __('minigame.rewarded_cta', ['minutes' => $rwMinutes]) }}
    </button>
</div>

<div class="rw-modal" id="rw-modal" hidden>
    <div class="rw-dialog" role="dialog" aria-modal="true" aria-labelledby="rw-title">
        <h3 id="rw-title">{{ __('minigame.rewarded_cta', ['minutes' => $rwMinutes]) }}</h3>

        {{-- 廣告版位。沒有設定聯播網時 ad-unit 什麼都不輸出,流程仍然可以走完,
             這樣過審前也能測整條路徑。 --}}
        @include('partials.ad-unit', ['zone' => 'game_end'])

        <p class="rw-status" id="rw-status"></p>
        <div class="rw-actions">
            <button type="button" class="btn btn-gold btn-sm" id="rw-claim" disabled>
                {{ __('minigame.rewarded_claim') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline" id="rw-close">
                {{ __('minigame.rewarded_close') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var bar = document.getElementById('rw-bar');
    if(!bar) return;
    var modal  = document.getElementById('rw-modal');
    var openB  = document.getElementById('rw-open');
    var claimB = document.getElementById('rw-claim');
    var closeB = document.getElementById('rw-close');
    var status = document.getElementById('rw-status');
    var clock  = document.getElementById('rw-clock');
    var csrf   = document.querySelector('meta[name="csrf-token"]').content;

    var left  = parseInt(bar.dataset.left, 10) || 0;
    var token = null, timer = null, tick = null;

    var T = {
        watching: @json(__('minigame.rewarded_watching', ['seconds' => '__S__'])),
        failed:   @json(__('minigame.rewarded_failed'))
    };

    function mmss(s){
        var m = Math.floor(s / 60), r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    // 剩餘時間歸零時直接重新載入:題庫與回合上限是伺服器算的,
    // 只把畫面上的字改掉會讓玩家以為還解鎖著。
    function runClock(){
        bar.classList.toggle('is-live', left > 0);
        if(left <= 0){ if(tick) clearInterval(tick); return; }
        clock.textContent = mmss(left);
        tick = setInterval(function(){
            left--;
            if(left <= 0){ clearInterval(tick); location.reload(); return; }
            clock.textContent = mmss(left);
        }, 1000);
    }

    function post(url, body){
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify(body || {})
        });
    }

    function openModal(){
        modal.hidden = false;
        claimB.disabled = true;
        status.textContent = '';

        post(@json(route('rewarded.start'))).then(function(r){ return r.json(); }).then(function(d){
            if(!d.token) return;
            token = d.token;
            var n = d.minWatchSeconds || 15;
            status.textContent = T.watching.replace('__S__', n);
            timer = setInterval(function(){
                n--;
                if(n <= 0){
                    clearInterval(timer);
                    status.textContent = '';
                    claimB.disabled = false;   // 秒數由伺服器再驗一次,這裡只是 UI
                    return;
                }
                status.textContent = T.watching.replace('__S__', n);
            }, 1000);
        });
    }

    openB && openB.addEventListener('click', openModal);

    // 付費關卡上的「看廣告解鎖」按鈕從這裡打開同一個彈窗。
    // 掛在 window 而不是各頁自己複製一份流程:計時、發憑證、兌換那幾段只能有
    // 一份,分身出去之後遲早會有一份忘了改。骰子那頁的關卡是 JS 動態產生的,
    // 事件監聽器綁不到,所以要用全域函式而不是 addEventListener。
    window.rewardedUnlockOpen = openModal;

    claimB.addEventListener('click', function(){
        claimB.disabled = true;
        post(@json(route('rewarded.claim')), {token: token}).then(function(r){
            if(!r.ok){ status.textContent = T.failed; return null; }
            return r.json();
        }).then(function(d){
            if(!d) return;
            // 重新載入,讓伺服器端的 $isPremium 重新計算 —— 題庫是後端決定的,
            // 不重載的話畫面解鎖了但內容還是免費版。
            location.reload();
        });
    });

    closeB.addEventListener('click', function(){
        modal.hidden = true;
        if(timer) clearInterval(timer);
    });

    runClock();
})();
</script>
@endpush
@endunless

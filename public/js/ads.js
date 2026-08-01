/* =====================================================
   廣告版位的投放時機
   =====================================================
   把「決定尺寸 → 插入 ins → 通知聯播網」這段收在一個地方。

   為什麼需要:ExoClick 是對**當下的容器尺寸**投放的,所以容器必須先有實際
   大小。頁面載入時就投放一個 display:none 或高度 0 的容器,拿到的會是 0×0
   的空廣告 —— 這個坑在「看廣告解鎖」的彈窗上踩過一次,那時整個彈窗是
   hidden,廣告永遠是空的。

   於是就有了兩種時機:一般版位在頁面載入時投放,收合式版位等展開才投放。
   兩者只差在什麼時候呼叫,邏輯不該有兩份。                                */

(function () {
  /**
   * 對一個版位容器投放廣告。同一個容器只會投一次 —— 重複投放會重複計曝光,
   * 那在聯播網眼裡是無效流量。
   *
   * 容器要帶 data-zoneid-narrow(必要)與 data-zoneid-wide(選配)。
   * 斷點 940px = 900px 素材 + 邊距;寬版素材塞進窄螢幕是被裁掉而不是縮小。
   */
  window.exoServe = function (el) {
    if (!el || el.dataset.served) return;

    var wide = el.dataset.zoneidWide
      && window.matchMedia('(min-width: 940px)').matches;
    var zone = wide ? el.dataset.zoneidWide : el.dataset.zoneidNarrow;
    if (!zone) return;

    el.dataset.served = '1';

    var ins = document.createElement('ins');
    ins.className = 'eas6a97888e2';
    ins.dataset.zoneid = zone;
    el.appendChild(ins);

    (window.AdProvider = window.AdProvider || []).push({ serve: {} });
  };
})();

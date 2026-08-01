/**
 * 逐漸升溫。
 *
 * 開著的時候題目從最輕的一級開始,每過幾回合往上開放一級;關掉的話所有等級
 * 一開始就全部混在一起抽。五個小遊戲共用這一支,升溫的節奏才會一致 ——
 * 各自寫一套的話,同一個開關在不同遊戲裡的意思就不一樣了。
 *
 * 只負責「這一回合可以用哪幾級」,不管題目怎麼抽,也不管哪一級要付費 ——
 * 付費的界線由各遊戲自己那份 pool 決定(拿不到的等級本來就不在 order 裡)。
 */
(function () {
    /** 每 N 回合往上開一級。 */
    var STEP = 4;

    /**
     * @param {number} round     從 1 開始的回合數
     * @param {string[]} order   由輕到重的等級,已經濾掉沒有權限的那幾級
     * @param {boolean} enabled  有沒有開「逐漸升溫」
     * @returns {string[]} 這一回合可以抽的等級
     */
    function tiersFor(round, order, enabled) {
        if (!enabled || !order.length) return order;

        var level = Math.floor((Math.max(1, round) - 1) / STEP) + 1;

        return order.slice(0, Math.min(level, order.length));
    }

    /**
     * 這一回合實際落在哪一級。取開放範圍裡最重的那一級 —— 升溫的重點是
     * 「越玩越大」,每回合都從全部開放的等級裡隨機挑的話,升溫感會被稀釋掉。
     */
    function topTierFor(round, order, enabled) {
        var allowed = tiersFor(round, order, enabled);

        return allowed.length ? allowed[allowed.length - 1] : null;
    }

    /** 設定畫面的開關現在是不是開著。找不到開關就當作沒開。 */
    function enabled(id) {
        var el = document.getElementById(id || 'escalate-toggle');

        return !!(el && el.checked);
    }

    window.Escalation = {
        STEP: STEP,
        tiersFor: tiersFor,
        topTierFor: topTierFor,
        enabled: enabled,
    };
})();

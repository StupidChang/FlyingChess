/**
 * 玩家頭像挑選器。
 *
 * 五個小遊戲的設定畫面各自有一份 .mg-player-row 的產生程式(有的還是 innerHTML
 * 字串),所以這支不去改它們的產生邏輯 —— 改用 MutationObserver 盯著畫面,
 * 只要出現沒有頭像的玩家列就自己補上去。這樣新增一個遊戲也不用再接一次。
 *
 * 頭像挑選面板是 row 內的絕對定位元素,不用算座標,手機上也不會跑版。
 */
(function () {
    var AVATARS = ['😈', '😇', '🔥', '💋', '🌙', '⭐', '🍑', '🍒', '🐰', '🦊', '🐻', '🦄'];

    /** 沒選過的時候依序給不同的預設,兩個玩家不會一開始就撞頭像。 */
    var handed = 0;

    function build(row) {
        var wrap = document.createElement('div');
        wrap.className = 'pa';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pa-btn';
        btn.setAttribute('aria-label', '選擇頭像');
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = AVATARS[handed % AVATARS.length];
        handed++;

        var grid = document.createElement('div');
        grid.className = 'pa-grid';
        grid.hidden = true;

        AVATARS.forEach(function (emoji) {
            var opt = document.createElement('button');
            opt.type = 'button';
            opt.className = 'pa-opt';
            opt.textContent = emoji;
            opt.addEventListener('click', function (e) {
                e.stopPropagation();
                btn.textContent = emoji;
                close();
            });
            grid.appendChild(opt);
        });

        function close() {
            grid.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            // 一次只開一個面板
            closeAll();
            grid.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
        });

        wrap.appendChild(btn);
        wrap.appendChild(grid);
        row.insertBefore(wrap, row.firstChild);
    }

    function closeAll() {
        Array.prototype.forEach.call(document.querySelectorAll('.pa-grid'), function (g) {
            g.hidden = true;
            var b = g.previousElementSibling;
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }

    function scan(root) {
        var rows = (root || document).querySelectorAll
            ? (root || document).querySelectorAll('.mg-player-row')
            : [];
        Array.prototype.forEach.call(rows, function (row) {
            if (!row.querySelector('.pa')) build(row);
        });
        if (root && root.classList && root.classList.contains('mg-player-row') && !root.querySelector('.pa')) {
            build(root);
        }
    }

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    /**
     * 這一列玩家的顯示名稱 —— 頭像直接接在名字前面。
     *
     * 刻意不把頭像另外存成一個欄位:各遊戲內部有的把玩家存成字串、有的存成物件,
     * 統一改資料結構要動五個遊戲的每一處顯示。接在名字前面的話,凡是印得出名字
     * 的地方(回合提示、計分板、遊玩紀錄、後台)就都自動帶著頭像。
     */
    function displayName(row) {
        var input = row.querySelector('.p-name');
        var btn = row.querySelector('.pa-btn');
        var name = input ? input.value.trim() : '';

        if (!name) return '';

        return btn ? btn.textContent + ' ' + name : name;
    }

    window.PlayerAvatar = {
        list: AVATARS,
        scan: scan,
        displayName: displayName,
    };

    function boot() {
        scan(document);
        new MutationObserver(function (records) {
            records.forEach(function (r) {
                Array.prototype.forEach.call(r.addedNodes, function (n) {
                    if (n.nodeType === 1) scan(n);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

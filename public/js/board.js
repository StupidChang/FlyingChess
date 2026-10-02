/* =====================================================
   情侶飛行棋 V2 — board.js
   Handles: play mode + square-content edit mode
   Canvas/layout/path edit: see board-editor.js
   ===================================================== */

/* Category "colors" reference the CSS custom properties defined in
   board.css (--sq-action, --sq-drink, ...) so the action-modal color
   bar always matches the on-board square styling — one source of truth. */
const COLOR_HEX = {
  action:'var(--sq-action)', drink:'var(--sq-drink)', dare:'var(--sq-dare)', truth:'var(--sq-truth)',
  strip:'var(--sq-strip)', move:'var(--sq-move)', normal:'var(--sq-normal)',
  start:'var(--sq-start)', end:'var(--sq-end)', male:'var(--sq-male)', female:'var(--sq-female)',
  p1:'var(--sq-p1)', p2:'var(--sq-p2)', p3:'var(--sq-p3)', p4:'var(--sq-p4)',
};

/* V8.0 四人版:p1..p4 只對該座位的玩家生效,其他人停到就跳過 —— 和 male/female
   同一個機制,差別只在比對的是座位而不是性別。 */
const SEAT_COLORS = ['p1', 'p2', 'p3', 'p4'];

/* 格子類型圖示(SQ_TYPE_ICONS / sqTypeIconHtml)在 sq-icons.js,大廳的快速預覽也用同一份。 */
/* 開局視窗的「骰一個名字」。形容詞 + 名詞,同一局不重複,超過輸入框上限(12 字)的組合跳過。 */
function randomPlayerName(taken) {
  const adj = PI18N.nameAdj || [], noun = PI18N.nameNoun || [];
  if (!adj.length || !noun.length) return null;
  const sep = PI18N.nameJoinSpace ? ' ' : '';
  for (let tries = 0; tries < 40; tries++) {
    const name = adj[Math.floor(Math.random() * adj.length)] + sep + noun[Math.floor(Math.random() * noun.length)];
    if (name.length <= 12 && !taken.includes(name)) return name;
  }
  return null;
}
function setupNameInputs() {
  return Array.from(document.querySelectorAll('#setup-modal input[id^="setup-p"]'));
}
function rollSetupName(input) {
  const others = setupNameInputs().filter(el => el !== input).map(el => el.value.trim());
  const name = randomPlayerName(others);
  if (name) input.value = name;
}
function initSetupNames() {
  setupNameInputs().forEach(rollSetupName);   // 預設名字就是骰一次的結果
  document.querySelectorAll('.setup-name-dice').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.for);
      if (!input) return;
      rollSetupName(input);
      btn.classList.remove('is-rolling'); void btn.offsetWidth; btn.classList.add('is-rolling');
    });
  });
  // 上次選的棋子樣式
  const pref = pieceStylePref();
  const radio = document.querySelector('input[name="piece-style"][value="' + pref + '"]');
  if (radio) radio.checked = true;
}
document.addEventListener('DOMContentLoaded', initSetupNames);

/* 「玩法」面板裡的格子說明:文字由伺服器輸出,這裡只補圖示 */
function fillLegendIcons() {
  document.querySelectorAll('.sq-legend [data-sq-type]').forEach(function (el) {
    el.innerHTML = sqTypeIconHtml(el.getAttribute('data-sq-type'));
  });
}
document.addEventListener('DOMContentLoaded', fillLegendIcons);

/* ── 棋子樣式 ─────────────────────────────────────────────
   圓片(預設)／立體棋子／愛心,開局視窗選,記在這台裝置(localStorage)。
   「立體」不是 WebGL:真的 3D 要多載一套引擎(幾百 KB、手機耗電),為了一顆棋子不值得。
   這裡用 SVG 的受光面、背光面、高光與地面陰影做出立體感,重量跟圓片一樣。
   顏色吃 .piece-N 上的 --pc,所以四個座位的配色只維護一份。 */
const PIECE_STYLES = ['disc', 'pawn', 'heart'];
function pieceStylePref() {
  try { const v = localStorage.getItem('pieceStyle'); if (PIECE_STYLES.includes(v)) return v; } catch (e) {}
  return 'disc';
}
function applyPieceStyle(style) {
  if (!PIECE_STYLES.includes(style)) style = 'disc';
  const board = document.getElementById('game-board');
  if (board) PIECE_STYLES.forEach(s => board.classList.toggle('pieces-' + s, s === style));
  try { localStorage.setItem('pieceStyle', style); } catch (e) {}
}
/*
 * 3D 棋子:用 CSS 3D 真的在空間裡疊出一顆兵。
 *
 * 不是 WebGL(要多載一套幾百 KB 的引擎,手機耗電),也不是一張有漸層的圖:一顆兵是
 * 24 片圓片沿著高度往上疊(一片一片 translateZ),每片的半徑照兵的輪廓(底座 → 身體 →
 * 領口 → 圓頭),整顆再用 rotateX 斜著看 —— 瀏覽器用透視算出來的就是一個有厚度的
 * 實心物體。影子是地板上的另一片,跳起來的時候留在原地、跟著變小。
 * 每片的明暗照高度與輪廓斜率算,看起來像打了頂光。
 */
const PAWN_SLICES = 44;
function pawnRadius(t) {               // t:0(底)→ 1(頂),回傳底座半徑的比例
  if (t < 0.07) return 1 - t * 1.4;                        // 底座:圓角往內收
  if (t < 0.13) return 0.90 - (t - 0.07) * 4.5;            // 底座上緣的斜面
  if (t < 0.50) return 0.63 - (t - 0.13) * 0.85;           // 身體:往上變細
  if (t < 0.55) return 0.54;                               // 領口(比身體寬一圈)
  if (t < 0.60) return 0.30;                               // 細脖子:頭跟身體分得開
  const u = (t - 0.80) / 0.20;                             // 頭:球
  return 0.44 * Math.sqrt(Math.max(0, 1 - u * u));
}
function pawn3dHtml() {
  let slices = '';
  for (let k = 0; k < PAWN_SLICES; k++) {
    const t = k / (PAWN_SLICES - 1);
    const r = pawnRadius(t);
    if (r <= 0.03) continue;
    /* 明暗:越高越亮(頂光);往上收的面朝上、受光,往外凸的面朝下、背光。
       每片是純色 —— 漸層會讓每一片的邊緣變暗,疊起來就是一圈一圈的紋路。 */
    const slope = (pawnRadius(Math.min(1, t + 0.03)) - r) / 0.03;
    const light = Math.round(Math.max(-30, Math.min(34, t * 26 - 14 - slope * 9)));
    slices += '<i style="--z:' + t.toFixed(3) + ';--r:' + (r * 100).toFixed(1) + '%;--l:' + light + '"></i>';
  }
  return '<div class="piece-shape shape-pawn" aria-hidden="true">'
    + '<div class="p3d-floor"><div class="p3d-shadow"></div>'
    + '<div class="p3d-body">' + slices + '<b class="p3d-shine"></b></div></div></div>';
}

function pieceShapeSvg(n) {
  const h = 'ph' + n;
  return pawn3dHtml()
    + '<svg class="piece-shape shape-heart" viewBox="0 0 40 40" aria-hidden="true">'
    + '<defs><radialGradient id="' + h + '" cx=".35" cy=".3" r=".75">'
    + '<stop offset="0" stop-color="#fff" stop-opacity=".45"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/>'
    + '<stop offset="1" stop-color="#000" stop-opacity=".35"/></radialGradient></defs>'
    + '<path d="M20 35S4.5 25.7 4.5 14.8C4.5 9.6 8.4 6 13 6c3.1 0 5.6 1.7 7 4.2C21.4 7.7 23.9 6 27 6c4.6 0 8.5 3.6 8.5 8.8C35.5 25.7 20 35 20 35Z"'
    + ' style="fill:var(--pc)" stroke="rgba(255,255,255,.85)" stroke-width="1.4"/>'
    + '<path d="M20 35S4.5 25.7 4.5 14.8C4.5 9.6 8.4 6 13 6c3.1 0 5.6 1.7 7 4.2C21.4 7.7 23.9 6 27 6c4.6 0 8.5 3.6 8.5 8.8C35.5 25.7 20 35 20 35Z" fill="url(#' + h + ')"/>'
    + '</svg>';
}

/* Entry wheel colours, in dice-face order — matches the six-slice wheel printed
   in each corner of the physical board. */
const WHEEL_HEX = ['#ec4899', '#3b82f6', '#22c55e', '#eab308', '#f97316', '#ef4444'];

/** The board's entry wheel, or null when pieces start on the track directly. */
function startWheel() {
  const w = window.START_WHEEL;
  return (Array.isArray(w) && w.length === 6) ? w : null;
}

/** A piece only occupies a square once it has entered. Without a wheel,
    everyone is on the track from the first turn (the previous behaviour). */
function isOnTrack(player) {
  return !startWheel() || player.entered === true;
}

/* Small inline SVG icon set — replaces emoji for a more premium, on-brand
   look. All icons use currentColor so color is controlled purely via CSS. */
const SVG_ICONS = {
  dice: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3.75" y="3.75" width="16.5" height="16.5" rx="4"/><circle cx="8.25" cy="8.25" r="1.15" fill="currentColor" stroke="none"/><circle cx="15.75" cy="8.25" r="1.15" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.15" fill="currentColor" stroke="none"/><circle cx="8.25" cy="15.75" r="1.15" fill="currentColor" stroke="none"/><circle cx="15.75" cy="15.75" r="1.15" fill="currentColor" stroke="none"/></svg>',
  heart: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.645 20.91a.75.75 0 0 0 .708 0c.106-.058.243-.134.406-.228a25.175 25.175 0 0 0 4.244-3.17C19.312 15.36 21.75 12.174 21.75 8.25 21.75 5.322 19.286 3 16.313 3A5.5 5.5 0 0 0 12 5.052 5.5 5.5 0 0 0 7.688 3C4.714 3 2.25 5.322 2.25 8.25c0 3.925 2.438 7.111 4.739 9.256a25.175 25.175 0 0 0 4.244 3.17c.163.094.3.17.406.228l.002.001-.002-.001Z"/></svg>',
  cup: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l-1.2 12.5a3 3 0 0 1-3 2.7h-3.6a3 3 0 0 1-3-2.7L6 3Z"/><path d="M9 21h6"/><path d="M12 18.2V21"/><path d="M6.6 7.5h10.8"/></svg>',
  trophy: '<svg viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 5.6 6.73 6.73 0 0 0 2.743 1.35A6.98 6.98 0 0 1 9.25 15v.25H9a.75.75 0 0 0 0 1.5h1.5v2.128a2.251 2.251 0 0 1-1.679 2.17l-.196.047a.75.75 0 0 0 .353 1.46l.196-.047a3.75 3.75 0 0 0 2.826-3.63V16.75h1.5a.75.75 0 0 0 0-1.5h-.25V15a6.98 6.98 0 0 1-.293-1.342 6.73 6.73 0 0 0 2.743-1.35 6.753 6.753 0 0 0 6.139-5.6.75.75 0 0 0-.585-.858 47.077 47.077 0 0 0-3.07-.543V2.62a.75.75 0 0 0-.658-.744 49.798 49.798 0 0 0-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 0 0-.657.744Zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 0 1 3.16 5.337a45.6 45.6 0 0 1 2.006-.343v.256Zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 0 1-2.863 3.207 6.72 6.72 0 0 0 .857-3.294Z" clip-rule="evenodd"/></svg>',
};
function svgIcon(name) { return SVG_ICONS[name] || ''; }

/* ── i18n + locale-aware endpoints (injected by the Blade views) ──
   PLAY_I18N: UI strings; BOARD_ROUTES: route()-generated URLs that carry
   the /tw|cn|jp|en prefix (edit pages only). Placeholders use the
   __N__/__NAME__ convention and are replaced via String.replace. */
const PI18N = window.PLAY_I18N || {};
function tp(key, repl) {
  let s = (PI18N[key] != null) ? PI18N[key] : key;
  if (repl) for (const k in repl) s = s.replace(k, repl[k]);
  return s;
}

/* ── Game state ── */
const state = {
  players: [],   // { name, stepIndex, skip, gender }
  current: 0,
  rolling: false,
  gameOver: false,
};

/* ═══════════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════════ */
function getSq(pos) {
  return (window.SQUARES_DATA && window.SQUARES_DATA[pos])
      || { text:'', color:'normal', fly_to:null, grid_row:1, grid_col:1 };
}

function escHtml(s) {
  return String(s||'')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/\n/g,'<br>');
}

/** Resolve which path array a given gender should follow */
function getEffectivePath(gender) {
  const pd = window.PATH_DATA || { all: null };
  if (gender && gender !== 'all' && pd[gender] && pd[gender].length > 0) return pd[gender];
  if (pd.all && pd.all.length > 0) return pd.all;
  // Fallback: sorted position keys
  return Object.keys(window.SQUARES_DATA || {}).map(Number).sort((a,b)=>a-b);
}

/**
 * 這位玩家實際走的路線。
 *
 * 棋盤有 seats(四色飛行棋那種「每個座位一條路線」)的話,第 N 位玩家走 seats[N-1]
 * —— 各自從自己的閘門出發、轉進自己顏色的家門。沒有的話照舊依性別(male／female／all)。
 * 見 FlyingChessV8ReplicaSeeder。
 */
function getPlayerPath(player) {
  const seats = (window.PATH_DATA || {}).seats;
  if (Array.isArray(seats) && seats.length && player && player.seat != null) {
    const p = seats[player.seat % seats.length];
    if (Array.isArray(p) && p.length) return p;
  }
  return getEffectivePath(player ? player.gender : 'all');
}

/** 棋盤上所有會被走到的路線(座位路線 + 共用／性別路線) */
function allRoutes() {
  const pd = window.PATH_DATA || {};
  const list = [getEffectivePath('all')];
  ['male', 'female'].forEach(g => { if (pd[g] && pd[g].length) list.push(pd[g]); });
  (Array.isArray(pd.seats) ? pd.seats : []).forEach(p => { if (Array.isArray(p) && p.length) list.push(p); });
  return list;
}

/** Current position ID for a player */
function currentPos(player) {
  const path = getPlayerPath(player);
  return path[Math.min(player.stepIndex, path.length - 1)];
}

/** Compute arrow map from path (pos → arrow char) */
function computeArrowMap(path, squaresData) {
  const map = {};
  for (let i = 0; i < path.length - 1; i++) {
    const from = squaresData[path[i]];
    const to   = squaresData[path[i+1]];
    if (!from || !to) continue;
    const dr = to.grid_row - from.grid_row;
    const dc = to.grid_col - from.grid_col;
    if      (dr===0 && dc>0)  map[path[i]] = '→';
    else if (dr===0 && dc<0)  map[path[i]] = '←';
    else if (dr>0  && dc===0) map[path[i]] = '↓';
    else if (dr<0  && dc===0) map[path[i]] = '↑';
  }
  if (path.length > 0) map[path[path.length-1]] = '★';
  return map;
}

/* ═══════════════════════════════════════════════════
   3D DICE — Face builder & rolling
   ═══════════════════════════════════════════════════ */
const DICE_DOTS = {
  1: [0,0,0, 0,1,0, 0,0,0],
  2: [0,0,1, 0,0,0, 1,0,0],
  3: [0,0,1, 0,1,0, 1,0,0],
  4: [1,0,1, 0,0,0, 1,0,1],
  5: [1,0,1, 0,1,0, 1,0,1],
  6: [1,0,1, 1,0,1, 1,0,1],
};

/** Build an HTML dice face with correct dots */
function diceFaceHtml(n, cls) {
  const d = DICE_DOTS[n] || DICE_DOTS[1];
  let html = '<div class="' + (cls || 'dice-face-flat') + '">';
  for (let i = 0; i < 9; i++) {
    html += d[i] ? '<span class="dot"></span>' : '<span></span>';
  }
  html += '</div>';
  return html;
}

/** Build full 3D cube faces inside the cube element */
function build3dCube() {
  const cube = document.getElementById('dice-cube');
  if (!cube) return;
  cube.innerHTML = '';
  for (let face = 1; face <= 6; face++) {
    const d = DICE_DOTS[face];
    let faceEl = document.createElement('div');
    faceEl.className = 'dice-face-3d dice-f' + face;
    for (let i = 0; i < 9; i++) {
      const sp = document.createElement('span');
      if (d[i]) sp.className = 'dot';
      faceEl.appendChild(sp);
    }
    cube.appendChild(faceEl);
  }
}

// Rotation to show each face value
const FACE_ROT = {
  1: { x: 0, y: 0 },
  2: { x: -90, y: 0 },
  3: { x: 0, y: 90 },
  4: { x: 0, y: -90 },
  5: { x: 90, y: 0 },
  6: { x: 0, y: 180 },
};
const FACE_ROTATIONS = Object.fromEntries(
  Object.entries(FACE_ROT).map(([v, r]) => [v, `rotateX(${r.x}deg) rotateY(${r.y}deg)`])
);

/** 0–359 的等效角度。往前收的時候要用它算「還要再轉多少才會到那一面」。 */
function mod360(deg) {
  return ((deg % 360) + 360) % 360;
}

/**
 * 擲骰動畫。**動畫停下來的那一面就是骰出來的點數。**
 *
 * 舊版是兩段式,而且兩段是壞的:
 *
 *   1. CSS 的 @keyframes diceRoll3d 結束在 rotateX(900deg) rotateY(720deg) ——
 *      900 ≡ 180、720 ≡ 0,也就是**每次都停在同一個面**(玩家看到的「六」)。
 *   2. 那段動畫只有 0.9s,但 JS 是在 1400ms 才切到 landing —— 中間那 500ms
 *      骰子凍在那個固定面上(玩家說的「停一下」)。
 *   3. 然後 landing 要從 900deg 倒轉回目標角度,倒轉本身又是一段看得見的動畫,
 *      所以看起來像「先骰出六,再跳成真正的點數」。
 *
 * 現在改成 rAF 自己轉,收尾**往前**收到結果面(加上到那一面的差角再多轉一圈),
 * 全程同一個方向、同一條時間軸。秒數只寫在這裡一份,不會再和 CSS 對不上。
 */
function roll3dDice() {
  return new Promise(function (resolve) {
    const overlay = document.getElementById('dice-overlay');
    const cube = document.getElementById('dice-cube');
    const result = Math.floor(Math.random() * 6) + 1;
    if (!overlay || !cube) {
      resolve(result);
      return;
    }
    const scene = overlay.querySelector('.dice-scene');
    const reduced = window.matchMedia
      && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    overlay.classList.add('active');

    if (reduced) {
      // Reduced motion: show the result face directly, briefly
      cube.className = 'dice-cube';
      cube.style.transition = 'none';
      cube.style.transform = FACE_ROTATIONS[result];
      setTimeout(function () {
        overlay.classList.remove('active');
        resolve(result);
      }, 650);
      return;
    }

    const TUMBLE_MS = 1000;   // 自由翻滾
    const LAND_MS = 720;      // 減速落到結果面
    const HOLD_MS = 620;      // 停在結果上讓人看清楚

    // ── 自由翻滾:三軸各自的角速度,每一場都不一樣 ──
    const s = {
      rx: 0, ry: 0, rz: 0,
      vx: 700 + Math.random() * 320,   // deg/s
      vy: 560 + Math.random() * 320,
      vz: (Math.random() < .5 ? -1 : 1) * (200 + Math.random() * 160),
      last: performance.now(),
      raf: 0,
    };
    cube.className = 'dice-cube';
    cube.style.transition = 'none';

    const step = function (now) {
      const dt = Math.min((now - s.last) / 1000, .05);
      s.last = now;
      s.rx += s.vx * dt;
      s.ry += s.vy * dt;
      s.rz += s.vz * dt;
      cube.style.transform =
        'rotateX(' + s.rx.toFixed(1) + 'deg) rotateY(' + s.ry.toFixed(1) + 'deg) rotateZ(' + s.rz.toFixed(1) + 'deg)';
      s.raf = requestAnimationFrame(step);
    };
    s.raf = requestAnimationFrame(step);

    // ── 落面:往前收,不倒轉 ──
    setTimeout(function () {
      cancelAnimationFrame(s.raf);
      const tgt = FACE_ROT[result];
      /* 加「到那一面的差角」再多轉一圈,所以永遠是繼續往前轉、減速停住。
         Z 收回整數圈(差角取最短),不然骰面的點會是斜的。 */
      const fx = s.rx + mod360(tgt.x - s.rx) + 360;
      const fy = s.ry + mod360(tgt.y - s.ry) + 360;
      const dz = mod360(-s.rz);
      const fz = s.rz + (dz > 180 ? dz - 360 : dz);

      /* 先把翻滾的最後姿態「釘」成一個沒有過渡的明確值,強制一次 reflow,
         再換上過渡與目標 —— 少了這一步,瀏覽器可能把兩次樣式變更併成一次,
         過渡的起點會變成翻滾**開始前**的角度:實測有三分之一的次數骰面停在
         上一輪的點數上,而那就是回報的「停在六再跳掉」。 */
      cube.style.transition = 'none';
      cube.style.transform =
        'rotateX(' + s.rx.toFixed(1) + 'deg) rotateY(' + s.ry.toFixed(1) + 'deg) rotateZ(' + s.rz.toFixed(1) + 'deg)';
      void cube.offsetHeight;

      cube.style.transition = 'transform ' + (LAND_MS / 1000) + 's cubic-bezier(.17,.85,.3,1.01)';
      cube.style.transform =
        'rotateX(' + fx.toFixed(1) + 'deg) rotateY(' + fy.toFixed(1) + 'deg) rotateZ(' + fz.toFixed(1) + 'deg)';
    }, TUMBLE_MS);

    // ── 觸地彈跳 ──
    setTimeout(function () {
      if (scene) scene.classList.add('dice-landed');
    }, TUMBLE_MS + LAND_MS - 60);

    // ── 收掉 ──
    setTimeout(function () {
      overlay.classList.remove('active');
      if (scene) scene.classList.remove('dice-landed');
      /* 骰面留在結果上(不重設成面 1)—— 重設會讓它在關掉的瞬間轉回去,那一下
         也是一種「跳」。但要把過渡關掉並把角度收成該面的標準值:留著過渡的話,
         下一輪的起點會是「還在過渡中」的狀態,誤差會一輪一輪累積。 */
      cube.style.transition = 'none';
      cube.style.transform = FACE_ROTATIONS[result];
      resolve(result);
    }, TUMBLE_MS + LAND_MS + HOLD_MS);
  });
}

/* ═══════════════════════════════════════════════════
   SQUARE INFO (read-only, play mode)
   ═══════════════════════════════════════════════════ */

const SQ_CATEGORY_KEY = {
  action: 'catAction', drink: 'catDrink', dare: 'catDare', truth: 'catTruth',
  strip: 'catStrip', move: 'catMove', normal: 'catNormal', start: 'catStart',
  end: 'catEnd', male: 'catMale', female: 'catFemale',
};

/**
 * 點格子看完整內容(唯讀)。
 *
 * 為什麼需要:格子裡放得下幾個字是「字級比例」的函數,跟格子多大無關
 * (見 sizeGameBoard 上面那段)。長文字的棋盤 —— 例如逐格復刻的原版盤面,
 * 最長的一格 50 字 —— 在方形格子裡一定會被裁掉。與其把字縮到看不清,
 * 不如讓人點開來看。
 *
 * 不影響回合:純顯示,沒有任何狀態改變,隨時可以關。
 */
function openSqInfo(pos) {
  const sq = (window.SQUARES_DATA || {})[pos];
  const modal = document.getElementById('sq-info-modal');
  if (!sq || !modal) return;

  const bar = document.getElementById('sq-info-bar');
  if (bar) bar.className = 'sq-info-bar color-' + (sq.color || 'normal');

  const title = document.getElementById('sq-info-title');
  if (title) title.textContent = tp('sqInfoTitle').replace(':n', pos);

  const cat = document.getElementById('sq-info-cat');
  if (cat) cat.textContent = tp(SQ_CATEGORY_KEY[sq.color] || 'catNormal');

  const text = document.getElementById('sq-info-text');
  if (text) text.textContent = sq.text || '';

  /* 這一格會發生什麼事(飛行/移動/停一輪),以及它是不是不在這條路線上 ——
     復刻的四人盤面上有 15 格是別人的家門,點開來看得出「這格你走不到」。 */
  const notes = document.getElementById('sq-info-notes');
  if (notes) {
    notes.innerHTML = '';
    const lines = [];
    if (sq.fly_to != null) lines.push(tp('sqInfoFly').replace(':n', sq.fly_to));
    if (sq.move_steps) lines.push(tp('sqInfoMove').replace(':n', sq.move_steps));
    if (sq.skip_turn) lines.push(tp('sqInfoSkip'));
    if (!allRoutes().some(r => r.indexOf(pos) !== -1)) lines.push(tp('sqInfoOffPath'));
    lines.forEach(function (line) {
      const li = document.createElement('li');
      li.textContent = line;
      notes.appendChild(li);
    });
  }

  openModal('sq-info-modal');
}
window.openSqInfo = openSqInfo;

/* ═══════════════════════════════════════════════════
   BOARD RENDERING  (content + play modes)
   ═══════════════════════════════════════════════════ */
let lastGrid = null;

/* Measure the header and pin --header-h so .play-page's height budget
   matches the real chrome instead of a hardcoded guess. */
function updateHeaderVar() {
  const header = document.querySelector('.site-header');
  if (header) {
    document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
  }
  // Self-contained play screen: hide the footer so the page never scrolls
  // (a scrolled sticky header would sit on top of the board).
  const footer = document.querySelector('.site-footer');
  if (footer && document.querySelector('.play-page')) footer.style.display = 'none';
}

/* Size the board to the wrap's actual free space (padding excluded),
   preserving the grid's aspect ratio. */
/* 棋盤大小有兩組尺寸,由棋盤上方的按鈕切換(toggleBoardSize)。

   `large` 是現在的預設:一格的可讀下限 78px —— 52px 試過,字級只有 8.8px,
   看得到有字但讀不出寫什麼;78px 是實測 30 字左右的格子讀得動的下限(13 欄的
   原版復刻盤面就是這個字量)。低於下限不再縮,改讓棋盤超出視窗由 .board-wrap 捲動。

   `standard` 是放大之前的那一組。放大解決的是「字讀得吃力」,代價是欄數多的盤面
   在筆電上就得捲動才看得到全盤 —— 想一眼看完整張棋盤的時候,小的那組才是對的。
   兩者都留著、讓人自己選,比替他決定好。

   maxW / maxWWide 是桌機的版面寬度上限:欄數多的棋盤需要更寬的版面,所以 12 欄
   以上走 maxWWide。標準組兩者相同(原本就是一律 960)。 */
const BOARD_SIZES = {
  large:    { minCell: 78, maxW: 1040, maxWWide: 1200 },
  standard: { minCell: 64, maxW: 960,  maxWWide: 960 },
};
const BOARD_SIZE_KEY = 'play_board_size';

/** 目前選的尺寸。純顯示偏好,記在 localStorage(無痕模式下存取會 throw)。 */
function boardSizePref() {
  let v = null;
  try { v = localStorage.getItem(BOARD_SIZE_KEY); } catch (e) { /* 無痕模式 */ }
  return BOARD_SIZES[v] ? v : 'large';
}

/** 按鈕上的字是「按下去會變成什麼」,不是現在的狀態。 */
function syncBoardSizeBtn() {
  const btn = document.getElementById('board-size-toggle');
  if (!btn) return;
  const large = boardSizePref() === 'large';
  btn.textContent = large ? tp('boardSmaller') : tp('boardBigger');
  // aria-pressed:true = 現在是放大的
  btn.setAttribute('aria-pressed', String(large));
}

function toggleBoardSize() {
  const next = boardSizePref() === 'large' ? 'standard' : 'large';
  try { localStorage.setItem(BOARD_SIZE_KEY, next); } catch (e) { /* 無痕模式 */ }
  syncBoardSizeBtn();

  const board = document.getElementById('game-board');
  if (!board || !lastGrid) return;
  sizeGameBoard(board, lastGrid.cols, lastGrid.rows);
  /* 棋子的位置是從格子的 bounding box 算出來的,不重畫就會留在舊格子上
     (跟 resize 同一個理由)。縮放之後輪到的棋子可能被捲出畫面,再跟一次。 */
  if (typeof renderPieces === 'function') renderPieces();
  followActivePiece(activePieceTarget());
}
window.toggleBoardSize = toggleBoardSize;

/**
 * 一段文字在格子裡「佔幾個中文字的寬度」。
 *
 * 下面的門檻都是用中文字數推出來的,但英文一個字母大約只有半個全形字寬
 * (空白更窄),直接拿 .length 算,英文棋盤會被判成字超多、字級縮到看不清。
 * 行末斷字的浪費英文比中文大(整個單字換行),所以字母給 .58 而不是 .5。
 */
function visualLength(text) {
  let n = 0;
  for (const ch of String(text || '')) {
    if (/\s/.test(ch)) n += (ch === ' ' ? .3 : 0);
    else if (/[\u2E80-\u9FFF\uF900-\uFAFF\uFF00-\uFFEF\u3000-\u303F]/.test(ch)) n += 1;
    else n += .58;
  }
  return n;
}

/**
 * 格子裡字級相對於格子邊長的比例。
 *
 * 固定比例做不到:短字的棋盤(「喝一口」)給 .19 才不會顯得空,而 30 字的格子
 * 用 .19 會直接被裁掉一半。所以照這張棋盤**實際的字量**決定,由 JS 寫進
 * --sq-text-factor,CSS 拿它算 font-size。
 *
 * 數字是從「n 行 × 每行幾字 ≥ 字數」推回來的:可用高度 ÷ (1.3 × 字級) 是行數,
 * 可用寬度 ÷ 字級 是每行字數。
 */
function textFactorFor(squares) {
  /* SQUARES_DATA 是以 position 當 key 的。position 剛好是 0..n-1 的時候 json_encode
     給出陣列,中間有缺號就變成物件 —— 直接 .map 會 TypeError,而那會讓整張棋盤
     畫不出來(比字太小嚴重得多)。 */
  const list = Array.isArray(squares) ? squares : Object.values(squares || {});
  const lengths = list
    .map(sq => (sq && sq.text ? visualLength(sq.text) : 0))
    .sort((a, b) => b - a);
  if (lengths.length === 0) return .19;

  // 用第二長的字量當基準:只有一格特別長的話,不該讓整盤的字都跟著縮
  const longest = lengths[Math.min(1, lengths.length - 1)];

  /* 門檻是從容量公式回推的,不是憑感覺調的:
       每行字數 ≈ (格子邊長 - 內距) / 字級
       行數     ≈ (格子邊長 - 內距) / (1.28 × 字級)
     兩個相乘要 ≥ 字數,而且要留約 1.3 倍的餘裕給「行末塞不下就換行」的截斷。
     解出來 字級 ≤ (邊長 - 9) / √(1.28 × 字數 × 1.3)。

     注意容量跟格子大小**無關** —— 寬高一起放大時字級也一起放大,能放的字數
     是比例的函數。所以格子變大只會讓同樣的字變好讀,不會讓更多字放得進去。 */
  if (longest <= 12) return .19;
  if (longest <= 20) return .15;
  if (longest <= 30) return .125;

  return .11;
}

function sizeGameBoard(board, cols, rows) {
  const wrap = board.closest('.board-wrap');
  const ar = cols / rows;
  const size = BOARD_SIZES[boardSizePref()];
  /* 桌機的寬度上限。放大組把它從一律 960px 拉開 —— 13 欄的棋盤在 960px 下每格
     只剩 73px、字被上限卡在 11px 還放不完,欄數多的棋盤本來就需要更寬的版面。 */
  let maxW = Math.min(window.innerWidth * 0.96, cols >= 12 ? size.maxWWide : size.maxW);
  let maxH = window.innerHeight - 205; // fallback if wrap not measurable yet
  if (wrap) {
    const cs = getComputedStyle(wrap);
    maxW = Math.min(maxW, wrap.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight));
    maxH = wrap.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
  }
  let bw = maxW, bh = bw / ar;
  if (bh > maxH) { bh = maxH; bw = bh * ar; }

  /* 格子有一個可讀的下限。手機上把整張棋盤塞進畫面,13 欄的棋盤每格只剩
     二十幾 px、字小到 7px —— 那不是「縮小」,是根本看不到。
     低於下限就不再縮,改讓棋盤超出視窗由 .board-wrap 捲動(它本來就是
     overflow:auto),再由 followActivePiece() 自動捲到輪到的那顆棋子。
     寧可要捲動也不要一張讀不了的棋盤。 */
  if (bw / cols < size.minCell) {
    bw = cols * size.minCell;
    bh = rows * size.minCell;
  }

  board.style.width  = Math.floor(bw) + 'px';
  board.style.height = Math.floor(bh) + 'px';
  /* CSS 的 max-width:100% 會把刻意超出視窗的棋盤壓回去,欄寬就又縮小了。
     寬度是這裡算出來的,交給 .board-wrap 捲動即可。 */
  board.style.maxWidth = 'none';
  // 格子邊長給 CSS 用:格子裡的字級跟著它走,見 board.css 的 .sq-text。
  board.style.setProperty('--cell', Math.floor(bw / cols) + 'px');
  // 字級比例照這張棋盤的字量調(見 textFactorFor)
  board.style.setProperty('--sq-text-factor', textFactorFor(window.SQUARES_DATA));
}

/** 輪到的玩家現在應該在的位置:還沒進場是轉盤,進場了是他所在的格子。 */
function activePieceTarget() {
  const board = document.getElementById('game-board');
  const p = state.players[state.current];
  if (!board || !p) return null;

  return isOnTrack(p)
    ? document.getElementById(`sq-${currentPos(p)}`)
    : board.querySelector('.board-entry-wheel');
}

/**
 * 棋盤超出可視範圍時(手機),把輪到的那顆棋子捲進畫面中央。
 *
 * 傳進來的是**目的地元素**(格子或進場轉盤),不是棋子本身:棋子的移動是 CSS
 * transition,剛設好 transform 的那一瞬間 getBoundingClientRect() 拿到的還是舊
 * 位置,照著捲等於捲到它出發前的地方 —— 看起來就像完全沒有跟。
 */
function followActivePiece(target) {
  const wrap = document.querySelector('.board-wrap');
  if (!wrap || !target) return;
  if (wrap.scrollWidth <= wrap.clientWidth && wrap.scrollHeight <= wrap.clientHeight) return;

  const wrapRect = wrap.getBoundingClientRect();
  const rect = target.getBoundingClientRect();

  wrap.scrollTo({
    left: wrap.scrollLeft + (rect.left - wrapRect.left) - (wrap.clientWidth - rect.width) / 2,
    top: wrap.scrollTop + (rect.top - wrapRect.top) - (wrap.clientHeight - rect.height) / 2,
    behavior: 'smooth',
  });
}

/* Re-fit on resize/rotation; piece positions are derived from square rects,
   so re-render them after the board changes size. */
/* 只在寬度改變、或高度大幅改變(轉向、分割畫面)時重算。手機捲動時網址列伸縮
   也會觸發 resize(高度差約 50–120px),每次都重算的話棋盤會跟著放大縮小、
   左右晃動 —— 自動跟隨棋子一捲動就會發生。 */
let lastFitW = window.innerWidth, lastFitH = window.innerHeight;
window.addEventListener('resize', () => {
  if (window.EDIT_MODE || !lastGrid) return;
  const w = window.innerWidth, h = window.innerHeight;
  if (w === lastFitW && Math.abs(h - lastFitH) < 150) return;
  lastFitW = w; lastFitH = h;
  updateHeaderVar();
  const board = document.getElementById('game-board');
  if (!board) return;
  sizeGameBoard(board, lastGrid.cols, lastGrid.rows);
  if (typeof renderPieces === 'function') renderPieces();
});

function buildBoard() {
  updateHeaderVar();
  const board = document.getElementById('game-board');
  if (!board) return;
  board.innerHTML = '';

  let rows = window.CANVAS_ROWS || 11;
  let cols = window.CANVAS_COLS || 13;
  const sqData     = window.SQUARES_DATA || {};
  const isEditMode = window.EDIT_MODE;

  // Auto-shrink: in play mode, detect actual used bounding box and offset squares
  let rowOffset = 0, colOffset = 0;
  if (!isEditMode) {
    let minR = Infinity, maxR = 0, minC = Infinity, maxC = 0;
    Object.values(sqData).forEach(sq => {
      if (!sq.grid_row || !sq.grid_col) return;
      if (sq.grid_row < minR) minR = sq.grid_row;
      if (sq.grid_row > maxR) maxR = sq.grid_row;
      if (sq.grid_col < minC) minC = sq.grid_col;
      if (sq.grid_col > maxC) maxC = sq.grid_col;
    });
    if (minR !== Infinity) {
      rowOffset = minR - 1;
      colOffset = minC - 1;
      rows = maxR - minR + 1;
      cols = maxC - minC + 1;
    }
  }

  board.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
  board.style.gridTemplateRows    = `repeat(${rows}, 1fr)`;

  // Calculate board dimensions to fit within the wrap's real free space —
  // guessing "viewport - 140" undersized the reserved space (header 61 +
  // player bar 120 + padding) and the board's top row ended up hidden
  // under the player bar.
  if (!isEditMode) {
    sizeGameBoard(board, cols, rows);
    lastGrid = { cols: cols, rows: rows };
  } else {
    board.style.aspectRatio = `${cols} / ${rows}`;
  }

  /* 箭頭:每條會被走到的路線各算一次,同一格方向不一樣時取多數。四色飛行棋的閘門
     那一格,只有一個顏色要轉進家門、其他三色繼續繞外圈 —— 多數決會畫「繼續繞」,
     家門裡的每一格則各自有箭頭。 */
  const routes = allRoutes();
  const votes = {};
  routes.forEach(r => {
    const m = computeArrowMap(r, sqData);
    Object.keys(m).forEach(k => { (votes[k] = votes[k] || {})[m[k]] = ((votes[k] || {})[m[k]] || 0) + 1; });
  });
  const arrowMap = {};
  Object.keys(votes).forEach(k => {
    arrowMap[k] = Object.keys(votes[k]).sort((a, b) => votes[k][b] - votes[k][a])[0];
  });
  // 只出現在某一個座位路線裡的格子(那個顏色的家門),標上座位色
  const seatLanes = {};
  const seatsPd = (window.PATH_DATA || {}).seats;
  if (!isEditMode && Array.isArray(seatsPd) && seatsPd.length > 1) {
    seatsPd.forEach((r, i) => (r || []).forEach(pos => {
      const inOthers = seatsPd.some((o, j) => j !== i && (o || []).indexOf(pos) !== -1);
      if (!inOthers) seatLanes[pos] = i + 1;
    }));
  }

  Object.entries(sqData).forEach(([posStr, sq]) => {
    const pos = parseInt(posStr, 10);
    if (!sq.grid_row || !sq.grid_col) return;

    const div = document.createElement('div');
    div.className        = `board-sq color-${sq.color}` + (seatLanes[pos] ? ` lane-p${seatLanes[pos]}` : '');
    div.id               = `sq-${pos}`;
    div.style.gridRow    = sq.grid_row - rowOffset;
    div.style.gridColumn = sq.grid_col - colOffset;

    const flyBadge = sq.fly_to != null
      ? `<div class="sq-fly-badge">✈→${sq.fly_to}</div>` : '';
    const arrow    = arrowMap[pos] ? `<div class="sq-arrow">${arrowMap[pos]}</div>` : '';

    // 遊玩畫面才放類型圖示;編輯器仍用顏色(那裡要一眼看出每格設成哪一類)
    const typeIcon = (!isEditMode && sqTypeIconHtml(sq.color))
      ? `<span class="sq-type" aria-hidden="true">${sqTypeIconHtml(sq.color)}</span>` : '';

    div.innerHTML = `
      <div class="sq-num">${pos}</div>
      ${typeIcon}
      <div class="sq-text">${escHtml(sq.text)}</div>
      ${flyBadge}
      ${arrow}
      ${isEditMode ? '<span class="edit-icon">✏</span>' : ''}
    `;

    if (isEditMode) {
      div.addEventListener('click', () => openSqModal(pos));
    } else {
      /* 遊玩模式:點格子看完整內容。方形格子放不下長文字是幾何限制(見
         textFactorFor 的說明),所以一定要有一個看得到全文的地方。
         也給鍵盤使用者一個入口 —— 只有滑鼠能開的資訊等於沒有。 */
      div.tabIndex = 0;
      div.setAttribute('role', 'button');
      div.title = sq.text || '';
      div.addEventListener('click', () => openSqInfo(pos));
      div.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openSqInfo(pos); }
      });
    }
    board.appendChild(div);
  });

  // 進場轉盤:優先佔 3×3 格(放得下六段文字),擠不下才退回 2×2 只放轉盤圖形。
  // 自訂棋盤不保證有 3×3 的空白,所以要有退路。
  let wheelSlot = null, wheelSpan = 0;
  if (!isEditMode && startWheel()) {
    [3, 2].some(function (size) {
      const slot = findWheelSlot(sqData, rowOffset, colOffset, rows, cols, size);
      if (slot) { wheelSlot = slot; wheelSpan = size; }
      return !!slot;
    });
  }
  if (wheelSlot) {
    const segs = startWheel();
    const wheelEl = document.createElement('div');
    wheelEl.className        = 'board-entry-wheel' + (wheelSpan < 3 ? ' bew-compact' : '');
    wheelEl.style.gridRow    = wheelSlot.r + ' / span ' + wheelSpan;
    wheelEl.style.gridColumn = wheelSlot.c + ' / span ' + wheelSpan;
    // title 留著:文字長度上限是 60 字,格子裡放不下的部分靠它補完。
    wheelEl.title = segs.map(function (seg, i) {
      return (i + 1) + '. ' + seg.text;
    }).join('\n');

    // 文字直接畫在扇形裡(見 wheelSvg),所以不再需要另外一份圖例 —— 那份圖例
    // 佔掉一半空間,轉盤反而被壓小,兩邊都看不清楚。
    wheelEl.innerHTML = `<div class="bew-graphic">${wheelSvg(null)}</div>`
      + `<div class="bew-label">${escHtml(tp('startWheel'))}</div>`;
    board.appendChild(wheelEl);
  }
  const WHEEL_SPAN = wheelSpan;

  // Center banner + corner decos only on default 11×13 cross board
  const origRows = window.CANVAS_ROWS || 11;
  const origCols = window.CANVAS_COLS || 13;
  if (origRows === 11 && origCols === 13 && rowOffset === 0 && colOffset === 0) {
    const center = document.createElement('div');
    center.className        = 'board-center';
    center.style.gridRow    = '6';
    center.style.gridColumn = '2 / 13';
    center.innerHTML = `
      <div class="center-title">${escHtml(tp('centerTitle'))}</div>
      <div class="center-rules-inline">${escHtml(tp('centerRules'))}</div>
    `;
    board.appendChild(center);

    const cornerData = [
      {row:'1/5',col:'1/5',  icon:'dice',  sub:tp('corner1')},
      {row:'1/5',col:'8/14', icon:'heart', sub:tp('corner2')},
      {row:'8/12',col:'1/5', icon:'cup',   sub:tp('corner3')},
      {row:'8/12',col:'8/14',icon:'trophy',sub:tp('corner4')},
    ];
    cornerData.forEach(c => {
      // 轉盤佔到的那一角就不畫裝飾,兩個疊在一起會糊成一團。
      if (wheelSlot
          && gridSpecOverlaps(c.row, wheelSlot.r, wheelSlot.r + WHEEL_SPAN - 1)
          && gridSpecOverlaps(c.col, wheelSlot.c, wheelSlot.c + WHEEL_SPAN - 1)) {
        return;
      }
      const el = document.createElement('div');
      el.className = 'board-corner-deco';
      el.style.gridRow    = c.row;
      el.style.gridColumn = c.col;
      el.innerHTML = `<div class="corner-icon ic-${c.icon}">${svgIcon(c.icon)}</div><div class="corner-sub">${escHtml(c.sub)}</div>`;
      board.appendChild(el);
    });
  }

  if (!isEditMode && state.players.length) renderPieces();
}

/* ── Piece tokens ──
   Pieces are appended directly to #game-board (not into the square div)
   and repositioned with a CSS transform, so moving from square to square
   is a smooth slide (with a slight overshoot/bounce from the transition
   easing) instead of a DOM teardown + rebuild on every step. */


/**
 * 把棋子放到任何一個目標元素的中心(格子,或還沒進場時的進場轉盤)。
 *
 * sizeRef 決定棋子畫多大,跟「放在哪」分開:等待中的棋子停在 3×3 的轉盤上,
 * 拿轉盤的邊長去算會得到三倍大的棋子。棋子的大小應該一直是「一格」的大小,
 * 不管它現在停在什麼東西上面。
 */
/* 同一格有幾個人,就排成幾個位置:1 人置中、2 人並排、3 人三角、4 人 2×2。
   排的是「這一格裡的第幾個」,不是玩家編號 —— 照玩家編號偏移的話,1 號和 4 號
   同格時會落在對角,中間空一大塊,2、3 號同格又疊在同一側。 */
const PIECE_SLOTS = {
  1: [[0, 0]],
  2: [[-1, 0], [1, 0]],
  3: [[-1, -0.85], [1, -0.85], [0, 0.95]],
  4: [[-1, -1], [1, -1], [-1, 1], [1, 1]],
};
/* 人越多棋子越小,四顆都要完整留在格子裡(最外緣約 0.38 格) */
const PIECE_SCALE = { 1: 0.5, 2: 0.4, 3: 0.35, 4: 0.34 };

function positionPiece(el, target, board, slotIndex = 0, sizeRef = null, occupants = 1) {
  const boardRect = board.getBoundingClientRect();
  const rect      = target.getBoundingClientRect();
  const sizeRect  = (sizeRef || target).getBoundingClientRect();
  const n = Math.max(1, Math.min(4, occupants));
  const size = Math.max(8, Math.min(sizeRect.width, sizeRect.height) * PIECE_SCALE[n]);
  el.style.width  = size + 'px';
  el.style.height = size + 'px';
  el.style.setProperty('--size', size + 'px');   // 3D 棋子用它算每一片的高度(translateZ 不能用 %)

  const [ox, oy] = PIECE_SLOTS[n][slotIndex % n];
  const nudge = size * 0.62;
  const cx = (rect.left - boardRect.left) + rect.width  / 2 + ox * nudge;
  const cy = (rect.top  - boardRect.top)  + rect.height / 2 + oy * nudge;
  el.style.transform = `translate(${cx - size / 2}px, ${cy - size / 2}px)`;
}

/**
 * 還沒進場的棋子擺在轉盤上。
 *
 * 不是擺在圓心 —— 幾顆棋子疊在中間看起來像一坨,也看不出有幾個人在等。
 * 沿著盤面繞一圈排開,每個人一個固定角度,才像「棋子站在轉盤上等著上場」。
 *
 * 半徑取在盤緣。棋子的直徑大約是轉盤的五分之一,擺在盤面內側不管放哪個半徑
 * 都會蓋掉東西 —— 內圈是點數數字,中圈是扇形文字。擺在邊緣只會壓到扇形最外側
 * 的空白處,數字與文字都還看得見,而且「站在轉盤邊上等著上場」也比較像那麼回事。
 */
function positionPieceOnWheel(el, wheelEl, board, index, total, sizeRef) {
  const svg = wheelEl.querySelector('svg') || wheelEl;
  const boardRect = board.getBoundingClientRect();
  const rect = svg.getBoundingClientRect();
  const sizeRect = (sizeRef || wheelEl).getBoundingClientRect();

  const size = Math.max(10, Math.min(sizeRect.width, sizeRect.height) * 0.5);
  el.style.width  = size + 'px';
  el.style.height = size + 'px';
  el.style.setProperty('--size', size + 'px');

  const wheelR = Math.min(rect.width, rect.height) / 2;
  const ring = wheelR * 0.88;
  /* 從正左方開始平均分配。從正上方起算的話,兩人時會落在十二點與六點,
     而六點正好是「進場轉盤」那行標籤的位置。左右兩側是最空的地方。 */
  const angle = Math.PI + (index / Math.max(1, total)) * Math.PI * 2;

  const cx = (rect.left - boardRect.left) + rect.width / 2 + ring * Math.cos(angle);
  const cy = (rect.top - boardRect.top) + rect.height / 2 + ring * Math.sin(angle);
  el.style.transform = `translate(${cx - size / 2}px, ${cy - size / 2}px)`;
}

/** 把棋子移到轉盤上某一個扇形的位置(擲出點數之後)。 */
function positionPieceOnFace(el, wheelEl, board, face, sizeRef) {
  const svg = wheelEl.querySelector('svg') || wheelEl;
  const boardRect = board.getBoundingClientRect();
  const rect = svg.getBoundingClientRect();
  const sizeRect = (sizeRef || wheelEl).getBoundingClientRect();

  const size = Math.max(10, Math.min(sizeRect.width, sizeRect.height) * 0.5);
  el.style.width = size + 'px';
  el.style.height = size + 'px';

  // 扇形的中線。和 wheelSvg 用同一組算式:第一片從正上方開始,順時針每片 60 度。
  const slice = Math.PI * 2 / 6;
  const angle = -Math.PI / 2 + (face - 1) * slice + slice / 2;
  const ring = Math.min(rect.width, rect.height) / 2 * 0.88;

  const cx = (rect.left - boardRect.left) + rect.width / 2 + ring * Math.cos(angle);
  const cy = (rect.top - boardRect.top) + rect.height / 2 + ring * Math.sin(angle);
  el.style.transform = `translate(${cx - size / 2}px, ${cy - size / 2}px)`;
}

const STEP_MS = 240;

/** 棋子跳一下(移動時每一格一次)。動畫在 board.css 的 .is-hopping,減少動態效果時不跳。 */
function hopPiece(el) {
  el.classList.remove('is-hopping');
  void el.offsetWidth;   // 重新觸發同一個動畫
  el.classList.add('is-hopping');
  clearTimeout(el._hopT);
  el._hopT = setTimeout(() => el.classList.remove('is-hopping'), STEP_MS + 20);
}

function renderPieces() {
  const board = document.getElementById('game-board');
  if (!board) return;
  const wheelEl = board.querySelector('.board-entry-wheel');
  // 棋子一律用格子的尺寸;沒有格子可量(理論上不會發生)才退回目標元素。
  const cellRef = board.querySelector('.board-sq');
  let activeTarget = null;

  /* 先數每一格上有誰,棋子才知道要縮多小、排在第幾個位置 */
  const occupancy = {};
  state.players.forEach((p, i) => {
    if (!isOnTrack(p)) return;
    const key = currentPos(p);
    (occupancy[key] = occupancy[key] || []).push(i);
  });

  state.players.forEach((p, i) => {
    let el = document.getElementById(`piece-${i+1}`);

    /* 還沒進場的棋子擺在進場轉盤上 —— 它們確實「在轉盤那邊等著」,
       藏起來的話玩家看不出自己還沒上場,也看不出還有誰在等。
       沒有轉盤可停(理論上不會發生:沒轉盤就不會有人未進場)才藏。 */
    const waiting = !isOnTrack(p);
    if (waiting && !wheelEl) {
      if (el) el.classList.add('piece-waiting');
      return;
    }

    const target = waiting ? wheelEl : document.getElementById(`sq-${currentPos(p)}`);
    if (!target) return;
    if (i === state.current) activeTarget = target;
    const isNew = !el;
    if (isNew) {
      el = document.createElement('div');
      el.className = `piece-token piece-${i+1}`;
      el.id        = `piece-${i+1}`;
      // 立體棋子／愛心的圖形。圓片樣式時由 CSS 藏起來,切換樣式不用重建棋子
      el.innerHTML = pieceShapeSvg(i + 1);
      board.appendChild(el);
    }
    el.classList.remove('piece-waiting');
    el.classList.toggle('piece-on-wheel', waiting);

    const place = () => waiting
      ? positionPieceOnWheel(el, wheelEl, board, i, state.players.length, cellRef)
      : positionPiece(el, target, board,
          occupancy[currentPos(p)].indexOf(i), cellRef, occupancy[currentPos(p)].length);

    const where = waiting ? 'wheel' : String(currentPos(p));
    if (isNew) {
      // Snap into place on first placement (setup/reset/rebuild) instead
      // of visibly sliding in from the top-left corner.
      el.style.transition = 'none';
      place();
      void el.offsetWidth; // force reflow so the transition-less transform commits
      el.style.transition = '';
    } else {
      place();
      // 換了格子就跳一下:一步一格地跳過去,而不是整顆滑過去
      if (el.dataset.at !== where) hopPiece(el);
    }
    el.dataset.at = where;
    el.classList.toggle('is-active', i === state.current && !state.gameOver);
  });

  // 棋盤放不進畫面時(手機),跟著輪到的那顆棋子捲動。
  followActivePiece(activeTarget);
}

/* ═══════════════════════════════════════════════════
   CONTENT EDIT MODE — Square Modal
   ═══════════════════════════════════════════════════ */
let editPos = -1;

function openSqModal(pos) {
  editPos = pos;
  const sq = getSq(pos);

  document.getElementById('sq-pos-label').textContent = `#${pos}`;
  const ta = document.getElementById('sq-text');
  ta.value = sq.text || '';
  document.getElementById('sq-char').textContent = ta.value.length;

  const radios = document.querySelectorAll('input[name="sq-color"]');
  let matched = false;
  radios.forEach(r => { r.checked = r.value === (sq.color||'normal'); if(r.checked) matched=true; });
  if (!matched && radios.length) radios[radios.length-1].checked = true;

  const flyInput = document.getElementById('sq-fly-to');
  if (flyInput) flyInput.value = sq.fly_to != null ? sq.fly_to : '';

  const stepsInput = document.getElementById('sq-move-steps');
  if (stepsInput) stepsInput.value = sq.move_steps != null ? sq.move_steps : '';
  const skipInput = document.getElementById('sq-skip-turn');
  if (skipInput) skipInput.checked = !!sq.skip_turn;

  const st = document.getElementById('sq-save-status');
  st.textContent = '';
  st.style.color = '#5fd080';
  document.getElementById('sq-modal').classList.add('open');
}

function closeSqModal() {
  document.getElementById('sq-modal').classList.remove('open');
  editPos = -1;
}

async function saveSquare() {
  if (editPos < 0) return;
  const text   = document.getElementById('sq-text').value;
  const color  = document.querySelector('input[name="sq-color"]:checked')?.value || 'normal';
  const flyVal = document.getElementById('sq-fly-to')?.value.trim();
  const fly_to = flyVal !== '' ? parseInt(flyVal,10) : null;
  const stepsVal   = document.getElementById('sq-move-steps')?.value.trim();
  const move_steps = stepsVal ? parseInt(stepsVal,10) : null;
  const skip_turn  = !!document.getElementById('sq-skip-turn')?.checked;
  const status = document.getElementById('sq-save-status');
  status.style.color='#5fd080'; status.textContent=tp('saving');
  try {
    const res = await fetch(`${window.BOARD_ROUTES.squares}/${editPos}`, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN},
      body:JSON.stringify({text,color,fly_to,move_steps,skip_turn}),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    if (!window.SQUARES_DATA) window.SQUARES_DATA = {};
    Object.assign(window.SQUARES_DATA[editPos], {text,color,fly_to,move_steps,skip_turn});

    const sqEl = document.getElementById(`sq-${editPos}`);
    if (sqEl) {
      sqEl.className = `board-sq color-${color}`;
      const textEl = sqEl.querySelector('.sq-text');
      if (textEl) textEl.innerHTML = escHtml(text);
      let flyBadge = sqEl.querySelector('.sq-fly-badge');
      if (fly_to != null) {
        if (!flyBadge) { flyBadge=document.createElement('div'); flyBadge.className='sq-fly-badge'; sqEl.insertBefore(flyBadge,sqEl.querySelector('.sq-arrow')||null); }
        flyBadge.textContent = `✈→${fly_to}`;
      } else if (flyBadge) flyBadge.remove();
    }
    status.textContent=tp('saved');
    setTimeout(closeSqModal, 900);
  } catch(err) {
    status.style.color='#f06080'; status.textContent=tp('saveFailed');
    console.error('saveSquare:',err);
  }
}

function openBoardMeta() {
  document.getElementById('meta-name').value = window.BOARD_NAME || '';
  document.getElementById('meta-desc').value = window.BOARD_DESC || '';
  const playersEl = document.getElementById('meta-players');
  if (playersEl) playersEl.value = String(window.BOARD_PLAYERS || 2);
  document.getElementById('meta-modal').classList.add('open');
}
function closeMetaModal() { document.getElementById('meta-modal').classList.remove('open'); }
async function saveMeta() {
  const name = document.getElementById('meta-name').value.trim();
  const desc = document.getElementById('meta-desc').value.trim();
  const playersEl = document.getElementById('meta-players');
  const players = playersEl ? parseInt(playersEl.value, 10) : undefined;
  if (!name) return;
  try {
    const res = await fetch(window.BOARD_ROUTES.update, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN},
      body:JSON.stringify({name,description:desc,recommended_players:players}),
    });
    if (!res.ok) throw new Error();
    window.BOARD_NAME = name;
    if (players) window.BOARD_PLAYERS = players;
    const d = document.getElementById('board-name-display');
    if (d) d.textContent = name;
    closeMetaModal();
  } catch { alert(tp('saveFailed')); }
}

/* ═══════════════════════════════════════════════════
   PLAY MODE — Setup
   ═══════════════════════════════════════════════════ */
function startSetup() {
  /* 1–4 人,各自一顆棋子,不分組(見 teamOf)。 */
  const count = Math.max(1, Math.min(4, window.PLAYER_COUNT || 1));
  state.players = [];
  for (let n = 1; n <= count; n++) {
    const nameEl = document.getElementById('setup-p' + n);
    const gEl = document.querySelector('input[name="p' + n + '-gender"]:checked');
    state.players.push({
      name: (nameEl && nameEl.value.trim()) || ('P' + n),
      stepIndex: 0, skip: false, finished: false,
      /* With a wheel, nobody is on the track until they roll an `enter` slot. */
      entered: !startWheel(),
      seat: n - 1,   // 座位路線用(見 getPlayerPath);跟棋子顏色 .piece-N 同一個編號
      gender: (gEl && gEl.value) || (n % 2 === 1 ? 'male' : 'female')
    });
  }
  state.current = 0; state.rolling = false; state.gameOver = false;
  state.finishOrder = [];

  /* 開局視窗的「追上別人時對方回起點」。預設是棋盤作者的設定(CAPTURE_ON),
     玩家可以在開局前改。captureAt() 讀的就是 window.CAPTURE_ON。 */
  const styleEl = document.querySelector('input[name="piece-style"]:checked');
  applyPieceStyle(styleEl ? styleEl.value : pieceStylePref());

  const capEl = document.querySelector('input[name="capture-rule"]:checked');
  if (capEl) window.CAPTURE_ON = capEl.value === 'on';

  const gIcon = g => g === 'male' ? ' \u2642' : ' \u2640';
  state.players.forEach(function(p, i) {
    const nm = document.getElementById('p' + (i + 1) + '-name');
    const ps = document.getElementById('p' + (i + 1) + '-pos');
    if (nm) nm.textContent = p.name + gIcon(p.gender);
    if (ps) ps.textContent = tp('startPoint');
    document.getElementById('p' + (i + 1) + '-panel')?.classList.remove('finished');
  });

  closeModal('setup-modal');
  buildBoard();
  build3dCube();
  updateTurnUI();
}

/* ── 分組與追趕 ─────────────────────────────────────────────
   不分組:每個人自成一組,先到終點的人贏(2026-09 使用者要求拿掉 V8.0 的兩人一組 ——
   3 人時會多出一個只有一人的「第二組」)。teammatesOf() 因此永遠是空陣列,
   arriveAtEnd() 的「等夥伴」與「終點特權」自然不會觸發。 */
function teamOf(idx) {
  return idx;
}
function teammatesOf(idx) {
  const t = teamOf(idx);
  return state.players.map(function(_, i) { return i; })
    .filter(function(i) { return i !== idx && teamOf(i) === t; });
}

/* 追趕驅逐:檢查「所有」其他玩家,不只下一位。
   原本只比對 (current+1) % length —— 那在 3–4 人時會漏掉其他對手。
   已抵達終點的棋子不再被驅逐。 */
function captureAt(moverIdx) {
  /* Per-board rule: some boards turn eviction off entirely. */
  if (window.CAPTURE_ON === false) return false;
  const mover = state.players[moverIdx];
  if (!mover || mover.stepIndex <= 0 || !isOnTrack(mover)) return false;
  const pos = currentPos(mover);
  let hit = false;
  state.players.forEach(function(other, i) {
    if (i === moverIdx || other.finished || !isOnTrack(other)) return;
    if (other.stepIndex > 0 && currentPos(other) === pos) {
      other.stepIndex = 0;
      hit = true;
    }
  });
  if (hit) { renderPieces(); updatePosDisplay(); }
  return hit;
}


function rollDice() {
  if (state.rolling || state.gameOver) return;
  const player = state.players[state.current];

  if (player.skip) {
    player.skip = false;
    document.getElementById('action-dice').textContent = '-';
    updateActionDiceFace(0);
    document.getElementById('action-text').textContent = tp('skipTurnName', { '__NAME__': player.name });
    document.getElementById('action-color-bar').style.background = '#9e9e9e';
    document.getElementById('skip-notice').classList.remove('hidden');
    document.getElementById('gender-notice').classList.add('hidden');
    showFlyButtons(false, null);
    openModal('action-modal');
    return;
  }

  state.rolling = true;
  document.getElementById('roll-btn').disabled = true;

  // Use 3D dice animation
  roll3dDice().then(function(roll) {
    // Update player bar dice display
    const diceEl = document.getElementById('dice');
    if (diceEl) diceEl.innerHTML = diceFaceHtml(roll);

    /* Still off the track → the roll is read off the entry wheel, not walked. */
    if (!isOnTrack(player)) {
      spinEntryWheel(player, roll).then(function () { state.rolling = false; });
      return;
    }

    // Animate piece movement step by step
    animateMove(roll).then(function() {
      state.rolling = false;
    });
  });
}

/** Animate piece movement step-by-step, then show action modal */
function animateMove(roll) {
  return new Promise(function(resolve) {
    const player  = state.players[state.current];
    const path    = getPlayerPath(player);
    const endIdx  = path.length - 1;
    const startIdx = player.stepIndex;
    const rawNext = startIdx + roll;

    /* Win: overshoot or land on end */
    if (rawNext >= endIdx) {
      animateSteps(player, startIdx, endIdx, function() {
        updatePosDisplay();
        setTimeout(function() { arriveAtEnd(state.current); }, 400);
        resolve();
      });
      return;
    }

    animateSteps(player, startIdx, rawNext, function() {
      const pos = currentPos(player);
      updatePosDisplay();

      /* Collision */
      captureAt(state.current);

      const sq = getSq(pos);
      if (sq.color === 'move') {
        applyMoveEffect(sq, function() {
          const finalPos = currentPos(player);
          /* Collision check after move effect */
          captureAt(state.current);

          /* A 前進 square can carry the piece onto the last step. The fly and
             partner-bonus paths already end the game there; this one used to
             fall through to the action modal, leaving the player parked on the
             end square and still taking turns. */
          if (player.stepIndex >= endIdx) {
            updatePosDisplay();
            setTimeout(function() { arriveAtEnd(state.current); }, 400);
            resolve();
            return;
          }

          setTimeout(function() { showActionModal(roll, finalPos); resolve(); }, 200);
        });
        return;
      }

      setTimeout(function() {
        showActionModal(roll, pos);
        resolve();
      }, 200);
    });
  });
}

/** Animate piece moving one square at a time */
function animateSteps(player, fromIdx, toIdx, callback) {
  if (fromIdx >= toIdx) {
    callback();
    return;
  }
  let step = fromIdx;
  function nextStep() {
    step++;
    player.stepIndex = step;
    renderPieces();
    const pos = currentPos(player);
    flashSquare(pos);
    // 一格的時間 = 一跳的時間(board.css 的 piece-hop／p3d-hop),跳完落地才走下一格
    if (step >= toIdx) {
      setTimeout(callback, STEP_MS);
    } else {
      setTimeout(nextStep, STEP_MS);
    }
  }
  nextStep();
}

function applyMoveEffect(sq, callback) {
  callback = callback || function(){};
  const player = state.players[state.current];
  const path   = getPlayerPath(player);
  /* Structured fields win; the zh-TW text patterns are only a fallback for
     squares saved before move_steps/skip_turn existed. Parsing the wording is
     locale-bound — 前进 (zh-CN), マス, "Move forward" all fail to match. */
  const eff  = squareEffect(sq);

  if (eff.skip) player.skip = true;

  if (eff.steps > 0) {
    const from = player.stepIndex;
    const to = Math.min(path.length-1, player.stepIndex + eff.steps);
    animateSteps(player, from, to, function() { updatePosDisplay(); callback(); });
  } else if (eff.steps < 0) {
    const from = player.stepIndex;
    const to = Math.max(0, player.stepIndex + eff.steps);
    animateStepsBackward(player, from, to, function() { updatePosDisplay(); callback(); });
  } else {
    callback();
  }
}

/** Resolve a square's movement effect: { steps, skip }. */
function squareEffect(sq) {
  const steps = parseInt(sq.move_steps, 10) || 0;
  const skip  = !!sq.skip_turn;

  /* skip_turn defaults to false rather than null, so "are the structured fields
     present" is not a usable test — check whether they actually say anything. */
  if (steps !== 0 || skip) return { steps: steps, skip: skip };

  const text = sq.text || '';
  const fwd  = text.match(/前進\s*(\d+)\s*格/);
  const bwd  = text.match(/後退\s*(\d+)\s*格/);

  return {
    steps: fwd ? parseInt(fwd[1], 10) : (bwd ? -parseInt(bwd[1], 10) : 0),
    skip: /跳過/.test(text),
  };
}

/** Animate piece moving backward one square at a time */
function animateStepsBackward(player, fromIdx, toIdx, callback) {
  if (fromIdx <= toIdx) {
    callback();
    return;
  }
  let step = fromIdx;
  function nextStep() {
    step--;
    player.stepIndex = step;
    renderPieces();
    const pos = currentPos(player);
    flashSquare(pos);
    if (step <= toIdx) {
      setTimeout(callback, 200);
    } else {
      setTimeout(nextStep, 200);
    }
  }
  nextStep();
}

/** Update the dice face in the action modal */
function updateActionDiceFace(n) {
  const el = document.getElementById('action-dice-face');
  if (!el) return;
  if (n > 0) {
    el.innerHTML = diceFaceHtml(n, 'dice-face-flat large');
  } else {
    el.innerHTML = '';
  }
}

function showActionModal(roll, pos) {
  const player   = state.players[state.current];
  const sq       = getSq(pos);
  const genderEl = document.getElementById('gender-notice');
  const skipNote = document.getElementById('skip-notice');
  const textEl   = document.getElementById('action-text');

  document.getElementById('action-dice').textContent = roll;
  updateActionDiceFace(roll);
  document.getElementById('action-color-bar').style.background = COLOR_HEX[sq.color]||COLOR_HEX.normal;
  skipNote.classList.add('hidden'); genderEl.classList.add('hidden');

  const seatIdx = SEAT_COLORS.indexOf(sq.color);
  const genderMismatch =
    (sq.color==='male'   && player.gender!=='male') ||
    (sq.color==='female' && player.gender!=='female') ||
    (seatIdx >= 0 && seatIdx !== state.current);

  if (genderMismatch) {
    const label = seatIdx >= 0
      ? (state.players[seatIdx] ? state.players[seatIdx].name : tp('sq_' + sq.color))
      : (sq.color==='male' ? tp('male') : tp('female'));
    textEl.textContent = sq.text || '';
    genderEl.textContent = tp('genderSkip', { '__LABEL__': label, '__NAME__': player.name });
    genderEl.classList.remove('hidden');
    showFlyButtons(false, null);
  } else {
    textEl.textContent = sq.text || tp('normalSquare');
    /* 飛只往前:座位路線各自從不同的閘門出發,同一個飛躍格對某些顏色是「飛回頭」——
       那種就不給飛(原版的飛躍格本來也只對特定顏色有效) */
    const flyPath = getPlayerPath(player);
    const hasFly = sq.fly_to != null && flyPath.indexOf(sq.fly_to) > player.stepIndex;
    showFlyButtons(hasFly, hasFly ? sq.fly_to : null);
  }

  if (player.skip) skipNote.classList.remove('hidden');
  openModal('action-modal');
}

/* ── Entry wheel ─────────────────────────────────────────────────────────
   Rendered as a six-slice SVG pie with the rolled slice pulled out, mirroring
   the wheel printed in each corner of the physical board. */
/* 把一段文字切成最多 max 行、每行 per 個字,超出的用省略號收尾。
   扇形裡塞不下整句是必然的 —— 格子文字上限 60 字 —— 所以這裡只求「看得出是什麼」,
   完整內容在擲骰後的轉盤彈窗與 title 裡。 */
function wrapWheelLabel(text, per, max) {
  /* 沒有全形字的(英文翻譯)要照單字斷行 —— 逐字切會把「Take a sip」切成
     「Take a」「sip」還算好,切成「Takea」「sip」就讀不懂了。字母約半個全形字寬,
     所以每行能放的字元數大約是中文的 1.8 倍。 */
  if (!/[\u2E80-\u9FFF\u3040-\u30FF\uFF00-\uFFEF]/.test(String(text || ''))) {
    return wrapLatinLabel(String(text || ''), Math.round(per * 1.8), max);
  }

  const clean = String(text || '').replace(/\s+/g, '');
  const lines = [];

  for (let i = 0; i < clean.length && lines.length < max; i += per) {
    lines.push(clean.substr(i, per));
  }
  if (clean.length > per * max) {
    lines[max - 1] = lines[max - 1].slice(0, per - 1) + '…';
  }
  return lines.length ? lines : [''];
}

function wrapLatinLabel(text, per, max) {
  const words = text.trim().split(/\s+/).filter(Boolean);
  const lines = [];
  let cur = '';
  let i = 0;
  for (; i < words.length && lines.length < max; i++) {
    const next = cur ? cur + ' ' + words[i] : words[i];
    if (next.length <= per || !cur) { cur = next; continue; }
    lines.push(cur);
    cur = words[i];
  }
  const truncated = i < words.length || lines.length >= max;
  if (cur && lines.length < max) lines.push(cur);
  if (!lines.length) return [''];
  if (truncated || lines[lines.length - 1].length > per) {
    const last = lines[lines.length - 1];
    lines[lines.length - 1] = (last.length > per - 1 ? last.slice(0, per - 1) : last) + '…';
  }
  return lines;
}

function wheelSvg(activeFace) {
  const w = startWheel();
  if (!w) return '';
  const R = 72, CX = 80, CY = 80, slice = Math.PI * 2 / 6;
  let out = `<svg viewBox="0 0 160 160" class="wheel-svg" role="img">`;

  w.forEach(function(seg, i) {
    const a0 = slice * i - Math.PI / 2;
    const a1 = a0 + slice;
    const active = (i + 1) === activeFace;
    const r = active ? R : R - 6;
    /* 沒有人擲點數時(棋盤上那顆常駐轉盤)不該有「未中選」的暗色 —— 那個
       .35 是用來反襯中選扇形的,六格全暗只會讓整顆轉盤發灰、字也看不清。 */
    const dim = (activeFace == null) ? .92 : (active ? 1 : .35);
    const x0 = CX + r * Math.cos(a0), y0 = CY + r * Math.sin(a0);
    const x1 = CX + r * Math.cos(a1), y1 = CY + r * Math.sin(a1);
    out += `<path d="M${CX} ${CY} L${x0} ${y0} A${r} ${r} 0 0 1 ${x1} ${y1} Z"`
        +  ` fill="${WHEEL_HEX[i]}" opacity="${dim}"`
        +  ` stroke="rgba(0,0,0,.35)" stroke-width="1"/>`;

    // 數字擺內圈,內容文字擺外圈 —— 兩者都在扇形裡,不用另外做圖例。
    const am = a0 + slice / 2;
    out += `<text x="${CX + 27 * Math.cos(am)}" y="${CY + 27 * Math.sin(am)}"`
        +  ` text-anchor="middle" dominant-baseline="middle"`
        +  ` font-size="10" font-weight="800" fill="#fff" opacity=".9">${i + 1}</text>`;

    /* 文字沿著弧的方向排,不是沿著半徑:半徑方向只有 40 幾單位可用(約 5 個字),
       弧方向在 r=50 處有 50 幾單位,再拆兩行等於容得下 12 個字。
       下半圈的扇形轉過來會上下顛倒,所以再轉 180 度。 */
    const tx = CX + 50 * Math.cos(am), ty = CY + 50 * Math.sin(am);
    let deg = am * 180 / Math.PI + 90;
    const norm = ((deg % 360) + 360) % 360;
    if (norm > 90 && norm < 270) deg += 180;

    const lines = wrapWheelLabel(seg.text, 6, 2);
    out += `<g transform="rotate(${deg.toFixed(1)} ${tx.toFixed(1)} ${ty.toFixed(1)})">`;
    lines.forEach(function (ln, li) {
      const dy = (li - (lines.length - 1) / 2) * 8;
      out += `<text x="${tx.toFixed(1)}" y="${(ty + dy).toFixed(1)}"`
          +  ` text-anchor="middle" dominant-baseline="middle"`
          +  ` font-size="7" font-weight="600" fill="#fff">${escHtml(ln)}</text>`;
    });
    out += `</g>`;
  });

  // activeFace 為 null 時中間不寫字 —— 直接內插會印出字串 "null"。
  // 棋盤上那顆常駐的轉盤就是用 null 呼叫的(還沒有人擲出點數)。
  const face = (activeFace == null) ? '' : activeFace;

  return out + `<circle cx="${CX}" cy="${CY}" r="20" fill="rgba(0,0,0,.55)"/>`
    + `<text x="${CX}" y="${CY}" text-anchor="middle" dominant-baseline="middle"`
    + ` font-size="20" font-weight="800" fill="#fff">${face}</text></svg>`;
}

/* ── 進場轉盤在棋盤上的落點 ──
   轉盤是棋盤的一部分(棋子由它決定從哪裡進場),所以畫在格線裡而不是版面上方。
   找一塊 size×size 的空格,取離起點最近的那塊 —— 起點旁邊才看得出兩者的關係。
   棋盤形狀是使用者自訂的,不能寫死座標;找不到空位就回 null,那時不畫。 */
function findWheelSlot(sqData, rowOffset, colOffset, rows, cols, size) {
  const occupied = new Set();
  let start = null;

  Object.entries(sqData).forEach(function (entry) {
    const sq = entry[1];
    if (!sq.grid_row || !sq.grid_col) return;
    const r = sq.grid_row - rowOffset, c = sq.grid_col - colOffset;
    occupied.add(r + ',' + c);
    if (parseInt(entry[0], 10) === 0) start = { r: r, c: c };
  });

  if (!start) start = { r: (rows + 1) / 2, c: (cols + 1) / 2 };

  let best = null;
  for (let r = 1; r + size - 1 <= rows; r++) {
    for (let c = 1; c + size - 1 <= cols; c++) {
      let free = true;
      for (let dr = 0; dr < size && free; dr++) {
        for (let dc = 0; dc < size && free; dc++) {
          if (occupied.has((r + dr) + ',' + (c + dc))) free = false;
        }
      }
      if (!free) continue;

      const cr = r + (size - 1) / 2, cc = c + (size - 1) / 2;
      const d = (cr - start.r) * (cr - start.r) + (cc - start.c) * (cc - start.c);
      if (!best || d < best.d) best = { r: r, c: c, d: d };
    }
  }
  return best;
}

/* grid-area 的 '1/5' 表示 1..4。判斷裝飾方塊會不會壓到轉盤。 */
function gridSpecOverlaps(spec, from, to) {
  const parts = String(spec).split('/');
  const a = parseInt(parts[0], 10);
  const b = parts[1] ? parseInt(parts[1], 10) - 1 : a;
  return a <= to && b >= from;
}

/** 「→ 第 N 格:內容」。玩家進場後會停在自己路徑的第一格,而路徑可以依性別或
    座位分開設定,所以不同玩家的第一格可能是不同的格子。 */
function entryHint(player) {
  const pos = getPlayerPath(player)[0];
  const text = String(getSq(pos).text || '').split('\n')[0];

  return tp('wheelEnterAt', { '__N__': pos }) + (text ? '：' + text : '');
}

/**
 * 擲出點數之後,棋子直接在轉盤上跑到那一片扇形。
 *
 * 之前是彈一個視窗、按「知道了」才繼續 —— 對一個「轉盤轉出結果」的動作來說,
 * 彈窗把發生的事講了一遍,卻沒有讓人看到它發生。這裡改成看得見的版本:
 * 扇形亮起來、棋子滑過去、標籤說明結果,停一下再結算。
 *
 * 停頓是必要的:結算會重畫棋子(進場的話要移到棋盤上),沒有停頓的話
 * 滑過去的動畫還沒跑完就被下一次重畫蓋掉,看起來像瞬移。
 */
function spinEntryWheel(player, roll) {
  return new Promise(function (resolve) {
    const seg = (startWheel() || [])[roll - 1];
    const board = document.getElementById('game-board');
    const wheelEl = board ? board.querySelector('.board-entry-wheel') : null;

    if (!seg || !wheelEl) { resolveWheel(seg, player); resolve(); return; }

    const graphic = wheelEl.querySelector('.bew-graphic');
    const label = wheelEl.querySelector('.bew-label');
    const piece = document.getElementById(`piece-${state.current + 1}`);

    if (graphic) graphic.innerHTML = wheelSvg(roll);
    if (piece) positionPieceOnFace(piece, wheelEl, board, roll, board.querySelector('.board-sq'));

    if (label) {
      label.classList.add('is-result');
      label.textContent = seg.enter
        ? `${seg.text} ${entryHint(player)}`
        : (seg.reroll ? `${seg.text} ${tp('wheelReroll')}` : `${seg.text} ${tp('wheelStay')}`);
    }

    setTimeout(function () {
      if (graphic) graphic.innerHTML = wheelSvg(null);
      if (label) {
        label.classList.remove('is-result');
        label.textContent = tp('startWheel');
      }
      resolveWheel(seg, player);
      resolve();
    }, 1600);
  });
}

/** 結算轉盤結果:進場、再擲一次,或換人。 */
function resolveWheel(seg, player) {
  if (!seg) { advanceTurn(); return; }

  if (seg.enter) {
    player.entered = true;
    player.stepIndex = 0;
    renderPieces(); updatePosDisplay(); flashSquare(currentPos(player));
    /* Entering onto an occupied start square must not evict anyone — captureAt
       already ignores stepIndex 0, so nothing to do here. */
    advanceTurn();

    return;
  }

  if (seg.reroll) {
    /* Same player rolls again — do not advance the turn. */
    renderPieces();
    updateTurnUI();
    const rb = document.getElementById('roll-btn');
    if (rb) rb.disabled = false;

    return;
  }

  renderPieces();   // 沒進場也沒重擲:棋子回到自己在盤緣的等待位置
  advanceTurn();
}

function showFlyButtons(hasFly, dest) {
  const btnComplete = document.getElementById('btn-complete');
  const flyGroup    = document.getElementById('fly-btn-group');
  const flyDest     = document.getElementById('fly-dest-label');
  if (hasFly && dest != null) {
    btnComplete?.classList.add('hidden');
    flyGroup?.classList.remove('hidden');
    if (flyDest) flyDest.textContent = dest;
  } else {
    btnComplete?.classList.remove('hidden');
    flyGroup?.classList.add('hidden');
  }
}

function confirmAction(choice) {
  const player = state.players[state.current];

  if (choice === 'fly') {
    const pos  = currentPos(player);
    const sq   = getSq(pos);
    if (sq.fly_to != null) {
      const path   = getPlayerPath(player);
      const endIdx = path.length - 1;
      const flyIdx = path.indexOf(sq.fly_to);
      if (flyIdx > player.stepIndex) {
        if (flyIdx >= endIdx) {
          player.stepIndex = endIdx;
          closeModal('action-modal');
          renderPieces(); flashSquare(path[endIdx]); updatePosDisplay();
          setTimeout(() => arriveAtEnd(state.current), 400);
          return;
        }
        player.stepIndex = flyIdx;
        renderPieces(); flashSquare(currentPos(player)); updatePosDisplay();
        /* Collision check after fly */
        captureAt(state.current);
      }
    }
  }

  closeModal('action-modal');
  advanceTurn();
}

function flashSquare(pos) {
  const el = document.getElementById(`sq-${pos}`);
  if (!el) return;
  el.classList.add('highlight');
  setTimeout(() => el.classList.remove('highlight'), 2200);
}

/* ── V8.0 抵達終點 ─────────────────────────────────────────────
   規則 7:同組兩人都進終點才算贏,先到者要等夥伴。
   規則 8:全場第一位抵達者可讓自己的夥伴前進 1–6 格。
   1–2 人時 teamOf() 讓每人自成一組,行為與改動前相同(先到即贏)。 */
function arriveAtEnd(idx) {
  const player = state.players[idx];
  if (player.finished) return;
  player.finished = true;
  state.finishOrder = state.finishOrder || [];
  const isFirstOverall = state.finishOrder.length === 0;
  state.finishOrder.push(idx);

  const panel = document.getElementById('p' + (idx + 1) + '-panel');
  if (panel) panel.classList.add('finished');
  const posEl = document.getElementById('p' + (idx + 1) + '-pos');
  if (posEl) posEl.textContent = tp('endPoint') || tp('startPoint');

  const mates = teammatesOf(idx);

  /* 同組全部抵達 → 該組獲勝 */
  if (mates.every(function(i) { return state.players[i].finished; })) {
    const names = [player.name].concat(mates.map(function(i) { return state.players[i].name; }));
    showWin(names.join(tp('nameJoin') || '、'));
    return;
  }

  /* 規則 8:全場第一位抵達者,可讓夥伴前進 1–6 格 */
  if (isFirstOverall && mates.length) {
    showFinishBonus(idx, mates[0]);
    return;
  }

  /* 還有夥伴沒到 → 換下一位繼續 */
  advanceTurn();
}

/* 終點特權:選 1–6 讓夥伴前進 */
function showFinishBonus(finisherIdx, mateIdx) {
  const box = document.getElementById('bonus-modal');
  if (!box) { advanceTurn(); return; }
  const label = document.getElementById('bonus-text');
  if (label) {
    label.textContent = tp('bonusText', {
      '__NAME__': state.players[finisherIdx].name,
      '__MATE__': state.players[mateIdx].name
    });
  }
  const btns = document.getElementById('bonus-btns');
  if (btns) {
    btns.innerHTML = '';
    for (let n = 1; n <= 6; n++) {
      const b = document.createElement('button');
      b.className = 'btn btn-gold bonus-num';
      b.textContent = n;
      b.onclick = function() { applyFinishBonus(mateIdx, n); };
      btns.appendChild(b);
    }
  }
  openModal('bonus-modal');
}

function applyFinishBonus(mateIdx, steps) {
  closeModal('bonus-modal');
  const mate = state.players[mateIdx];
  const path = getPlayerPath(mate);
  const endIdx = path.length - 1;
  const from = mate.stepIndex;
  const to = Math.min(endIdx, from + steps);
  animateSteps(mate, from, to, function() {
    updatePosDisplay();
    captureAt(mateIdx);
    if (mate.stepIndex >= endIdx) { arriveAtEnd(mateIdx); return; }
    advanceTurn();
  });
}

/* 換手:跳過已抵達終點的玩家 */
function advanceTurn() {
  if (state.gameOver) return;
  const n = state.players.length;
  if (n > 1) {
    let guard = 0;
    do {
      state.current = (state.current + 1) % n;
      guard++;
    } while (state.players[state.current].finished && guard <= n);
  }
  updateTurnUI();
  /* 換人時畫面要跟過去。手機上棋盤是超出畫面捲動的,不帶過去的話下一位玩家
     根本看不到自己的棋子在哪 —— 之前只有「棋子移動」會捲,「換人」不會。 */
  followActivePiece(activePieceTarget());
  const rb = document.getElementById('roll-btn');
  if (rb) rb.disabled = false;
}

function showWin(name) {
  state.gameOver = true;
  document.getElementById('win-title').textContent = tp('winTitle', { '__NAME__': name });
  document.getElementById('win-text').textContent  = tp('winText', { '__NAME__': name });
  openModal('win-modal');
}

function resetGame() {
  closeModal('win-modal');
  state.gameOver=false; state.rolling=false; state.current=0;
  /* finished / finishOrder and the panel styling have to go too — otherwise the
     next game starts with every seat already flagged as finished, so arriveAtEnd
     returns early and advanceTurn skips everyone. */
  state.finishOrder = [];
  state.players.forEach((p, i) => {
    p.stepIndex=0; p.skip=false; p.finished=false; p.entered=!startWheel();
    document.getElementById('p' + (i + 1) + '-panel')?.classList.remove('finished');
  });
  buildBoard(); updateTurnUI(); updatePosDisplay();
  document.getElementById('roll-btn').disabled = false;
  const idleDice = document.getElementById('dice');
  if (idleDice) idleDice.innerHTML = svgIcon('dice');
  openModal('setup-modal');
}

/* ── Turn UI ── */
function updateTurnUI() {
  const p = state.players[state.current];
  if (!p) return;
  const label = document.getElementById('turn-label');
  if (label) {
    label.textContent = tp('turnOf', { ':name': p.name });
    /* 重新觸發淡入動畫。同一個元素要再播一次 CSS animation,得先移除 class 並
       強制一次 reflow —— 只是拿掉再加上,瀏覽器會把兩次變更合併成沒有變更。 */
    label.classList.remove('is-changing');
    void label.offsetWidth;
    label.classList.add('is-changing');
  }
  /* Every seat, not just p1/p2 — in a 4-player game the highlight used to get
     stuck on whoever of the first two moved last. */
  state.players.forEach(function(_, i) {
    document.getElementById('p' + (i + 1) + '-panel')?.classList.toggle('active', state.current === i);
  });
}

function updatePosDisplay() {
  state.players.forEach((p, i) => {
    const el = document.getElementById(`p${i+1}-pos`);
    if (!el) return;
    const path   = getPlayerPath(p);
    const endIdx = path.length - 1;
    el.textContent =
      !isOnTrack(p)            ? tp('wheelWaiting')
      : p.stepIndex === 0      ? tp('startPoint')
      : p.stepIndex >= endIdx  ? tp('endPoint')
      : tp('stepN', { '__N__': p.stepIndex });
  });
}

/* ── Modals ── */
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.EDIT_MODE === 'undefined') window.EDIT_MODE = false;
  buildBoard();
  // 按鈕上的字要對上上次選的尺寸(Blade 印的是預設值)
  syncBoardSizeBtn();
  build3dCube();
  const sqText = document.getElementById('sq-text');
  if (sqText) sqText.addEventListener('input', () => {
    document.getElementById('sq-char').textContent = sqText.value.length;
  });
});

/* 玩法側欄:桌機為右側欄、窄螢幕為滑出抽屜。狀態記在 localStorage,
   使用者關掉之後不會每次進來又跳出來。 */
function toggleRules(force) {
  const body = document.querySelector('.play-body');
  const btn = document.getElementById('rules-toggle');
  if (!body) return;
  const open = typeof force === 'boolean' ? force : !body.classList.contains('rules-open');
  body.classList.toggle('rules-open', open);
  if (btn) btn.setAttribute('aria-expanded', String(open));
  try { localStorage.setItem('play_rules_open', open ? '1' : '0'); } catch (e) {}
}

/* 首次進入預設打開(讓使用者知道有規則可看);之後尊重上次的選擇。 */
(function initRules() {
  function apply() {
    let pref = null;
    try { pref = localStorage.getItem('play_rules_open'); } catch (e) {}
    // 桌機預設開、窄螢幕預設關(抽屜蓋住棋盤不適合當預設)
    const wide = window.matchMedia && window.matchMedia('(min-width:1024px)').matches;
    toggleRules(pref === null ? wide : pref === '1');
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', apply);
  } else { apply(); }
})();

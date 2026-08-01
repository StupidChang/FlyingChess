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

/** Current position ID for a player */
function currentPos(player) {
  const path = getEffectivePath(player.gender);
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
const FACE_ROTATIONS = {
  1: 'rotateX(0deg) rotateY(0deg)',
  2: 'rotateX(-90deg) rotateY(0deg)',
  3: 'rotateX(0deg) rotateY(90deg)',
  4: 'rotateX(0deg) rotateY(-90deg)',
  5: 'rotateX(90deg) rotateY(0deg)',
  6: 'rotateX(0deg) rotateY(180deg)',
};

/** Animate 3D dice roll and resolve with value */
function roll3dDice() {
  return new Promise(function(resolve) {
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

    // Show overlay
    overlay.classList.add('active');

    if (reduced) {
      // Reduced motion: show the result face directly, briefly
      cube.className = 'dice-cube';
      cube.style.transform = FACE_ROTATIONS[result];
      setTimeout(function() {
        overlay.classList.remove('active');
        resolve(result);
      }, 650);
      return;
    }

    // Stage 1: fast tumble → decelerating settle (CSS keyframe, .9s)
    cube.className = 'dice-cube rolling';
    cube.style.transform = '';

    // Stage 2: land on result face (overshoot bezier transition)
    setTimeout(function() {
      cube.className = 'dice-cube landing';
      cube.style.transform = FACE_ROTATIONS[result];
    }, 900);

    // Stage 3: squash & stretch bounce on touchdown
    setTimeout(function() {
      if (scene) scene.classList.add('dice-landed');
    }, 1450);

    // Hide overlay after landing
    setTimeout(function() {
      overlay.classList.remove('active');
      cube.className = 'dice-cube';
      if (scene) scene.classList.remove('dice-landed');
      resolve(result);
    }, 1950);
  });
}

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
function sizeGameBoard(board, cols, rows) {
  const wrap = board.closest('.board-wrap');
  const ar = cols / rows;
  let maxW = Math.min(window.innerWidth * 0.96, 960);
  let maxH = window.innerHeight - 205; // fallback if wrap not measurable yet
  if (wrap) {
    const cs = getComputedStyle(wrap);
    maxW = Math.min(maxW, wrap.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight));
    maxH = wrap.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
  }
  let bw = maxW, bh = bw / ar;
  if (bh > maxH) { bh = maxH; bw = bh * ar; }
  board.style.width  = Math.floor(bw) + 'px';
  board.style.height = Math.floor(bh) + 'px';
}

/* Re-fit on resize/rotation; piece positions are derived from square rects,
   so re-render them after the board changes size. */
window.addEventListener('resize', () => {
  if (window.EDIT_MODE || !lastGrid) return;
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

  const allPaths   = getEffectivePath('all');
  const arrowMap   = computeArrowMap(allPaths, sqData);

  Object.entries(sqData).forEach(([posStr, sq]) => {
    const pos = parseInt(posStr, 10);
    if (!sq.grid_row || !sq.grid_col) return;

    const div = document.createElement('div');
    div.className        = `board-sq color-${sq.color}`;
    div.id               = `sq-${pos}`;
    div.style.gridRow    = sq.grid_row - rowOffset;
    div.style.gridColumn = sq.grid_col - colOffset;

    const flyBadge = sq.fly_to != null
      ? `<div class="sq-fly-badge">✈→${sq.fly_to}</div>` : '';
    const arrow    = arrowMap[pos] ? `<div class="sq-arrow">${arrowMap[pos]}</div>` : '';

    div.innerHTML = `
      <div class="sq-num">${pos}</div>
      <div class="sq-text">${escHtml(sq.text)}</div>
      ${flyBadge}
      ${arrow}
      ${isEditMode ? '<span class="edit-icon">✏</span>' : ''}
    `;

    if (isEditMode) div.addEventListener('click', () => openSqModal(pos));
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

    const legend = segs.map(function (seg, i) {
      return `<li><b>${i + 1}</b><span>${escHtml(seg.text)}</span></li>`;
    }).join('');

    wheelEl.innerHTML = `<div class="bew-graphic">${wheelSvg(null)}</div>`
      + `<div class="bew-label">${escHtml(tp('startWheel'))}</div>`
      + `<ol class="bew-legend">${legend}</ol>`;
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
function positionPiece(el, sqEl, board, offsetIndex) {
  const boardRect = board.getBoundingClientRect();
  const sqRect    = sqEl.getBoundingClientRect();
  const size = Math.max(10, Math.min(sqRect.width, sqRect.height) * 0.5);
  el.style.width  = size + 'px';
  el.style.height = size + 'px';
  // Small per-player offset so two pieces sharing a square stay visible
  // instead of fully overlapping (mirrors the previous top-left/bottom-right layout).
  const nudge = size * 0.28;
  const dx = offsetIndex === 0 ? -nudge : nudge;
  const dy = offsetIndex === 0 ? -nudge : nudge;
  const cx = (sqRect.left - boardRect.left) + sqRect.width  / 2 + dx;
  const cy = (sqRect.top  - boardRect.top)  + sqRect.height / 2 + dy;
  el.style.transform = `translate(${cx - size / 2}px, ${cy - size / 2}px)`;
}

function renderPieces() {
  const board = document.getElementById('game-board');
  if (!board) return;
  state.players.forEach((p, i) => {
    let el = document.getElementById(`piece-${i+1}`);

    /* Not on the track yet (entry wheel) → keep the piece off the board. */
    if (!isOnTrack(p)) {
      if (el) el.classList.add('piece-waiting');
      return;
    }
    el?.classList.remove('piece-waiting');

    const pos  = currentPos(p);
    const sqEl = document.getElementById(`sq-${pos}`);
    if (!sqEl) return;
    const isNew = !el;
    if (isNew) {
      el = document.createElement('div');
      el.className = `piece-token piece-${i+1}`;
      el.id        = `piece-${i+1}`;
      board.appendChild(el);
    }
    if (isNew) {
      // Snap into place on first placement (setup/reset/rebuild) instead
      // of visibly sliding in from the top-left corner.
      el.style.transition = 'none';
      positionPiece(el, sqEl, board, i);
      void el.offsetWidth; // force reflow so the transition-less transform commits
      el.style.transition = '';
    } else {
      positionPiece(el, sqEl, board, i);
    }
  });
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
  document.getElementById('meta-modal').classList.add('open');
}
function closeMetaModal() { document.getElementById('meta-modal').classList.remove('open'); }
async function saveMeta() {
  const name = document.getElementById('meta-name').value.trim();
  const desc = document.getElementById('meta-desc').value.trim();
  if (!name) return;
  try {
    const res = await fetch(window.BOARD_ROUTES.update, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN},
      body:JSON.stringify({name,description:desc}),
    });
    if (!res.ok) throw new Error();
    window.BOARD_NAME = name;
    const d = document.getElementById('board-name-display');
    if (d) d.textContent = name;
    closeMetaModal();
  } catch { alert(tp('saveFailed')); }
}

/* ═══════════════════════════════════════════════════
   PLAY MODE — Setup
   ═══════════════════════════════════════════════════ */
function startSetup() {
  /* V8.0 四人版:兩人一組(0&1 第一組、2&3 第二組),同組都抵達終點才算贏。 */
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
      gender: (gEl && gEl.value) || (n % 2 === 1 ? 'male' : 'female')
    });
  }
  state.current = 0; state.rolling = false; state.gameOver = false;
  state.finishOrder = [];

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

/* ── V8.0 分組與追趕 ─────────────────────────────────────────────
   分組:3–4 人時兩人一組;1–2 人時每人自成一組(維持原本「先到即贏」)。 */
function teamOf(idx) {
  return state.players.length >= 3 ? Math.floor(idx / 2) : idx;
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
      showWheelModal(roll);
      state.rolling = false;
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
    const path    = getEffectivePath(player.gender);
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
    if (step >= toIdx) {
      setTimeout(callback, 200);
    } else {
      setTimeout(nextStep, 200);
    }
  }
  nextStep();
}

function applyMoveEffect(sq, callback) {
  callback = callback || function(){};
  const player = state.players[state.current];
  const path   = getEffectivePath(player.gender);
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
    const hasFly = sq.fly_to != null;
    showFlyButtons(hasFly, hasFly ? sq.fly_to : null);
  }

  if (player.skip) skipNote.classList.remove('hidden');
  openModal('action-modal');
}

/* ── Entry wheel ─────────────────────────────────────────────────────────
   Rendered as a six-slice SVG pie with the rolled slice pulled out, mirroring
   the wheel printed in each corner of the physical board. */
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
    const x0 = CX + r * Math.cos(a0), y0 = CY + r * Math.sin(a0);
    const x1 = CX + r * Math.cos(a1), y1 = CY + r * Math.sin(a1);
    out += `<path d="M${CX} ${CY} L${x0} ${y0} A${r} ${r} 0 0 1 ${x1} ${y1} Z"`
        +  ` fill="${WHEEL_HEX[i]}" opacity="${active ? 1 : .35}"`
        +  ` stroke="rgba(0,0,0,.35)" stroke-width="1"/>`;

    const am = a0 + slice / 2, lr = r * .62;
    out += `<text x="${CX + lr * Math.cos(am)}" y="${CY + lr * Math.sin(am)}"`
        +  ` text-anchor="middle" dominant-baseline="middle"`
        +  ` font-size="15" font-weight="700" fill="#fff">${i + 1}</text>`;
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

function showWheelModal(roll) {
  const w      = startWheel();
  const seg    = w[roll - 1];
  const player = state.players[state.current];

  state.pendingWheel = { roll: roll, seg: seg };

  document.getElementById('wheel-graphic').innerHTML = wheelSvg(roll);
  document.getElementById('wheel-player').textContent = player.name;
  document.getElementById('wheel-text').textContent = seg.text || '';

  const note = document.getElementById('wheel-note');
  if (note) {
    note.textContent = seg.enter ? tp('wheelEnter') : (seg.reroll ? tp('wheelReroll') : tp('wheelStay'));
    note.className = 'wheel-note' + (seg.enter ? ' is-enter' : '');
  }

  openModal('wheel-modal');
}

/** Resolve the wheel result: enter the track, roll again, or pass the turn. */
function confirmWheel() {
  const pending = state.pendingWheel;
  closeModal('wheel-modal');
  state.pendingWheel = null;
  if (!pending) { advanceTurn(); return; }

  const player = state.players[state.current];

  if (pending.seg.enter) {
    player.entered = true;
    player.stepIndex = 0;
    renderPieces(); updatePosDisplay(); flashSquare(currentPos(player));
    /* Entering onto an occupied start square must not evict anyone — captureAt
       already ignores stepIndex 0, so nothing to do here. */
    advanceTurn();
    return;
  }

  if (pending.seg.reroll) {
    /* Same player rolls again — do not advance the turn. */
    updateTurnUI();
    const rb = document.getElementById('roll-btn');
    if (rb) rb.disabled = false;
    return;
  }

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
      const path   = getEffectivePath(player.gender);
      const endIdx = path.length - 1;
      const flyIdx = path.indexOf(sq.fly_to);
      if (flyIdx >= 0) {
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
  const path = getEffectivePath(mate.gender);
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
  if (label) label.textContent = tp('turnOf', { ':name': p.name });
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
    const path   = getEffectivePath(p.gender);
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

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
};

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
function buildBoard() {
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

  // Calculate board dimensions to fit within viewport
  if (!isEditMode) {
    const ar = cols / rows;
    const maxW = Math.min(window.innerWidth * 0.96, 960);
    const maxH = window.innerHeight - 140; // header + player bar
    let bw = maxW, bh = bw / ar;
    if (bh > maxH) { bh = maxH; bw = bh * ar; }
    board.style.width  = bw + 'px';
    board.style.height = bh + 'px';
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
    const pos  = currentPos(p);
    const sqEl = document.getElementById(`sq-${pos}`);
    if (!sqEl) return;
    let el = document.getElementById(`piece-${i+1}`);
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
  const status = document.getElementById('sq-save-status');
  status.style.color='#5fd080'; status.textContent=tp('saving');
  try {
    const res = await fetch(`${window.BOARD_ROUTES.squares}/${editPos}`, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN},
      body:JSON.stringify({text,color,fly_to}),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    if (!window.SQUARES_DATA) window.SQUARES_DATA = {};
    Object.assign(window.SQUARES_DATA[editPos], {text,color,fly_to});

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
  const p1Name   = document.getElementById('setup-p1')?.value.trim()  || tp('player1');
  const p1Gender = document.querySelector('input[name="p1-gender"]:checked')?.value || 'male';
  state.players  = [{ name:p1Name, stepIndex:0, skip:false, gender:p1Gender }];
  state.current  = 0; state.rolling=false; state.gameOver=false;

  if (window.PLAYER_COUNT >= 2) {
    const p2Name   = document.getElementById('setup-p2')?.value.trim()  || tp('player2');
    const p2Gender = document.querySelector('input[name="p2-gender"]:checked')?.value || 'female';
    state.players.push({ name:p2Name, stepIndex:0, skip:false, gender:p2Gender });
  }

  const gIcon = g => g==='male'?' ♂':' ♀';
  document.getElementById('p1-name').textContent = state.players[0].name + gIcon(p1Gender);
  document.getElementById('p1-pos').textContent  = tp('startPoint');
  if (state.players[1]) {
    document.getElementById('p2-name').textContent = state.players[1].name + gIcon(state.players[1].gender);
    document.getElementById('p2-pos').textContent  = tp('startPoint');
  }

  closeModal('setup-modal');
  buildBoard();
  build3dCube();
  updateTurnUI();
}

/* ═══════════════════════════════════════════════════
   PLAY MODE — Dice & Movement
   ═══════════════════════════════════════════════════ */
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
        setTimeout(function() { showWin(player.name); }, 400);
        resolve();
      });
      return;
    }

    animateSteps(player, startIdx, rawNext, function() {
      const pos = currentPos(player);
      updatePosDisplay();

      /* Collision */
      if (state.players.length > 1 && player.stepIndex > 0) {
        const other = state.players[(state.current+1) % state.players.length];
        if (other.stepIndex > 0 && currentPos(other) === pos) {
          other.stepIndex = 0;
          renderPieces(); updatePosDisplay();
        }
      }

      const sq = getSq(pos);
      if (sq.color === 'move') {
        applyMoveEffect(sq, function() {
          const finalPos = currentPos(player);
          /* Collision check after move effect */
          if (state.players.length > 1 && player.stepIndex > 0) {
            const other = state.players[(state.current+1) % state.players.length];
            if (other.stepIndex > 0 && currentPos(other) === finalPos) {
              other.stepIndex = 0;
              renderPieces(); updatePosDisplay();
            }
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
  const fwd    = sq.text?.match(/前進\s*(\d+)\s*格/);
  const bwd    = sq.text?.match(/後退\s*(\d+)\s*格/);
  if (fwd) {
    const from = player.stepIndex;
    const to = Math.min(path.length-1, player.stepIndex+parseInt(fwd[1],10));
    animateSteps(player, from, to, function() { updatePosDisplay(); callback(); });
  } else if (bwd) {
    const from = player.stepIndex;
    const to = Math.max(0, player.stepIndex-parseInt(bwd[1],10));
    animateStepsBackward(player, from, to, function() { updatePosDisplay(); callback(); });
  } else if (/跳過/.test(sq.text||'')) {
    player.skip = true;
    callback();
  } else {
    callback();
  }
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

  const genderMismatch =
    (sq.color==='male'   && player.gender!=='male') ||
    (sq.color==='female' && player.gender!=='female');

  if (genderMismatch) {
    const label = sq.color==='male' ? tp('male') : tp('female');
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
          setTimeout(() => showWin(player.name), 400);
          return;
        }
        player.stepIndex = flyIdx;
        renderPieces(); flashSquare(currentPos(player)); updatePosDisplay();
        /* Collision check after fly */
        if (state.players.length > 1 && player.stepIndex > 0) {
          const other = state.players[(state.current+1) % state.players.length];
          if (other.stepIndex > 0 && currentPos(other) === currentPos(player)) {
            other.stepIndex = 0;
            renderPieces(); updatePosDisplay();
          }
        }
      }
    }
  }

  closeModal('action-modal');
  if (state.players.length > 1) state.current = (state.current+1) % state.players.length;
  updateTurnUI();
  document.getElementById('roll-btn').disabled = false;
}

function flashSquare(pos) {
  const el = document.getElementById(`sq-${pos}`);
  if (!el) return;
  el.classList.add('highlight');
  setTimeout(() => el.classList.remove('highlight'), 2200);
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
  state.players.forEach(p => { p.stepIndex=0; p.skip=false; });
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
  document.getElementById('p1-panel')?.classList.toggle('active', state.current===0);
  document.getElementById('p2-panel')?.classList.toggle('active', state.current===1);
}

function updatePosDisplay() {
  state.players.forEach((p, i) => {
    const el = document.getElementById(`p${i+1}-pos`);
    if (!el) return;
    const path   = getEffectivePath(p.gender);
    const endIdx = path.length - 1;
    el.textContent =
      p.stepIndex === 0        ? tp('startPoint')
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

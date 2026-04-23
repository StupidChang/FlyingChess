/* =====================================================
   情侶飛行棋 V2 — board.js
   Handles: play mode + square-content edit mode
   Canvas/layout/path edit: see board-editor.js
   ===================================================== */

const COLOR_HEX = {
  action:'#ff9800',drink:'#fdd835',dare:'#9c27b0',truth:'#1976d2',
  strip:'#e91e63',move:'#43a047',normal:'#9e9e9e',
  start:'#f57c00',end:'#d32f2f',male:'#1565c0',female:'#c2185b',
};

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
   BOARD RENDERING  (content + play modes)
   ═══════════════════════════════════════════════════ */
function buildBoard() {
  const board = document.getElementById('game-board');
  if (!board) return;
  board.innerHTML = '';

  const rows = window.CANVAS_ROWS || 11;
  const cols = window.CANVAS_COLS || 13;
  board.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
  board.style.gridTemplateRows    = `repeat(${rows}, 1fr)`;
  board.style.aspectRatio         = `${cols} / ${rows}`;

  const allPaths   = getEffectivePath('all');
  const arrowMap   = computeArrowMap(allPaths, window.SQUARES_DATA || {});
  const sqData     = window.SQUARES_DATA || {};
  const isEditMode = window.EDIT_MODE;

  Object.entries(sqData).forEach(([posStr, sq]) => {
    const pos = parseInt(posStr, 10);
    if (!sq.grid_row || !sq.grid_col) return;

    const div = document.createElement('div');
    div.className        = `board-sq color-${sq.color}`;
    div.id               = `sq-${pos}`;
    div.style.gridRow    = sq.grid_row;
    div.style.gridColumn = sq.grid_col;

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
  if (rows === 11 && cols === 13) {
    const center = document.createElement('div');
    center.className        = 'board-center';
    center.style.gridRow    = '6';
    center.style.gridColumn = '2 / 13';
    center.innerHTML = `
      <div class="center-title">✈ 情侶飛行棋 V2.0</div>
      <div class="center-rules-inline">♂格僅男執行 · ♀格僅女執行 · 雙方同格後到者退回起點</div>
    `;
    board.appendChild(center);

    const cornerData = [
      {row:'1/5',col:'1/5',  icon:'🎲',sub:'準備好酒水\n啤酒或調酒'},
      {row:'1/5',col:'8/14', icon:'💕',sub:'起點→終點\n先到者獲勝'},
      {row:'8/12',col:'1/5', icon:'🍺',sub:'不喝酒可改\n對方口交30秒'},
      {row:'8/12',col:'8/14',icon:'🏆',sub:'完成所有挑戰\n為愛鼓掌！'},
    ];
    cornerData.forEach(c => {
      const el = document.createElement('div');
      el.className = 'board-corner-deco';
      el.style.gridRow    = c.row;
      el.style.gridColumn = c.col;
      el.innerHTML = `<div class="corner-icon">${c.icon}</div><div class="corner-sub">${escHtml(c.sub)}</div>`;
      board.appendChild(el);
    });
  }

  if (!isEditMode && state.players.length) renderPieces();
}

/* ── Piece tokens ── */
function renderPieces() {
  document.querySelectorAll('.piece-token').forEach(e => e.remove());
  state.players.forEach((p, i) => {
    const pos  = currentPos(p);
    const sqEl = document.getElementById(`sq-${pos}`);
    if (!sqEl) return;
    const el       = document.createElement('div');
    el.className   = `piece-token piece-${i+1}`;
    el.id          = `piece-${i+1}`;
    el.textContent = i === 0 ? '♥' : '♦';
    sqEl.appendChild(el);
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
  status.style.color='#5fd080'; status.textContent='儲存中…';
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/squares/${editPos}`, {
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
    status.textContent='✅ 已儲存';
    setTimeout(closeSqModal, 900);
  } catch(err) {
    status.style.color='#f06080'; status.textContent='❌ 儲存失敗，請重試';
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
    const res = await fetch(`/boards/${window.BOARD_ID}`, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN},
      body:JSON.stringify({name,description:desc}),
    });
    if (!res.ok) throw new Error();
    window.BOARD_NAME = name;
    const d = document.getElementById('board-name-display');
    if (d) d.textContent = name;
    closeMetaModal();
  } catch { alert('儲存失敗，請重試'); }
}

/* ═══════════════════════════════════════════════════
   PLAY MODE — Setup
   ═══════════════════════════════════════════════════ */
function startSetup() {
  const p1Name   = document.getElementById('setup-p1')?.value.trim()  || '玩家 1';
  const p1Gender = document.querySelector('input[name="p1-gender"]:checked')?.value || 'male';
  state.players  = [{ name:p1Name, stepIndex:0, skip:false, gender:p1Gender }];
  state.current  = 0; state.rolling=false; state.gameOver=false;

  if (window.PLAYER_COUNT >= 2) {
    const p2Name   = document.getElementById('setup-p2')?.value.trim()  || '玩家 2';
    const p2Gender = document.querySelector('input[name="p2-gender"]:checked')?.value || 'female';
    state.players.push({ name:p2Name, stepIndex:0, skip:false, gender:p2Gender });
  }

  const gIcon = g => g==='male'?' ♂':' ♀';
  document.getElementById('p1-name').textContent = state.players[0].name + gIcon(p1Gender);
  document.getElementById('p1-pos').textContent  = '起點';
  if (state.players[1]) {
    document.getElementById('p2-name').textContent = state.players[1].name + gIcon(state.players[1].gender);
    document.getElementById('p2-pos').textContent  = '起點';
  }

  closeModal('setup-modal');
  buildBoard();
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
    document.getElementById('action-text').textContent = `${player.name} 本回合跳過！`;
    document.getElementById('action-color-bar').style.background = '#9e9e9e';
    document.getElementById('skip-notice').classList.remove('hidden');
    document.getElementById('gender-notice').classList.add('hidden');
    showFlyButtons(false, null);
    openModal('action-modal');
    return;
  }

  state.rolling = true;
  document.getElementById('roll-btn').disabled = true;

  const diceEl = document.getElementById('dice');
  diceEl.classList.add('rolling');
  const FACES = ['⚀','⚁','⚂','⚃','⚄','⚅'];
  let ticks = 0;
  const timer = setInterval(() => {
    diceEl.textContent = FACES[Math.floor(Math.random()*6)];
    if (++ticks >= 10) {
      clearInterval(timer);
      const roll = Math.floor(Math.random()*6)+1;
      diceEl.textContent = FACES[roll-1];
      diceEl.classList.remove('rolling');
      setTimeout(() => applyMove(roll), 300);
    }
  }, 55);
}

function applyMove(roll) {
  const player  = state.players[state.current];
  const path    = getEffectivePath(player.gender);
  const endIdx  = path.length - 1;
  const rawNext = player.stepIndex + roll;

  /* Win: overshoot or land on end */
  if (rawNext >= endIdx) {
    player.stepIndex = endIdx;
    const endPos = path[endIdx];
    renderPieces(); flashSquare(endPos); updatePosDisplay();
    state.rolling = false;
    setTimeout(() => showWin(player.name), 600);
    return;
  }

  player.stepIndex = rawNext;
  const pos = currentPos(player);
  renderPieces(); flashSquare(pos); updatePosDisplay();
  state.rolling = false;

  /* Collision */
  if (state.players.length > 1 && player.stepIndex > 0) {
    const other = state.players[(state.current+1) % state.players.length];
    if (other.stepIndex > 0 && currentPos(other) === pos) {
      other.stepIndex = 0;
      renderPieces(); updatePosDisplay();
    }
  }

  const sq = getSq(pos);
  if (sq.color === 'move') applyMoveEffect(sq);

  setTimeout(() => showActionModal(roll, pos), 400);
}

function applyMoveEffect(sq) {
  const player = state.players[state.current];
  const path   = getEffectivePath(player.gender);
  const fwd    = sq.text?.match(/前進\s*(\d+)\s*格/);
  const bwd    = sq.text?.match(/後退\s*(\d+)\s*格/);
  if (fwd) {
    player.stepIndex = Math.min(path.length-1, player.stepIndex+parseInt(fwd[1],10));
    renderPieces(); updatePosDisplay();
  } else if (bwd) {
    player.stepIndex = Math.max(0, player.stepIndex-parseInt(bwd[1],10));
    renderPieces(); updatePosDisplay();
  } else if (/跳過/.test(sq.text||'')) {
    player.skip = true;
  }
}

function showActionModal(roll, pos) {
  const player   = state.players[state.current];
  const sq       = getSq(pos);
  const genderEl = document.getElementById('gender-notice');
  const skipNote = document.getElementById('skip-notice');
  const textEl   = document.getElementById('action-text');

  document.getElementById('action-dice').textContent = roll;
  document.getElementById('action-color-bar').style.background = COLOR_HEX[sq.color]||COLOR_HEX.normal;
  skipNote.classList.add('hidden'); genderEl.classList.add('hidden');

  const genderMismatch =
    (sq.color==='male'   && player.gender!=='male') ||
    (sq.color==='female' && player.gender!=='female');

  if (genderMismatch) {
    const label = sq.color==='male'?'♂ 男生':'♀ 女生';
    textEl.textContent = sq.text || '';
    genderEl.textContent = `此格為 ${label} 專屬，${player.name} 跳過本次懲罰`;
    genderEl.classList.remove('hidden');
    showFlyButtons(false, null);
  } else {
    textEl.textContent = sq.text || '普通格子，繼續！';
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
  document.getElementById('win-title').textContent = `🏆 ${name} 獲勝！`;
  document.getElementById('win-text').textContent  = `恭喜 ${name} 率先抵達終點！遊戲結束，為愛鼓掌！🎉`;
  openModal('win-modal');
}

function resetGame() {
  closeModal('win-modal');
  state.gameOver=false; state.rolling=false; state.current=0;
  state.players.forEach(p => { p.stepIndex=0; p.skip=false; });
  buildBoard(); updateTurnUI(); updatePosDisplay();
  document.getElementById('roll-btn').disabled = false;
  document.getElementById('dice').textContent  = '🎲';
  openModal('setup-modal');
}

/* ── Turn UI ── */
function updateTurnUI() {
  const p = state.players[state.current];
  if (!p) return;
  const label = document.getElementById('turn-label');
  if (label) label.textContent = `${p.name} 的回合`;
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
      p.stepIndex === 0        ? '起點'
      : p.stepIndex >= endIdx  ? '終點 🏁'
      : `第 ${p.stepIndex} 步`;
  });
}

/* ── Modals ── */
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.EDIT_MODE === 'undefined') window.EDIT_MODE = false;
  buildBoard();
  const sqText = document.getElementById('sq-text');
  if (sqText) sqText.addEventListener('input', () => {
    document.getElementById('sq-char').textContent = sqText.value.length;
  });
});

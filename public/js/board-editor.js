/* =====================================================
   board-editor.js  —  Canvas Layout + Path Editor
   Loaded ONLY on the edit page (EDIT_MODE = true)
   ===================================================== */

/* ── Editor state ── */
const edState = {
  currentTab   : 'content',   // 'content' | 'layout' | 'path'
  pathGroup    : 'all',        // 'all' | 'male' | 'female'
  pathData     : null,         // { all:[...], male:[...]|null, female:[...]|null }
  canvasRows   : 11,
  canvasCols   : 13,
  dragSrcIdx   : null,
};

/* ═══════════════════════════════════════════════════
   TAB MANAGEMENT
   ═══════════════════════════════════════════════════ */
function switchTab(tab) {
  edState.currentTab = tab;

  // Toggle tab buttons
  document.querySelectorAll('.tab-btn').forEach(b =>
    b.classList.toggle('tab-active', b.dataset.tab === tab));

  // Toggle panels
  document.querySelectorAll('.tab-panel').forEach(p =>
    p.classList.toggle('tab-hidden', p.id !== `tab-${tab}`));

  // Rebuild the shared game-board div
  const board = document.getElementById('game-board');
  if (!board) return;

  if (tab === 'content') {
    board.classList.remove('layout-mode', 'path-mode');
    board.classList.add('edit-mode');
    buildBoard(); // from board.js
    document.getElementById('path-side-panel')?.classList.add('hidden');
    document.getElementById('layout-controls')?.classList.add('hidden');
  } else if (tab === 'layout') {
    board.classList.remove('edit-mode', 'path-mode');
    board.classList.add('layout-mode');
    document.getElementById('path-side-panel')?.classList.add('hidden');
    document.getElementById('layout-controls')?.classList.remove('hidden');
    buildLayoutBoard();
  } else if (tab === 'path') {
    board.classList.remove('edit-mode', 'layout-mode');
    board.classList.add('path-mode');
    document.getElementById('layout-controls')?.classList.add('hidden');
    document.getElementById('path-side-panel')?.classList.remove('hidden');
    buildPathBoard();
    renderPathList();
  }
}

/* ═══════════════════════════════════════════════════
   CANVAS / LAYOUT EDITOR
   ═══════════════════════════════════════════════════ */

/** Render all cells (occupied + empty) for the layout editor */
function buildLayoutBoard() {
  const board = document.getElementById('game-board');
  if (!board) return;
  board.innerHTML = '';

  const rows = edState.canvasRows;
  const cols = edState.canvasCols;
  board.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
  board.style.gridTemplateRows    = `repeat(${rows}, 1fr)`;
  board.style.aspectRatio         = `${cols} / ${rows}`;

  for (let r = 1; r <= rows; r++) {
    for (let c = 1; c <= cols; c++) {
      const posId = findPosAtCell(r, c);
      const cell  = document.createElement('div');
      cell.style.gridRow    = r;
      cell.style.gridColumn = c;

      if (posId !== null) {
        const sq = getSq(posId);
        cell.className = `board-sq color-${sq.color} layout-occupied`;
        cell.id        = `sq-${posId}`;
        cell.innerHTML = `
          <div class="sq-num">${posId}</div>
          <div class="sq-text">${escHtml(sq.text)}</div>
          <button class="layout-del-btn" title="刪除此格子"
                  onclick="layoutDeleteSquare(event,${posId})">✕</button>
        `;
      } else {
        cell.className = 'layout-empty-cell';
        cell.title     = `加入格子 (${r},${c})`;
        cell.innerHTML = '<span class="layout-add-icon">＋</span>';
        cell.addEventListener('click', () => layoutAddSquare(r, c));
      }
      board.appendChild(cell);
    }
  }
}

/** Find which position ID is placed at a given grid cell */
function findPosAtCell(row, col) {
  const sqData = window.SQUARES_DATA || {};
  for (const [posStr, sq] of Object.entries(sqData)) {
    if (sq.grid_row === row && sq.grid_col === col) return parseInt(posStr, 10);
  }
  return null;
}

/** Add a new square at a cell */
async function layoutAddSquare(row, col) {
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/squares`, {
      method : 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
      body   : JSON.stringify({ grid_row: row, grid_col: col }),
    });
    const json = await res.json();
    if (!json.success) { alert(json.message || '新增失敗'); return; }
    if (!window.SQUARES_DATA) window.SQUARES_DATA = {};
    window.SQUARES_DATA[json.position] = json.square;
    buildLayoutBoard();
  } catch (e) { console.error(e); alert('新增失敗，請重試'); }
}

/** Delete a square from canvas */
async function layoutDeleteSquare(evt, posId) {
  evt.stopPropagation();
  if (!confirm(`確定刪除格子 #${posId}？此操作無法復原。`)) return;
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/squares/${posId}`, {
      method : 'DELETE',
      headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN },
    });
    const json = await res.json();
    if (!json.success) { alert(json.message||'刪除失敗'); return; }
    delete window.SQUARES_DATA[posId];
    // Sync path_data
    ['all','male','female'].forEach(g => {
      if (edState.pathData?.[g]) {
        edState.pathData[g] = edState.pathData[g].filter(p => p !== posId);
        if (edState.pathData[g].length === 0) edState.pathData[g] = null;
      }
    });
    window.PATH_DATA = edState.pathData;
    buildLayoutBoard();
  } catch (e) { console.error(e); alert('刪除失敗，請重試'); }
}

/** Apply canvas size change */
async function applyCanvasSize() {
  const rows = parseInt(document.getElementById('canvas-rows-input').value, 10);
  const cols = parseInt(document.getElementById('canvas-cols-input').value, 10);
  if (isNaN(rows)||rows<3||rows>30||isNaN(cols)||cols<3||cols>30) {
    alert('畫布大小需在 3–30 之間'); return;
  }
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/canvas`, {
      method : 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
      body   : JSON.stringify({ canvas_rows: rows, canvas_cols: cols }),
    });
    const json = await res.json();
    if (!json.success) { alert('儲存失敗'); return; }
    edState.canvasRows = rows; edState.canvasCols = cols;
    window.CANVAS_ROWS = rows; window.CANVAS_COLS = cols;
    buildLayoutBoard();
  } catch (e) { console.error(e); alert('儲存失敗'); }
}

/** Apply a preset (clears existing squares and creates preset) */
async function applyPreset(preset) {
  const names = { cross: '十字形', square: '方形環狀' };
  if (!confirm(`套用「${names[preset]}」預設將清除此棋盤所有格子與路徑！\n確定繼續？`)) return;
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/preset`, {
      method : 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
      body   : JSON.stringify({ preset }),
    });
    const json = await res.json();
    if (!json.success) { alert('套用失敗'); return; }
    window.SQUARES_DATA = json.squares;
    window.PATH_DATA    = json.path_data;
    window.CANVAS_ROWS  = edState.canvasRows = json.canvas_rows;
    window.CANVAS_COLS  = edState.canvasCols = json.canvas_cols;
    edState.pathData    = json.path_data;
    document.getElementById('canvas-rows-input').value = json.canvas_rows;
    document.getElementById('canvas-cols-input').value = json.canvas_cols;
    buildLayoutBoard();
  } catch (e) { console.error(e); alert('套用失敗'); }
}

/* ═══════════════════════════════════════════════════
   PATH EDITOR
   ═══════════════════════════════════════════════════ */

/** Render the path board (shows step numbers on squares) */
function buildPathBoard() {
  const board = document.getElementById('game-board');
  if (!board) return;
  board.innerHTML = '';

  const rows     = edState.canvasRows;
  const cols     = edState.canvasCols;
  const curPath  = getCurrentPath();
  // Map position ID → step number (1-based)
  const stepMap  = {};
  curPath.forEach((pos, idx) => { stepMap[pos] = idx + 1; });

  board.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
  board.style.gridTemplateRows    = `repeat(${rows}, 1fr)`;
  board.style.aspectRatio         = `${cols} / ${rows}`;

  const sqData = window.SQUARES_DATA || {};
  Object.entries(sqData).forEach(([posStr, sq]) => {
    const pos = parseInt(posStr, 10);
    if (!sq.grid_row || !sq.grid_col) return;

    const cell = document.createElement('div');
    cell.className        = `board-sq color-${sq.color} path-sq`;
    cell.id               = `sq-${pos}`;
    cell.style.gridRow    = sq.grid_row;
    cell.style.gridColumn = sq.grid_col;

    const step = stepMap[pos];
    const inPath = step !== undefined;

    cell.innerHTML = `
      <div class="sq-num">${pos}</div>
      ${inPath ? `<div class="path-step-badge">${step}</div>` : ''}
      <div class="sq-text">${escHtml(sq.text)}</div>
    `;

    cell.classList.toggle('in-path', inPath);
    cell.addEventListener('click', () => togglePosInPath(pos));
    board.appendChild(cell);
  });

  // Empty cells for reference
  for (let r = 1; r <= rows; r++) {
    for (let c = 1; c <= cols; c++) {
      const occupied = Object.values(sqData).some(s => s.grid_row===r && s.grid_col===c);
      if (!occupied) {
        const ghost = document.createElement('div');
        ghost.style.gridRow=r; ghost.style.gridColumn=c;
        ghost.className='layout-empty-cell path-ghost';
        board.appendChild(ghost);
      }
    }
  }
}

/** Get the current path for the selected group */
function getCurrentPath() {
  const group = edState.pathGroup;
  const pd    = edState.pathData || {};
  if (group !== 'all' && pd[group] && pd[group].length > 0) return pd[group];
  if (group !== 'all') return []; // separate empty path for male/female
  return pd.all || [];
}

/** Toggle a position in/out of the current path group */
function togglePosInPath(pos) {
  if (!edState.pathData) edState.pathData = { all: [], male: null, female: null };
  const group = edState.pathGroup;

  if (!edState.pathData[group]) edState.pathData[group] = [];

  const idx = edState.pathData[group].indexOf(pos);
  if (idx >= 0) {
    edState.pathData[group].splice(idx, 1);
    if (edState.pathData[group].length === 0 && group !== 'all') edState.pathData[group] = null;
  } else {
    edState.pathData[group].push(pos);
  }

  buildPathBoard();
  renderPathList();
}

/** Render the sortable path list panel */
function renderPathList() {
  const ul = document.getElementById('path-list-ul');
  if (!ul) return;
  ul.innerHTML = '';

  const path = getCurrentPath();
  const sqData = window.SQUARES_DATA || {};

  if (path.length === 0) {
    ul.innerHTML = '<li class="path-empty-hint">點擊棋盤上的格子加入路徑</li>';
    return;
  }

  path.forEach((pos, idx) => {
    const sq = sqData[pos] || {};
    const li = document.createElement('li');
    li.className     = 'path-item';
    li.draggable     = true;
    li.dataset.idx   = idx;
    li.dataset.pos   = pos;

    const isFirst = idx === 0;
    const isLast  = idx === path.length - 1;
    const stepLabel = isFirst ? '🚀 起點' : isLast ? '🏁 終點' : `步 ${idx + 1}`;

    li.innerHTML = `
      <span class="drag-handle" title="拖曳排序">☰</span>
      <span class="step-label ${isFirst?'step-start':isLast?'step-end':''}">${stepLabel}</span>
      <span class="pos-chip color-${sq.color||'normal'}" title="#${pos}">#${pos}</span>
      <span class="path-item-text">${escHtml((sq.text||'').split('\n')[0].substring(0,18))}</span>
      <button class="path-remove-btn" onclick="removeFromPath(${idx})" title="從路徑移除">×</button>
    `;

    li.addEventListener('dragstart', e => {
      edState.dragSrcIdx = idx;
      li.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    li.addEventListener('dragend', () => li.classList.remove('dragging'));
    li.addEventListener('dragover', e => {
      e.preventDefault(); e.dataTransfer.dropEffect = 'move';
      li.classList.add('drag-over');
    });
    li.addEventListener('dragleave', () => li.classList.remove('drag-over'));
    li.addEventListener('drop', e => {
      e.preventDefault();
      li.classList.remove('drag-over');
      const srcIdx  = edState.dragSrcIdx;
      const destIdx = parseInt(li.dataset.idx, 10);
      if (srcIdx !== null && srcIdx !== destIdx) reorderPath(srcIdx, destIdx);
      edState.dragSrcIdx = null;
    });

    ul.appendChild(li);
  });
}

/** Remove a position from the current path by list index */
function removeFromPath(idx) {
  const group = edState.pathGroup;
  if (!edState.pathData?.[group]) return;
  edState.pathData[group].splice(idx, 1);
  if (edState.pathData[group].length === 0 && group !== 'all') edState.pathData[group] = null;
  buildPathBoard();
  renderPathList();
}

/** Reorder: move item from srcIdx to destIdx */
function reorderPath(srcIdx, destIdx) {
  const group = edState.pathGroup;
  const arr   = edState.pathData?.[group];
  if (!arr) return;
  const [item] = arr.splice(srcIdx, 1);
  arr.splice(destIdx, 0, item);
  buildPathBoard();
  renderPathList();
}

/** Switch the active path group tab */
function selectPathGroup(group) {
  edState.pathGroup = group;
  document.querySelectorAll('.path-group-tab').forEach(b =>
    b.classList.toggle('active', b.dataset.group === group));

  const hint = document.getElementById('path-group-hint');
  if (hint) {
    hint.textContent = group === 'all'
      ? '主路徑：所有玩家默認使用此路徑'
      : group === 'male'
        ? '♂ 男生路徑：若設定，男性玩家優先使用此路徑（空白 = 使用主路徑）'
        : '♀ 女生路徑：若設定，女性玩家優先使用此路徑（空白 = 使用主路徑）';
  }

  buildPathBoard();
  renderPathList();
}

/** Clear current path */
function clearCurrentPath() {
  if (!confirm('確定清除當前路徑？')) return;
  const group = edState.pathGroup;
  if (!edState.pathData) edState.pathData = {};
  edState.pathData[group] = group === 'all' ? [] : null;
  buildPathBoard();
  renderPathList();
}

/** Reset all path to default (sorted positions) */
function resetPathToDefault() {
  if (!confirm('將路徑重設為格子編號順序？')) return;
  const positions = Object.keys(window.SQUARES_DATA || {}).map(Number).sort((a,b)=>a-b);
  edState.pathData = { all: positions, male: null, female: null };
  buildPathBoard();
  renderPathList();
}

/** Save paths to server */
async function savePaths() {
  const pd     = edState.pathData || {};
  const status = document.getElementById('path-save-status');
  if (!pd.all || pd.all.length < 2) { alert('主路徑至少需要 2 個格子'); return; }
  if (status) { status.style.color='#5fd080'; status.textContent='儲存中…'; }
  try {
    const res = await fetch(`/boards/${window.BOARD_ID}/path`, {
      method : 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
      body   : JSON.stringify(pd),
    });
    const json = await res.json();
    if (!json.success) throw new Error(json.message||'失敗');
    window.PATH_DATA = pd;
    if (status) { status.textContent='✅ 路徑已儲存'; setTimeout(()=>{ if(status) status.textContent=''; }, 2500); }
  } catch (e) {
    console.error(e);
    if (status) { status.style.color='#f06080'; status.textContent='❌ 儲存失敗'; }
  }
}

/* ═══════════════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  // Sync edState from window globals
  edState.canvasRows = window.CANVAS_ROWS || 11;
  edState.canvasCols = window.CANVAS_COLS || 13;
  edState.pathData   = window.PATH_DATA
    ? JSON.parse(JSON.stringify(window.PATH_DATA))   // deep clone
    : { all: [], male: null, female: null };

  // Sync canvas size inputs
  const rowInput = document.getElementById('canvas-rows-input');
  const colInput = document.getElementById('canvas-cols-input');
  if (rowInput) rowInput.value = edState.canvasRows;
  if (colInput) colInput.value = edState.canvasCols;
});

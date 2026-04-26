/* =============================================
   FlyingChessOnline - Game Logic (Client)
   ============================================= */

const COLORS    = ['yellow', 'blue', 'green', 'red'];
const COLOR_ZH  = { yellow: '黃色', blue: '藍色', green: '綠色', red: '紅色' };
const DICE_FACES = ['', '⚀','⚁','⚂','⚃','⚄','⚅'];

// Board data from PHP
const TRACK        = window.BOARD_DATA.track;
const SAFE_LANES   = window.BOARD_DATA.safeLanes;
const HOME_POS     = window.BOARD_DATA.homePos;
const CENTER       = window.BOARD_DATA.center;
const SAFE_SQUARES = window.BOARD_DATA.safeSquares;
const START_OFFSETS= window.BOARD_DATA.startOffsets;

const HOME_AREAS = {
    yellow: { rMin:0, rMax:5, cMin:0, cMax:5 },
    blue:   { rMin:0, rMax:5, cMin:9, cMax:14 },
    green:  { rMin:9, rMax:14, cMin:9, cMax:14 },
    red:    { rMin:9, rMax:14, cMin:0, cMax:5 },
};

const SAFE_LANE_CELLS = {};
COLORS.forEach(c => { SAFE_LANE_CELLS[c] = new Set(SAFE_LANES[c].map(p => p[0]+','+p[1])); });

const TRACK_SET = new Set(TRACK.map(p => p[0]+','+p[1]));

let gameState   = null;
let myColor     = window.MY_COLOR  || '';
let gameStatus  = window.GAME_STATUS;
let isSolo      = window.IS_SOLO   || false;
let pollTimer   = null;
let validMoves  = [];
let playersList = [];
let isBotThinking = false;

// DOM refs
let boardEl, rollBtn, startBtn, diceEl, turnDotEl, turnNameEl,
    myPiecesEl, logEl, playersListEl, playerCountEl, botStatusEl;

document.addEventListener('DOMContentLoaded', () => {
    boardEl       = document.getElementById('game-board');
    rollBtn       = document.getElementById('roll-btn');
    startBtn      = document.getElementById('start-btn');
    diceEl        = document.getElementById('dice');
    turnDotEl     = document.getElementById('turn-dot');
    turnNameEl    = document.getElementById('turn-name');
    myPiecesEl    = document.getElementById('my-pieces');
    logEl         = document.getElementById('game-log');
    playersListEl = document.getElementById('players-list');
    playerCountEl = document.getElementById('player-count');

    // Create bot status bar
    botStatusEl = document.createElement('div');
    botStatusEl.id = 'bot-status';
    botStatusEl.className = 'bot-status hidden';
    botStatusEl.innerHTML = '<span class="bot-spinner"></span> <span id="bot-status-text">AI 正在思考...</span>';
    document.querySelector('.game-main')?.prepend(botStatusEl);

    buildBoard();

    if (gameStatus === 'playing' || gameStatus === 'finished') {
        fetchState();
    }
    startPolling();
});

/* ---- Board Construction ---- */
function buildBoard() {
    if (!boardEl) return;
    boardEl.innerHTML = '';

    for (let r = 0; r < 15; r++) {
        for (let c = 0; c < 15; c++) {
            const cell = document.createElement('div');
            cell.className = 'board-cell';
            cell.id        = `cell-${r}-${c}`;
            cell.style.gridRow    = r + 1;
            cell.style.gridColumn = c + 1;
            cell.dataset.row = r;
            cell.dataset.col = c;

            const key = `${r},${c}`;

            // Center 3×3
            if (r >= 6 && r <= 8 && c >= 6 && c <= 8) {
                if (r === 7 && c === 7) {
                    cell.classList.add('cell-center');
                    cell.textContent = '✈';
                } else {
                    cell.style.background = '#1a2030';
                }
                boardEl.appendChild(cell); continue;
            }

            // Home base areas
            let inHome = false;
            for (const [color, area] of Object.entries(HOME_AREAS)) {
                if (r >= area.rMin && r <= area.rMax && c >= area.cMin && c <= area.cMax) {
                    cell.classList.add(`cell-home-${color}`);
                    inHome = true;
                    const isCircle = HOME_POS[color].some(([hr,hc]) => hr===r && hc===c);
                    if (isCircle) {
                        const circle = document.createElement('div');
                        circle.className = `cell-home-circle ${color}`;
                        cell.appendChild(circle);
                    }
                    break;
                }
            }
            if (inHome) { boardEl.appendChild(cell); continue; }

            // Safe lane cells
            let inLane = false;
            for (const color of COLORS) {
                if (SAFE_LANE_CELLS[color].has(key)) {
                    cell.classList.add(`cell-lane-${color}`);
                    inLane = true; break;
                }
            }
            if (inLane) { boardEl.appendChild(cell); continue; }

            // Track cells
            if (TRACK_SET.has(key)) {
                cell.classList.add('cell-path');
                const trackIdx = TRACK.findIndex(([tr,tc]) => tr===r && tc===c);
                if (SAFE_SQUARES.includes(trackIdx)) {
                    cell.classList.remove('cell-path');
                    cell.classList.add('cell-safe');
                }
                COLORS.forEach(color => {
                    if (trackIdx === START_OFFSETS[color]) {
                        cell.classList.add(`cell-start-${color}`);
                    }
                });
            } else {
                cell.style.background = '#0d1117';
                cell.style.border = 'none';
            }

            boardEl.appendChild(cell);
        }
    }
}

/* ---- Piece Rendering ---- */
function renderPieces(state) {
    if (!boardEl || !state) return;
    boardEl.querySelectorAll('.piece').forEach(p => p.remove());

    const piecesOnCell = {};

    for (const color of COLORS) {
        if (!state.pieces[color]) continue;
        state.pieces[color].forEach((progress, idx) => {
            if (progress === 0) return;
            if (progress === 58) {
                const cell = document.getElementById(`cell-${CENTER[0]}-${CENTER[1]}`);
                if (cell) addPieceToCell(cell, color, idx, piecesOnCell, `${CENTER[0]},${CENTER[1]}`);
                return;
            }
            const coords = progressToCoords(color, progress);
            if (!coords) return;
            const [r, c] = coords;
            const cell = document.getElementById(`cell-${r}-${c}`);
            if (cell) addPieceToCell(cell, color, idx, piecesOnCell, `${r},${c}`);
        });
    }
}

function addPieceToCell(cell, color, idx, tracker, key) {
    if (!tracker[key]) tracker[key] = [];
    const pieceEl = document.createElement('div');
    pieceEl.className = `piece ${color}`;
    pieceEl.dataset.color = color;
    pieceEl.dataset.idx   = idx;
    pieceEl.textContent   = idx + 1;

    const count = tracker[key].length;
    if (count > 0) pieceEl.classList.add(`piece-offset-${count}`);

    if (color === myColor && validMoves.includes(idx)) {
        pieceEl.classList.add('selectable');
        pieceEl.style.pointerEvents = 'auto';
        pieceEl.addEventListener('click', () => movePiece(idx));
    }

    cell.style.position = 'relative';
    cell.appendChild(pieceEl);
    tracker[key].push(pieceEl);
}

function progressToCoords(color, progress) {
    if (progress <= 0) return null;
    if (progress === 58) return CENTER;
    if (progress >= 53) return SAFE_LANES[color][progress - 53] || null;
    const absIdx = (START_OFFSETS[color] + progress - 1) % 52;
    return TRACK[absIdx] || null;
}

function renderHomePieces(state) {
    document.querySelectorAll('.home-piece-fill').forEach(e => e.remove());
    for (const color of COLORS) {
        if (!state.pieces[color]) continue;
        let homeIdx = 0;
        state.pieces[color].forEach((progress) => {
            if (progress === 0 && homeIdx < HOME_POS[color].length) {
                const [r,c] = HOME_POS[color][homeIdx];
                const cell = document.getElementById(`cell-${r}-${c}`);
                if (cell) {
                    cell.innerHTML = '';
                    const p = document.createElement('div');
                    p.className = `piece ${color} home-piece-fill`;
                    p.style.pointerEvents = 'none';
                    cell.appendChild(p);
                }
                homeIdx++;
            }
        });
    }
}

/* ---- Sidebar UI updates ---- */
function updateTurn(state) {
    if (!state) return;
    const cur = state.current_color;
    const bots = state.bots || [];
    if (turnDotEl)  turnDotEl.className = `turn-dot ${cur}`;
    if (turnNameEl) {
        const player = playersList.find(p => p.color === cur);
        const label = player?.player_name || COLOR_ZH[cur];
        const isBot = bots.includes(cur);
        turnNameEl.textContent = label + (isBot ? ' (AI)' : '') + ' 的回合';
    }

    const isMyTurn = myColor && cur === myColor && !isBotThinking;
    if (rollBtn) {
        rollBtn.disabled = !(isMyTurn && !state.dice_rolled && gameStatus === 'playing');
    }
}

function updateDice(val) {
    if (!diceEl) return;
    diceEl.textContent = val ? (DICE_FACES[val] || val) : '?';
}

function updateMyPieces(state) {
    if (!myPiecesEl || !myColor || !state.pieces[myColor]) return;
    myPiecesEl.innerHTML = '';
    state.pieces[myColor].forEach((progress, idx) => {
        const btn = document.createElement('button');
        btn.className = `my-piece-btn ${myColor}`;
        btn.textContent = idx + 1;
        btn.title = `棋子 ${idx+1}: ${progressLabel(progress)}`;

        if (progress === 58) btn.classList.add('finished');
        if (validMoves.includes(idx) && !isBotThinking) {
            btn.classList.add('selectable');
            btn.onclick = () => movePiece(idx);
        } else {
            btn.disabled = true;
        }
        myPiecesEl.appendChild(btn);
    });
}

function progressLabel(p) {
    if (p === 0)  return '在基地';
    if (p === 58) return '已到達終點';
    if (p >= 53)  return `安全通道第 ${p-52} 格`;
    return `主路第 ${p} 格`;
}

function updatePlayersList(players) {
    if (!playersListEl) return;
    playersList = players;
    playersListEl.innerHTML = '';
    players.forEach(p => {
        const li = document.createElement('li');
        li.className = `player-item player-${p.color}`;
        li.innerHTML = `
            <span class="player-dot ${p.color}"></span>
            <span class="player-name">${escHtml(p.player_name)}</span>
            ${p.is_bot  ? '<span class="bot-badge">AI</span>' : ''}
            ${p.is_host && !p.is_bot ? '<span class="host-badge">房主</span>' : ''}
            ${p.color === myColor ? '<span class="me-badge">我</span>' : ''}
        `;
        playersListEl.appendChild(li);
    });
}

function addLog(msg) {
    if (!logEl) return;
    const li = document.createElement('li');
    li.className = 'log-entry';
    li.textContent = msg;
    logEl.prepend(li);
    while (logEl.children.length > 50) logEl.removeChild(logEl.lastChild);
}

function showBotThinking(color) {
    isBotThinking = true;
    if (botStatusEl) {
        botStatusEl.classList.remove('hidden');
        const txt = document.getElementById('bot-status-text');
        if (txt) txt.textContent = `${COLOR_ZH[color]} AI 正在思考...`;
    }
    if (rollBtn) rollBtn.disabled = true;
}

function hideBotThinking() {
    isBotThinking = false;
    if (botStatusEl) botStatusEl.classList.add('hidden');
}

function displayBotActions(botActions) {
    if (!botActions || botActions.length === 0) return;
    botActions.forEach(a => {
        const colorZh = COLOR_ZH[a.color] || a.color;
        if (a.action === 'three_sixes') {
            addLog(`${colorZh} AI 連擲三個6，失去行動權`);
        } else if (a.action === 'no_moves') {
            addLog(`${colorZh} AI 擲出 ${a.dice} 點，無法移動`);
        } else {
            addLog(`${colorZh} AI 擲出 ${a.dice} 點，移動棋子 ${(a.piece ?? 0) + 1}`);
        }
    });
}

/* ---- Game Actions ---- */
window.rollDice = async function() {
    if (!rollBtn || rollBtn.disabled) return;
    rollBtn.disabled = true;
    if (diceEl) diceEl.classList.add('rolling');

    try {
        const res = await apiPost(`/games/${window.GAME_CODE}/roll`);
        if (diceEl) diceEl.classList.remove('rolling');

        if (!res.success) { if (rollBtn) rollBtn.disabled = false; return; }

        updateDice(res.dice);
        validMoves = res.valid_moves || [];

        if (res.three_sixes) {
            addLog('連擲三個6！失去行動權');
            validMoves = [];
        } else {
            addLog(`${COLOR_ZH[myColor]} 擲出 ${res.dice} 點`);
            if (validMoves.length === 0) addLog('無可移動的棋子，換人');
        }

        // Show bot actions that ran after our roll with no moves
        if (res.bot_actions) displayBotActions(res.bot_actions);

        if (res.state) {
            gameState = res.state;
            renderPieces(gameState);
            renderHomePieces(gameState);
            updateTurn(gameState);
            updateMyPieces(gameState);
            updateDice(gameState.dice_value);
        }

        if (res.winner) {
            gameStatus = 'finished';
            showWinner(res.winner, playersList);
        }
    } catch(e) {
        if (diceEl) diceEl.classList.remove('rolling');
        console.error(e);
    }
};

window.movePiece = async function(pieceIdx) {
    validMoves = [];
    updateMyPieces(gameState);

    // Show bot thinking indicator
    if (isSolo) showBotThinking(myColor);

    try {
        const res = await apiPost(`/games/${window.GAME_CODE}/move`, { piece_index: pieceIdx });

        hideBotThinking();

        if (!res.success) { addLog('移動失敗：' + (res.message || '')); return; }

        gameState = res.state;
        renderPieces(gameState);
        renderHomePieces(gameState);
        updateTurn(gameState);
        updateMyPieces(gameState);
        updateDice(gameState.dice_value);

        addLog(`${COLOR_ZH[myColor]} 移動棋子 ${pieceIdx + 1}`);

        // Show bot actions in log
        if (res.bot_actions) displayBotActions(res.bot_actions);

        if (res.winner) {
            gameStatus = 'finished';
            showWinner(res.winner, playersList);
        }
    } catch(e) {
        hideBotThinking();
        console.error(e);
    }
};

window.startGame = async function() {
    if (startBtn) startBtn.disabled = true;
    try {
        const res = await apiPost(`/games/${window.GAME_CODE}/start`);
        if (!res.success) {
            alert(res.message || '無法開始遊戲');
            if (startBtn) startBtn.disabled = false;
        }
    } catch(e) {
        console.error(e);
        if (startBtn) startBtn.disabled = false;
    }
};

/* ---- Polling ---- */
function startPolling() {
    // Solo games need less frequent polling (bots run server-side)
    const interval = isSolo ? 3000 : 2000;
    pollTimer = setInterval(fetchState, interval);
}

async function fetchState() {
    if (isBotThinking) return; // don't poll while waiting for response

    try {
        const res = await apiFetch(`/games/${window.GAME_CODE}/state`);
        const prevStatus = gameStatus;
        gameStatus = res.status;

        // Update my_color from server (handles tab_id-based identity)
        if (res.my_color && !myColor) {
            myColor = res.my_color;
        }

        updatePlayersList(res.players || []);
        if (playerCountEl) playerCountEl.textContent = res.players_count;
        if (startBtn) startBtn.disabled = (res.players_count < 2);

        if (res.status === 'playing' || res.status === 'finished') {
            if (prevStatus === 'waiting' && res.status === 'playing') {
                location.reload(); return;
            }
            if (res.game_state) {
                gameState = res.game_state;
                renderPieces(gameState);
                renderHomePieces(gameState);
                updateTurn(gameState);
                updateDice(gameState.dice_value);
                if (validMoves.length === 0) updateMyPieces(gameState);
            }
            if (res.status === 'finished' && gameState?.winner) {
                showWinner(gameState.winner, res.players);
                clearInterval(pollTimer);
            }
        }
    } catch(e) {
        console.error('Poll error:', e);
    }
}

function showWinner(winnerColor, players) {
    let overlay = document.getElementById('winner-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'winner-overlay';
        overlay.className = 'winner-overlay';
        overlay.innerHTML = `
            <div class="winner-card">
                <div class="trophy-icon">🏆</div>
                <h2>遊戲結束！</h2>
                <p id="winner-text"></p>
                <a href="/" class="btn btn-primary" style="margin-top:12px">回到首頁</a>
            </div>
        `;
        document.querySelector('.game-main')?.appendChild(overlay);
    }
    overlay.style.display = 'flex';
    // Re-trigger fade-in animation
    overlay.style.animation = 'none';
    overlay.offsetHeight; // force reflow
    overlay.style.animation = '';
    const winnerPlayer = (players || playersList).find(p => p.color === winnerColor);
    const name = winnerPlayer?.player_name || COLOR_ZH[winnerColor];
    const wt = document.getElementById('winner-text');
    if (wt) wt.textContent = `${name} (${COLOR_ZH[winnerColor]}) 獲勝！`;
}

/* ---- HTTP helpers ---- */
async function apiFetch(url) {
    const sep = url.includes('?') ? '&' : '?';
    const fullUrl = window.TAB_ID ? `${url}${sep}tab_id=${window.TAB_ID}` : url;
    const r = await fetch(fullUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Tab-Id': window.TAB_ID || '' } });
    return r.json();
}

async function apiPost(url, data = {}) {
    if (window.TAB_ID) data.tab_id = window.TAB_ID;
    const r = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
            'X-Tab-Id': window.TAB_ID || '',
        },
        body: JSON.stringify(data),
    });
    return r.json();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

window.copyCode = function(code) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.copy-btn');
        if (btn) {
            const orig = btn.textContent;
            btn.textContent = '✓ 已複製！';
            setTimeout(() => btn.textContent = orig, 1500);
        }
    });
};

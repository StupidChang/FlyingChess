/* =============================================
   FlyingChessOnline - Game Logic (Client)
   ============================================= */

const COLORS    = ['yellow', 'blue', 'green', 'red'];
const DICE_FACES = ['', '⚀','⚁','⚂','⚃','⚄','⚅'];

// Localized strings + locale-prefixed API endpoints injected by the Blade view
const I18N        = window.GAME_I18N  || {};
const GAME_ROUTES = window.GAME_ROUTES || {};
const COLOR_LABELS = I18N.colors || { yellow: 'yellow', blue: 'blue', green: 'green', red: 'red' };

/** Translate key with __PLACEHOLDER__ replacement, e.g. t('rolled', {'__N__': 5}) */
function t(key, repl) {
    let s = (I18N[key] != null) ? I18N[key] : key;
    if (repl) for (const k in repl) s = s.replace(k, repl[k]);
    return s;
}

// 3D dice — dot layouts per face & cube rotation to show each value
const DICE_DOTS_MAP = {
    1: [0,0,0, 0,1,0, 0,0,0],
    2: [0,0,1, 0,0,0, 1,0,0],
    3: [0,0,1, 0,1,0, 1,0,0],
    4: [1,0,1, 0,0,0, 1,0,1],
    5: [1,0,1, 0,1,0, 1,0,1],
    6: [1,0,1, 1,0,1, 1,0,1],
};
const DICE_FACE_ROT = {
    1: { x: 0,   y: 0 },
    2: { x: -90, y: 0 },
    3: { x: 0,   y: 90 },
    4: { x: 0,   y: -90 },
    5: { x: 90,  y: 0 },
    6: { x: 0,   y: 180 },
};
const DICE_IDLE_ROT = { x: -28, y: 36 };

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
let diceCubeEl  = null;   // .dice-cube3d element (built inside #dice)
let diceSrEl    = null;   // visually-hidden text for aria-live announcements
let dicePrevShown = null; // last dice value shown on the cube
let diceAnimating = false;
let diceQueuedVal = null; // value received (e.g. via polling) during an animation
let diceTumbleState = null;
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

    buildDiceCube();

    // Create bot status bar
    botStatusEl = document.createElement('div');
    botStatusEl.id = 'bot-status';
    botStatusEl.className = 'bot-status hidden';
    botStatusEl.innerHTML = '<span class="bot-spinner"></span> <span id="bot-status-text"></span>';
    botStatusEl.querySelector('#bot-status-text').textContent = t('botThinking');
    // Mounted inside .board-stage (not .game-main) — .board-stage reserves a fixed
    // top gutter sized exactly for this pill, so it floats above the board instead
    // of overlapping its top rows (see .board-stage / .bot-status in game.css).
    document.querySelector('.board-stage')?.prepend(botStatusEl);

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
                    cell.style.background = 'var(--surface2)';
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
                cell.style.background = 'var(--bg)';
                cell.style.border = 'none';
            }

            boardEl.appendChild(cell);
        }
    }

    setupPieceLayer();
}

/* ---- Piece Rendering ----
 * Pieces are persistent DOM nodes (created once in setupPieceLayer) placed inside
 * an absolutely-positioned overlay (.pieces-layer) that sits on top of the static
 * board grid. Re-rendering only updates each piece's `left`/`top` (in % of the
 * 15x15 board), so the CSS transition on .piece-slot animates the move instead of
 * the old remove+recreate approach which made transitions impossible to trigger.
 *
 * Multi-square moves are stepped one square at a time (~120ms/hop) so the piece
 * visibly hops along the path rather than jumping straight to the destination.
 * Trade-off: the rare "bounce back" overshoot (progress ends up <= previous
 * progress after reflecting off the finish) is rendered as a single smooth glide
 * rather than retracing every intermediate square — a full bounce replay wasn't
 * worth the added complexity for a corner case that ends on the same square.
 */
const CELL_PCT = 100 / 15;
const STEP_MS  = 120; // per-square hop duration when animating a multi-square move

let piecesLayerEl  = null;
let pieceEls       = {}; // pieceEls[color][idx] = { slot, piece }
let pieceProgress  = {}; // pieceProgress[color][idx] = last rendered progress (undefined = not yet rendered)

function cellForProgress(color, idx, progress) {
    if (progress === 0)  return HOME_POS[color][idx] || null;
    if (progress === 58) return CENTER;
    if (progress >= 53)  return SAFE_LANES[color][progress - 53] || null;
    const absIdx = (START_OFFSETS[color] + progress - 1) % 52;
    return TRACK[absIdx] || null;
}

/** Set a slot's grid position (in % of the board). animate=false snaps instantly. */
function positionSlot(slot, coords, animate) {
    if (!slot || !coords) return;
    const [r, c] = coords;
    if (!animate) {
        slot.style.transition = 'none';
        slot.style.left = (c * CELL_PCT) + '%';
        slot.style.top  = (r * CELL_PCT) + '%';
        void slot.offsetHeight; // force reflow so the next animated move isn't skipped
        slot.style.transition = '';
    } else {
        slot.style.left = (c * CELL_PCT) + '%';
        slot.style.top  = (r * CELL_PCT) + '%';
    }
}

/** Build the persistent piece overlay once, after the static board grid exists. */
function setupPieceLayer() {
    if (!boardEl) return;
    piecesLayerEl = document.createElement('div');
    piecesLayerEl.className = 'pieces-layer';
    boardEl.appendChild(piecesLayerEl);

    pieceEls = {};
    pieceProgress = {};
    COLORS.forEach(color => {
        pieceEls[color] = [];
        pieceProgress[color] = [];
        const count = (HOME_POS[color] || []).length;
        for (let idx = 0; idx < count; idx++) {
            const slot = document.createElement('div');
            slot.className = 'piece-slot';

            const piece = document.createElement('div');
            piece.className = `piece ${color}`;
            piece.dataset.color = color;
            piece.dataset.idx   = String(idx);
            piece.textContent   = idx + 1;
            piece.addEventListener('click', () => {
                if (color === myColor && !isBotThinking && validMoves.includes(idx)) movePiece(idx);
            });

            slot.appendChild(piece);
            piecesLayerEl.appendChild(slot);
            pieceEls[color][idx] = { slot, piece };
            pieceProgress[color][idx] = undefined;
            positionSlot(slot, HOME_POS[color][idx], false);
        }
    });
}

/** Animate one piece's slot from its previously rendered progress to the new one. */
function animatePieceTo(ref, color, idx, fromProgress, toProgress) {
    const slot = ref.slot;
    if (slot._stepTimer) { clearTimeout(slot._stepTimer); slot._stepTimer = null; }

    // First paint — snap in place, nothing to animate from.
    if (fromProgress === undefined) {
        positionSlot(slot, cellForProgress(color, idx, toProgress), false);
        return;
    }
    if (fromProgress === toProgress) return;

    // Respect the OS-level reduced-motion setting — jump straight to the result.
    if (prefersReducedMotion()) {
        positionSlot(slot, cellForProgress(color, idx, toProgress), false);
        return;
    }

    // Captured — sent straight back to base. Shrink+fade, relocate, then pop back in.
    if (toProgress === 0 && fromProgress > 0) {
        ref.piece.classList.add('piece-captured');
        slot._stepTimer = setTimeout(() => {
            ref.piece.classList.remove('piece-captured');
            positionSlot(slot, HOME_POS[color][idx], false);
            ref.piece.classList.add('piece-popin');
            setTimeout(() => ref.piece.classList.remove('piece-popin'), 300);
            slot._stepTimer = null;
        }, 240);
        return;
    }

    const delta = toProgress - fromProgress;

    // Backward bounce or a big catch-up jump (e.g. missed a poll cycle) — one smooth glide.
    if (delta <= 0 || delta > 8) {
        positionSlot(slot, cellForProgress(color, idx, toProgress), true);
        return;
    }

    // Forward move of 1-8 squares — hop one square at a time.
    let step = fromProgress;
    const hop = () => {
        step += 1;
        positionSlot(slot, cellForProgress(color, idx, step), true);
        if (step < toProgress) {
            slot._stepTimer = setTimeout(hop, STEP_MS);
        } else {
            slot._stepTimer = null;
        }
    };
    hop();
}

function renderPieces(state) {
    if (!boardEl || !state || !piecesLayerEl) return;

    // Group pieces landing on the same cell so overlapping ones can be nudged apart.
    const groups  = {};
    const targets = {};
    COLORS.forEach(color => {
        const arr = state.pieces[color];
        if (!arr) return;
        targets[color] = [];
        arr.forEach((progress, idx) => {
            const coords = cellForProgress(color, idx, progress);
            targets[color][idx] = coords;
            if (coords) {
                const key = coords[0] + ',' + coords[1];
                (groups[key] = groups[key] || []).push(`${color}:${idx}`);
            }
        });
    });

    COLORS.forEach(color => {
        const arr = state.pieces[color];
        if (!arr || !pieceEls[color]) return;
        arr.forEach((progress, idx) => {
            const ref = pieceEls[color][idx];
            if (!ref) return;
            const prevProgress = pieceProgress[color][idx];
            pieceProgress[color][idx] = progress;

            animatePieceTo(ref, color, idx, prevProgress, progress);

            const coords = targets[color][idx];
            const key    = coords ? `${coords[0]},${coords[1]}` : null;
            const group  = key ? groups[key] : null;
            ref.piece.classList.remove('piece-offset-1', 'piece-offset-2', 'piece-offset-3');
            if (group && group.length > 1) {
                const order = group.indexOf(`${color}:${idx}`);
                if (order > 0 && order <= 3) ref.piece.classList.add(`piece-offset-${order}`);
            }

            ref.piece.classList.toggle('piece-finished', progress === 58);

            const isSelectable = color === myColor && validMoves.includes(idx) && !isBotThinking;
            ref.piece.classList.toggle('selectable', isSelectable);
            ref.slot.style.zIndex = isSelectable ? 12 : (progress === 0 ? 6 : 10);
        });
    });
}

/* ---- Sidebar UI updates ---- */
function updateTurn(state) {
    if (!state) return;
    const cur = state.current_color;
    const bots = state.bots || [];
    if (turnDotEl)  turnDotEl.className = `turn-dot ${cur}`;
    if (turnNameEl) {
        const player = playersList.find(p => p.color === cur);
        const label = player?.player_name || COLOR_LABELS[cur];
        const isBot = bots.includes(cur);
        turnNameEl.textContent = t('turnOf', { '__NAME__': label + (isBot ? ' (AI)' : '') });
    }

    const isMyTurn = myColor && cur === myColor && !isBotThinking;
    if (rollBtn) {
        rollBtn.disabled = !(isMyTurn && !state.dice_rolled && gameStatus === 'playing');
    }
}

/* ---- 3D Dice ---- */
function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
}

/** Rebuild #dice content as a 3D cube (keeps #dice id + aria-live on #dice-display) */
function buildDiceCube() {
    if (!diceEl) return;
    diceEl.textContent = '';
    diceEl.classList.add('dice-idle');
    diceEl.setAttribute('aria-hidden', 'true');

    const cube = document.createElement('div');
    cube.className = 'dice-cube3d';
    for (let face = 1; face <= 6; face++) {
        const f = document.createElement('div');
        f.className = `dice-face3d df${face}`;
        DICE_DOTS_MAP[face].forEach(on => {
            const sp = document.createElement('span');
            if (on) sp.className = 'dot';
            f.appendChild(sp);
        });
        cube.appendChild(f);
    }
    diceEl.appendChild(cube);
    diceCubeEl = cube;
    setCubeTransform(DICE_IDLE_ROT.x, DICE_IDLE_ROT.y, false);

    // aria-live lives on #dice-display — announce values via hidden text
    diceSrEl = document.createElement('span');
    diceSrEl.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;';
    if (diceEl.parentElement) diceEl.parentElement.appendChild(diceSrEl);
}

function setCubeTransform(x, y, withTransition) {
    if (!diceCubeEl) return;
    diceCubeEl.style.transition = withTransition ? 'transform .3s ease' : 'none';
    diceCubeEl.style.transform = `rotateX(${x}deg) rotateY(${y}deg)`;
}

function announceDice(val) {
    if (diceSrEl) diceSrEl.textContent = val ? t('diceValue', { '__N__': val }) : '';
}

/** Snap/short-rotate to a face. Used by polling — never runs the full roll animation. */
function updateDice(val) {
    const v = val || null;
    if (!diceCubeEl) { if (diceEl) diceEl.textContent = v ? (DICE_FACES[v] || v) : '?'; return; }
    if (diceAnimating) { diceQueuedVal = v; return; }
    if (v === dicePrevShown) return;
    dicePrevShown = v;
    diceEl.classList.toggle('dice-idle', !v);
    const r = v ? DICE_FACE_ROT[v] : DICE_IDLE_ROT;
    setCubeTransform(r.x, r.y, !prefersReducedMotion());
    announceDice(v);
}

/** Free tumble (random-axis spin) while waiting for the server result */
function startDiceTumble() {
    diceAnimating = true;
    diceQueuedVal = null;
    if (!diceCubeEl || prefersReducedMotion()) return;
    diceEl.classList.remove('dice-idle');
    diceEl.classList.add('rolling3d');
    const s = {
        rx: DICE_IDLE_ROT.x, ry: DICE_IDLE_ROT.y,
        vx: 620 + Math.random() * 320,  // deg/s
        vy: 480 + Math.random() * 320,
        last: performance.now(), raf: 0,
    };
    diceTumbleState = s;
    const step = (now) => {
        const dt = Math.min((now - s.last) / 1000, .05);
        s.last = now;
        s.rx += s.vx * dt;
        s.ry += s.vy * dt;
        diceCubeEl.style.transition = 'none';
        diceCubeEl.style.transform = `rotateX(${s.rx.toFixed(1)}deg) rotateY(${s.ry.toFixed(1)}deg)`;
        s.raf = requestAnimationFrame(step);
    };
    s.raf = requestAnimationFrame(step);
}

/** Abort tumble (roll failed) and restore the previous face */
function stopDiceTumble() {
    if (diceTumbleState) { cancelAnimationFrame(diceTumbleState.raf); diceTumbleState = null; }
    if (diceEl) diceEl.classList.remove('rolling3d');
    diceAnimating = false;
    const v = diceQueuedVal !== null ? diceQueuedVal : dicePrevShown;
    diceQueuedVal = null;
    dicePrevShown = undefined; // force re-apply
    updateDice(v);
}

/** Decelerate from the tumble into the correct face, then bounce (squash & stretch) */
function landDiceOn(val) {
    return new Promise((resolve) => {
        const tgt = DICE_FACE_ROT[val] || DICE_FACE_ROT[1];

        const finish = () => {
            setCubeTransform(tgt.x, tgt.y, false);
            dicePrevShown = val;
            if (diceEl) diceEl.classList.remove('dice-idle');
            announceDice(val);
            diceAnimating = false;
            const queued = diceQueuedVal;
            diceQueuedVal = null;
            if (queued !== null && queued !== val) updateDice(queued);
            resolve();
        };

        if (!diceCubeEl || prefersReducedMotion()) {
            if (diceTumbleState) { cancelAnimationFrame(diceTumbleState.raf); diceTumbleState = null; }
            if (diceEl) diceEl.classList.remove('rolling3d');
            if (!diceCubeEl && diceEl) diceEl.textContent = DICE_FACES[val] || val;
            finish();
            return;
        }

        const s = diceTumbleState || { rx: DICE_IDLE_ROT.x, ry: DICE_IDLE_ROT.y, raf: 0 };
        if (diceTumbleState) { cancelAnimationFrame(diceTumbleState.raf); diceTumbleState = null; }

        // Target: 2 extra full turns above current rotation, easing out
        const fx = tgt.x + (Math.ceil(s.rx / 360) + 2) * 360;
        const fy = tgt.y + (Math.ceil(s.ry / 360) + 2) * 360;
        diceCubeEl.style.transition = 'none';
        diceCubeEl.style.transform = `rotateX(${s.rx.toFixed(1)}deg) rotateY(${s.ry.toFixed(1)}deg)`;
        void diceCubeEl.offsetHeight; // reflow so the transition starts from the tumble pose
        diceCubeEl.style.transition = 'transform .9s cubic-bezier(.16,.84,.3,1)';
        diceCubeEl.style.transform = `rotateX(${fx}deg) rotateY(${fy}deg)`;

        let done = false;
        const onEnd = () => {
            if (done) return;
            done = true;
            diceCubeEl.removeEventListener('transitionend', onEnd);
            clearTimeout(fallback);
            diceEl.classList.remove('rolling3d');
            diceEl.classList.add('dice-land');
            setTimeout(() => diceEl.classList.remove('dice-land'), 520);
            finish();
        };
        const fallback = setTimeout(onEnd, 1100);
        diceCubeEl.addEventListener('transitionend', onEnd);
    });
}

function updateMyPieces(state) {
    if (!myPiecesEl || !myColor || !state.pieces[myColor]) return;
    myPiecesEl.innerHTML = '';
    state.pieces[myColor].forEach((progress, idx) => {
        const btn = document.createElement('button');
        btn.className = `my-piece-btn ${myColor}`;
        btn.textContent = idx + 1;
        btn.title = `${t('pieceLabel', { '__N__': idx + 1 })}: ${progressLabel(progress)}`;

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
    if (p === 0)  return t('atBase');
    if (p === 58) return t('finished');
    if (p >= 53)  return t('safeLane', { '__N__': p - 52 });
    return t('mainTrack', { '__N__': p });
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
            ${p.is_host && !p.is_bot ? `<span class="host-badge">${escHtml(t('badgeHost'))}</span>` : ''}
            ${p.color === myColor ? `<span class="me-badge">${escHtml(t('badgeMe'))}</span>` : ''}
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
        if (txt) txt.textContent = t('botThinkingColor', { '__NAME__': COLOR_LABELS[color] });
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
        const colorLabel = COLOR_LABELS[a.color] || a.color;
        if (a.action === 'three_sixes') {
            addLog(t('botThreeSixes', { '__NAME__': colorLabel }));
        } else if (a.action === 'no_moves') {
            addLog(t('botNoMoves', { '__NAME__': colorLabel, '__N__': a.dice }));
        } else {
            addLog(t('botMoved', { '__NAME__': colorLabel, '__N__': a.dice, '__P__': (a.piece ?? 0) + 1 }));
        }
    });
}

/* ---- Game Actions ---- */
window.rollDice = async function() {
    if (!rollBtn || rollBtn.disabled) return;
    rollBtn.disabled = true;
    startDiceTumble(); // free spin while waiting for server

    try {
        const res = await apiPost(GAME_ROUTES.roll);

        if (!res.success) { stopDiceTumble(); if (rollBtn) rollBtn.disabled = false; return; }

        await landDiceOn(res.dice); // decelerate onto the real face + bounce
        validMoves = res.valid_moves || [];

        if (res.three_sixes) {
            addLog(t('threeSixes'));
            validMoves = [];
        } else {
            addLog(t('rolled', { '__NAME__': COLOR_LABELS[myColor], '__N__': res.dice }));
            if (validMoves.length === 0) addLog(t('noMoves'));
        }

        // Show bot actions that ran after our roll with no moves
        if (res.bot_actions) displayBotActions(res.bot_actions);

        if (res.state) {
            gameState = res.state;
            renderPieces(gameState);
            updateTurn(gameState);
            updateMyPieces(gameState);
            updateDice(gameState.dice_value);
        }

        if (res.winner) {
            gameStatus = 'finished';
            showWinner(res.winner, playersList);
        }
    } catch(e) {
        stopDiceTumble();
        console.error(e);
    }
};

window.movePiece = async function(pieceIdx) {
    validMoves = [];
    updateMyPieces(gameState);

    // Show bot thinking indicator
    if (isSolo) showBotThinking(myColor);

    try {
        const res = await apiPost(GAME_ROUTES.move, { piece_index: pieceIdx });

        hideBotThinking();

        if (!res.success) { addLog(t('moveFailed', { '__MSG__': res.message || '' })); return; }

        gameState = res.state;
        renderPieces(gameState);
        updateTurn(gameState);
        updateMyPieces(gameState);
        updateDice(gameState.dice_value);

        addLog(t('moved', { '__NAME__': COLOR_LABELS[myColor], '__N__': pieceIdx + 1 }));

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
        const res = await apiPost(GAME_ROUTES.start);
        if (!res.success) {
            alert(res.message || t('cannotStart'));
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
        const res = await apiFetch(GAME_ROUTES.state);
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
                <h2>${escHtml(t('gameOver'))}</h2>
                <p id="winner-text"></p>
                <a href="${GAME_ROUTES.home || '/'}" class="btn btn-primary" style="margin-top:12px">${escHtml(t('backHome'))}</a>
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
    const name = winnerPlayer?.player_name || COLOR_LABELS[winnerColor];
    const wt = document.getElementById('winner-text');
    if (wt) wt.textContent = t('winner', { '__NAME__': name, '__COLOR__': COLOR_LABELS[winnerColor] });
    spawnConfetti(overlay);
}

/** A small celebratory confetti burst behind the winner card. Runs once per overlay. */
function spawnConfetti(overlay) {
    if (!overlay || overlay.querySelector('.confetti-layer') || prefersReducedMotion()) return;
    const colors = ['#facc15', '#38bdf8', '#4ade80', '#f87171', '#d9a441', '#ffffff'];
    const layer = document.createElement('div');
    layer.className = 'confetti-layer';
    layer.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < 28; i++) {
        const piece = document.createElement('span');
        piece.className = 'confetti-piece';
        piece.style.left = (Math.random() * 100).toFixed(1) + '%';
        piece.style.background = colors[i % colors.length];
        piece.style.setProperty('--drift', ((Math.random() * 80) - 40).toFixed(0) + 'px');
        piece.style.animationDelay = (Math.random() * .6).toFixed(2) + 's';
        piece.style.animationDuration = (2.2 + Math.random() * 1.4).toFixed(2) + 's';
        layer.appendChild(piece);
    }
    overlay.appendChild(layer);
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
            btn.textContent = t('copied');
            setTimeout(() => btn.textContent = orig, 1500);
        }
    });
};

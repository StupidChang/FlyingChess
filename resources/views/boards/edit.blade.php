@extends('layouts.app')
@section('title','編輯棋盤 — ' . $board->name)
@section('styles')
<link rel="stylesheet" href="{{ asset('css/board.css') }}">
@endsection

@section('content')
<div class="edit-page">
    <div class="edit-toolbar">
        <div class="edit-toolbar-left">
            <h1 id="board-name-display">{{ $board->name }}</h1>
            <button class="btn btn-sm btn-outline" onclick="openBoardMeta()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
                改名稱
            </button>
        </div>
        <div class="edit-toolbar-right">
            <a href="{{ route('play.board', $board) }}" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                開始遊戲
            </a>
            <a href="{{ route('home') }}" class="btn btn-outline">← 返回</a>
        </div>
    </div>

    {{-- Tab navigation --}}
    <div class="tab-bar">
        <button class="tab-btn tab-active" data-tab="content" onclick="switchTab('content')">格子內容</button>
        <button class="tab-btn" data-tab="layout"  onclick="switchTab('layout')">版面配置</button>
        <button class="tab-btn" data-tab="path"    onclick="switchTab('path')">路徑設定</button>
    </div>

    {{-- Per-tab hint strips (toggled via tab-hidden) --}}
    <div id="tab-content" class="tab-panel">
        <p class="edit-hint">點擊任意格子即可編輯文字與顏色，變更自動儲存</p>
        <div class="color-legend">
            <span class="legend-title">格子顏色：</span>
            <span class="sq sq-action">親密動作</span>
            <span class="sq sq-drink">喝酒</span>
            <span class="sq sq-dare">大冒險</span>
            <span class="sq sq-truth">真心話</span>
            <span class="sq sq-strip">脫衣</span>
            <span class="sq sq-move">移動</span>
            <span class="sq sq-male">♂ 男生格</span>
            <span class="sq sq-female">♀ 女生格</span>
            <span class="sq sq-normal">普通</span>
        </div>
    </div>
    <div id="tab-layout" class="tab-panel tab-hidden">
        <p class="edit-hint">點擊空格加入格子；點擊 X 刪除格子；完成後切換至「路徑設定」</p>
    </div>
    <div id="tab-path" class="tab-panel tab-hidden">
        <p class="edit-hint">點擊格子加入 / 移除路徑；拖曳右側清單調整順序；完成後按「儲存路徑」</p>
    </div>

    {{-- Layout controls bar (hidden until layout tab) --}}
    <div id="layout-controls" class="layout-controls hidden">
        <div class="layout-canvas-controls">
            <span class="layout-label">畫布大小：</span>
            <label>行 <input id="canvas-rows-input" type="number" min="3" max="30"
                              class="canvas-size-input" value="{{ $board->canvas_rows }}"></label>
            <span style="color:var(--text-dim)">×</span>
            <label>列 <input id="canvas-cols-input" type="number" min="3" max="30"
                              class="canvas-size-input" value="{{ $board->canvas_cols }}"></label>
            <button onclick="applyCanvasSize()" class="btn btn-sm">套用大小</button>
        </div>
        <div class="layout-preset-controls">
            <span class="layout-label">套用預設：</span>
            <button onclick="applyPreset('cross')"  class="btn btn-sm">十字形（11×13）</button>
            <button onclick="applyPreset('square')" class="btn btn-sm">方形環（11×11）</button>
        </div>
    </div>

    {{-- Main area: board + path side panel (always in DOM) --}}
    <div class="edit-main">
        <div class="board-wrap">
            <div id="game-board" class="game-board edit-mode">
                {{-- rendered by JS --}}
            </div>
        </div>

        {{-- Path side panel (hidden until path tab) --}}
        <div id="path-side-panel" class="path-side-panel hidden">
            <div class="path-group-tabs">
                <button class="path-group-tab active" data-group="all"    onclick="selectPathGroup('all')">主路徑</button>
                <button class="path-group-tab"        data-group="male"   onclick="selectPathGroup('male')">♂ 男</button>
                <button class="path-group-tab"        data-group="female" onclick="selectPathGroup('female')">♀ 女</button>
            </div>
            <p id="path-group-hint" class="path-group-hint">主路徑：所有玩家默認使用此路徑</p>
            <ul id="path-list-ul" class="path-list-ul"></ul>
            <div class="path-panel-actions">
                <button onclick="clearCurrentPath()"   class="btn btn-sm btn-outline">清除</button>
                <button onclick="resetPathToDefault()" class="btn btn-sm btn-outline">重設</button>
                <button onclick="savePaths()"          class="btn btn-gold btn-sm">儲存路徑</button>
            </div>
            <div id="path-save-status" class="save-status path-save-status"></div>
        </div>
    </div>
</div>

{{-- Edit Square Modal --}}
<div id="sq-modal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-overlay" onclick="closeSqModal()"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="closeSqModal()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <h2>編輯格子 <span id="sq-pos-label" class="sq-pos-badge">#0</span></h2>

        <div class="form-group">
            <label>格子文字</label>
            <textarea id="sq-text" class="form-control" rows="4" maxlength="200" placeholder="輸入格子上顯示的文字..."></textarea>
            <div class="char-count"><span id="sq-char">0</span>/200</div>
        </div>

        <div class="form-group">
            <label>格子顏色類型</label>
            <div class="color-picker">
                <label class="cp-opt"><input type="radio" name="sq-color" value="action"> <span class="sq sq-action">親密動作</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="drink">  <span class="sq sq-drink">喝酒</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="dare">   <span class="sq sq-dare">大冒險</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="truth">  <span class="sq sq-truth">真心話</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="strip">  <span class="sq sq-strip">脫衣</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="move">   <span class="sq sq-move">移動</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="male">   <span class="sq sq-male">♂ 男生格</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="female"> <span class="sq sq-female">♀ 女生格</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="normal"> <span class="sq sq-normal">普通</span></label>
            </div>
        </div>

        <div class="form-group">
            <label>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z"/>
                </svg>
                飛行目標格（完成挑戰後飛到）
            </label>
            <div style="display:flex;align-items:center;gap:8px">
                <input type="number" id="sq-fly-to" class="form-control"
                       min="0" max="999" placeholder="空白 = 不設定飛行"
                       style="width:160px">
                <span style="font-size:.78rem;color:var(--text-dim)">格子編號</span>
            </div>
            <div style="font-size:.75rem;color:var(--text-dim);margin-top:4px">
                設定後，遊戲中此格會出現「完成挑戰可飛行」與「執行懲罰」兩個按鈕供玩家選擇
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-gold btn-full" onclick="saveSquare()">儲存</button>
        </div>

        <div id="sq-save-status" class="save-status"></div>
    </div>
</div>

{{-- Board Meta Modal --}}
<div id="meta-modal" class="modal" role="dialog">
    <div class="modal-overlay" onclick="closeMetaModal()"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="closeMetaModal()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <h2>棋盤設定</h2>
        <div class="form-group">
            <label>棋盤名稱</label>
            <input type="text" id="meta-name" class="form-control" maxlength="100">
        </div>
        <div class="form-group">
            <label>說明</label>
            <textarea id="meta-desc" class="form-control" rows="3" maxlength="500"></textarea>
        </div>
        <button class="btn btn-gold btn-full" onclick="saveMeta()">儲存</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.BOARD_ID      = {{ $board->id }};
window.BOARD_NAME    = {{ Js::from($board->name) }};
window.BOARD_DESC    = {{ Js::from($board->description ?? '') }};
window.SQUARES_DATA  = @json($squares);
window.PATH_DATA     = @json($pathData);
window.CANVAS_ROWS   = {{ $board->canvas_rows }};
window.CANVAS_COLS   = {{ $board->canvas_cols }};
window.CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').content;
window.EDIT_MODE     = true;
</script>
<script src="{{ asset('js/board.js') }}"></script>
<script src="{{ asset('js/board-editor.js') }}"></script>
@endsection

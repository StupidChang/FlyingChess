@extends('layouts.app')
@section('title', __('profile.edit_title') . ' — ' . __('ui.site_name'))
@section('robots', 'noindex,nofollow')

@section('content')
@php $theme = $user->themeMeta(); @endphp
<div class="pf-wrap container" style="--pf-accent:{{ $theme['accent'] }};--pf-from:{{ $theme['from'] }};--pf-to:{{ $theme['to'] }}">

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="pf-form">
        @csrf @method('PATCH')

        <input type="hidden" name="avatar_pos_x" id="pf-avatar-pos-x" value="{{ $user->avatar_pos_x ?? 50 }}">
        <input type="hidden" name="avatar_pos_y" id="pf-avatar-pos-y" value="{{ $user->avatar_pos_y ?? 50 }}">
        <input type="hidden" name="banner_pos_x" id="pf-banner-pos-x" value="{{ $user->banner_pos_x ?? 50 }}">
        <input type="hidden" name="banner_pos_y" id="pf-banner-pos-y" value="{{ $user->banner_pos_y ?? 50 }}">

        {{-- 預覽:換圖(尚未上傳)也會先顯示在這裡。調整範圍在下方各自的彈窗裡做。 --}}
        <div class="pf-banner" id="pf-banner-preview"
             @if($user->bannerUrl()) style="background-image:url('{{ $user->bannerUrl() }}');background-position:{{ $user->bannerPosition() }}" @endif></div>
        <div class="pf-headline">
            <div class="pf-avatar" id="pf-avatar-box">
                <img src="{{ $user->avatarUrl() ?: '' }}" alt="{{ $user->name }}" id="pf-avatar-preview"
                     style="object-position:{{ $user->avatarPosition() }};@unless($user->avatarUrl()) display:none @endunless">
                <span class="pf-avatar-fallback" id="pf-avatar-fallback" @if($user->avatarUrl()) style="display:none" @endif>{{ $user->initial() }}</span>
            </div>
            <div class="pf-headline-text">
                <h1>{{ $user->name }}</h1>
                <p>{{ $user->city ? __('profile.lives_in', ['city' => $user->city]) : __('profile.edit_intro') }}</p>
            </div>
            <div class="pf-headline-actions">
                @if($user->profile_public)
                <a href="{{ route('profile.public', $user) }}" class="btn btn-sm btn-outline">{{ __('profile.view_public') }}</a>
                @endif
                <a href="{{ route('profile.index') }}" class="btn btn-sm btn-outline">{{ __('profile.back_to_profile') }}</a>
            </div>
        </div>
        @if($errors->any())<div class="alert alert-error" style="margin:18px 0">{{ $errors->first() }}</div>@endif

        {{-- 頭像 --}}
        <section class="pf-card">
            <h2>{{ __('profile.section_avatar') }}</h2>
            <div class="pf-avatar-row">
                <div class="pf-avatar pf-avatar-sm">
                    <img src="{{ $user->avatarUrl() ?: '' }}" alt="{{ $user->name }}" id="pf-avatar-thumb"
                         style="object-position:{{ $user->avatarPosition() }};@unless($user->avatarUrl()) display:none @endunless">
                    <span class="pf-avatar-fallback" id="pf-avatar-thumb-fb" @if($user->avatarUrl()) style="display:none" @endif>{{ $user->initial() }}</span>
                </div>
                <div class="pf-avatar-controls">
                    <label class="btn btn-sm btn-outline">
                        @include('partials.icon', ['name' => 'upload'])
                        {{ $user->avatarUrl() ? __('profile.avatar_change2') : __('profile.avatar_upload2') }}
                        <input type="file" name="avatar" id="pf-avatar-input" accept="image/jpeg,image/png,image/webp" class="pf-file">
                    </label>
                    <button type="button" class="btn btn-sm btn-outline" id="pf-avatar-adjust" @unless($user->avatarUrl()) hidden @endunless>
                        @include('partials.icon', ['name' => 'crop']) {{ __('profile.adjust') }}
                    </button>
                    <label class="pf-remove-btn"><input type="checkbox" name="remove_avatar" value="1" id="pf-avatar-remove">{{ __('profile.avatar_remove') }}</label>
                    <p class="pf-hint">{{ __('profile.avatar_hint') }}</p>
                </div>
            </div>
        </section>

        {{-- 橫幅 --}}
        <section class="pf-card">
            <h2>{{ __('profile.section_banner') }}</h2>
            <div class="pf-banner-thumb" id="pf-banner-thumb"
                 @if($user->bannerUrl()) style="background-image:url('{{ $user->bannerUrl() }}');background-position:{{ $user->bannerPosition() }}" @else data-empty="1" @endif></div>
            <div class="pf-avatar-controls" style="margin-top:12px">
                <label class="btn btn-sm btn-outline">
                    @include('partials.icon', ['name' => 'upload'])
                    {{ $user->bannerUrl() ? __('profile.banner_change') : __('profile.banner_upload') }}
                    <input type="file" name="banner" id="pf-banner-input" accept="image/jpeg,image/png,image/webp" class="pf-file">
                </label>
                <button type="button" class="btn btn-sm btn-outline" id="pf-banner-adjust" @unless($user->bannerUrl()) hidden @endunless>
                    @include('partials.icon', ['name' => 'crop']) {{ __('profile.adjust') }}
                </button>
                <label class="pf-remove-btn"><input type="checkbox" name="remove_banner" value="1" id="pf-banner-remove">{{ __('profile.banner_remove') }}</label>
                <p class="pf-hint">{{ __('profile.banner_hint') }}</p>
            </div>
        </section>

        {{-- 基本資料 --}}
        <section class="pf-card">
            <h2>{{ __('profile.section_basics') }}</h2>
            <label class="pf-field">
                <span>{{ __('profile.name_label') }}</span>
                <input type="text" name="name" maxlength="50" value="{{ old('name', $user->name) }}" required>
            </label>
            <label class="pf-field">
                <span>{{ __('profile.city_label') }}</span>
                <input type="text" name="city" maxlength="60" value="{{ old('city', $user->city) }}" placeholder="{{ __('profile.city_placeholder') }}">
            </label>
            <label class="pf-field">
                <span>{{ __('profile.looking_for_label') }}</span>
                <input type="text" name="looking_for" maxlength="120" value="{{ old('looking_for', $user->looking_for) }}" placeholder="{{ __('profile.looking_for_placeholder') }}">
            </label>
            <label class="pf-field">
                <span>{{ __('profile.bio_label') }}</span>
                <textarea name="bio" maxlength="500" rows="4" placeholder="{{ __('profile.bio_placeholder') }}">{{ old('bio', $user->bio) }}</textarea>
            </label>
        </section>

        {{-- 配色 --}}
        <section class="pf-card">
            <h2>{{ __('profile.section_theme') }}</h2>
            <p class="pf-hint" style="margin:0 0 14px">{{ __('profile.theme_hint') }}</p>
            <div class="pf-theme-grid">
                @foreach($themes as $key => $t)
                <label class="pf-swatch" style="--sw-from:{{ $t['from'] }};--sw-to:{{ $t['to'] }}">
                    <input type="radio" name="profile_theme" value="{{ $key }}"
                           @checked(($user->profile_theme ?? config('profile.default_theme')) === $key)>
                    <span class="pf-swatch-chip"></span>
                    <span class="pf-swatch-name">{{ $t['name'] }}</span>
                </label>
                @endforeach
            </div>
        </section>

        {{-- 公開設定 --}}
        <section class="pf-card">
            <h2>{{ __('profile.section_privacy') }}</h2>
            <label class="pf-toggle">
                <input type="checkbox" name="profile_public" value="1" @checked($user->profile_public)>
                <span class="pf-toggle-track"><span class="pf-toggle-dot"></span></span>
                <span class="pf-toggle-label">{{ __('profile.public_label') }}</span>
            </label>
            <p class="pf-hint" style="margin:10px 0 14px">{{ __('profile.public_hint') }}</p>

            <div class="pf-subtoggles">
                <label class="pf-toggle">
                    <input type="checkbox" name="show_traits" value="1" @checked($user->show_traits ?? true)>
                    <span class="pf-toggle-track"><span class="pf-toggle-dot"></span></span>
                    <span class="pf-toggle-label">{{ __('profile.show_traits_label') }}</span>
                </label>
                <label class="pf-toggle">
                    <input type="checkbox" name="show_boards" value="1" @checked($user->show_boards ?? true)>
                    <span class="pf-toggle-track"><span class="pf-toggle-dot"></span></span>
                    <span class="pf-toggle-label">{{ __('profile.show_boards_label') }}</span>
                </label>
                <p class="pf-hint" style="margin:6px 0 0">{{ __('profile.public_sections_hint') }}</p>
            </div>
        </section>

        <div style="margin:26px 0 60px">
            <button type="submit" class="btn btn-theme btn-xl">{{ __('profile.save') }}</button>
        </div>
    </form>

    {{-- 裁切彈窗:顯示整張圖,拖曳/縮放方框選要保留的範圍。只有框內會被裁切上傳。 --}}
    <div class="pf-crop" id="pf-crop" hidden aria-modal="true" role="dialog">
        <div class="pf-crop-box">
            <h3 id="pf-crop-title"></h3>
            <div class="pf-crop-stage" id="pf-crop-stage">
                <img id="pf-crop-src" alt="" draggable="false">
                <div class="pf-crop-sel" id="pf-crop-sel">
                    <div class="pf-crop-grid" aria-hidden="true"></div>
                    <span class="pf-crop-handle" id="pf-crop-handle"></span>
                </div>
            </div>
            <p class="pf-hint" style="text-align:center;margin:12px 0 0">{{ __('profile.crop_hint') }}</p>
            <div class="pf-crop-actions">
                <button type="button" class="btn btn-outline" id="pf-crop-cancel">{{ __('profile.crop_cancel') }}</button>
                <button type="button" class="btn btn-theme" id="pf-crop-apply">{{ __('profile.crop_apply') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

    // 每種圖:裁切框比例、輸出尺寸、要同步更新的預覽元素。
    var TYPES = {
        avatar: {
            aspect: 1, outW: 512, outH: 512, round: true, title: @json(__('profile.crop_avatar_title')),
            input: 'pf-avatar-input', adjust: 'pf-avatar-adjust', remove: 'pf-avatar-remove',
            targets: [ ['pf-avatar-preview', 'object'], ['pf-avatar-thumb', 'object'] ],
            fallbacks: ['pf-avatar-fallback', 'pf-avatar-thumb-fb']
        },
        banner: {
            aspect: 4.8, outW: 1440, outH: 300, round: false, title: @json(__('profile.crop_banner_title')),
            input: 'pf-banner-input', adjust: 'pf-banner-adjust', remove: 'pf-banner-remove',
            targets: [ ['pf-banner-preview', 'bg'], ['pf-banner-thumb', 'bg'] ],
            fallbacks: []
        }
    };

    var modal = $('pf-crop'), stage = $('pf-crop-stage'), img = $('pf-crop-src'),
        sel = $('pf-crop-sel'), handle = $('pf-crop-handle'), titleEl = $('pf-crop-title');
    var cur = null, curType = null, orig = {}, pending = null, box = { x: 0, y: 0, w: 0, h: 0 };

    function setPreview(cfg, url, x, y) {
        cfg.targets.forEach(function (t) {
            var el = $(t[0]); if (!el) return;
            if (t[1] === 'object') { if (url) { el.src = url; el.style.display = ''; } el.style.objectPosition = x + '% ' + y + '%'; }
            else { if (url) el.style.backgroundImage = "url('" + url + "')"; el.style.backgroundPosition = x + '% ' + y + '%'; el.removeAttribute('data-empty'); }
        });
        cfg.fallbacks.forEach(function (f) { var e = $(f); if (e && url) e.style.display = 'none'; });
    }

    function layoutSel() {
        sel.style.left = box.x + 'px'; sel.style.top = box.y + 'px';
        sel.style.width = box.w + 'px'; sel.style.height = box.h + 'px';
    }
    function initBox() {
        var sw = img.clientWidth, sh = img.clientHeight, a = cur.aspect;
        var w = Math.min(sw, sh * a), h = w / a;
        box = { x: (sw - w) / 2, y: (sh - h) / 2, w: w, h: h };
        layoutSel();
    }

    function openCrop(type, url) {
        cur = TYPES[type]; curType = type;
        titleEl.textContent = cur.title;
        sel.classList.toggle('is-round', !!cur.round);
        img.onload = initBox;
        img.src = url;
        modal.hidden = false; document.body.style.overflow = 'hidden';
        if (img.complete && img.naturalWidth) initBox();
    }
    function closeCrop() { modal.hidden = true; document.body.style.overflow = ''; cur = null; }
    // 取消:若是「剛選檔還沒裁切」就把那個檔案丟掉(避免上傳未裁切的原圖)
    function cancelCrop() {
        if (pending) { $(TYPES[pending].input).value = ''; orig[pending] = null; pending = null; }
        closeCrop();
    }

    // 拖曳移動 / 角落縮放(維持比例)
    var mode = null, sX, sY, sBox;
    sel.addEventListener('pointerdown', function (e) {
        if (e.target === handle) return;
        mode = 'move'; sX = e.clientX; sY = e.clientY; sBox = { x: box.x, y: box.y };
        sel.setPointerCapture(e.pointerId); e.preventDefault();
    });
    handle.addEventListener('pointerdown', function (e) {
        mode = 'resize'; sX = e.clientX; sBox = { w: box.w }; e.preventDefault(); e.stopPropagation();
        handle.setPointerCapture(e.pointerId);
    });
    document.addEventListener('pointermove', function (e) {
        if (!cur || !mode) return;
        var sw = img.clientWidth, sh = img.clientHeight;
        if (mode === 'move') {
            box.x = clamp(sBox.x + (e.clientX - sX), 0, sw - box.w);
            box.y = clamp(sBox.y + (e.clientY - sY), 0, sh - box.h);
        } else {
            var w = clamp(sBox.w + (e.clientX - sX), 48, sw - box.x);
            var h = w / cur.aspect;
            if (box.y + h > sh) { h = sh - box.y; w = h * cur.aspect; }
            box.w = w; box.h = h;
        }
        layoutSel();
    });
    document.addEventListener('pointerup', function () { mode = null; });

    // 套用:把框內的部分用 canvas 裁出來,取代檔案輸入的檔案(只上傳裁切後的圖)
    $('pf-crop-apply').addEventListener('click', function () {
        if (!cur) return;
        var scaleX = img.naturalWidth / img.clientWidth, scaleY = img.naturalHeight / img.clientHeight;
        var canvas = document.createElement('canvas');
        canvas.width = cur.outW; canvas.height = cur.outH;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, cur.outW, cur.outH);
        ctx.drawImage(img, box.x * scaleX, box.y * scaleY, box.w * scaleX, box.h * scaleY, 0, 0, cur.outW, cur.outH);
        var type = curType, cfg = cur;
        canvas.toBlob(function (blob) {
            if (!blob) return;
            var dt = new DataTransfer();
            dt.items.add(new File([blob], type + '.jpg', { type: 'image/jpeg' }));
            $(cfg.input).files = dt.files;
            setPreview(cfg, URL.createObjectURL(blob), 50, 50);
            var adj = $(cfg.adjust); if (adj) adj.hidden = false;
            var rm = $(cfg.remove); if (rm) rm.checked = false;
            pending = null;
            closeCrop();
        }, 'image/jpeg', 0.9);
    });
    $('pf-crop-cancel').addEventListener('click', cancelCrop);
    modal.addEventListener('click', function (e) { if (e.target === modal) cancelCrop(); });

    // 選新檔 → 讀原圖 → 開彈窗選範圍(orig 記著原圖給「調整範圍」重用)
    function onPick(inputId, type) {
        var input = $(inputId); if (!input) return;
        input.addEventListener('change', function () {
            var f = input.files && input.files[0]; if (!f) return;
            var r = new FileReader();
            r.onload = function () { orig[type] = r.result; pending = type; openCrop(type, r.result); };
            r.readAsDataURL(f);
        });
    }
    onPick('pf-avatar-input', 'avatar');
    onPick('pf-banner-input', 'banner');

    function bindAdjust(btnId, type, previewId, isBg) {
        var btn = $(btnId); if (!btn) return;
        btn.addEventListener('click', function () {
            var src = orig[type];
            if (!src) {
                var el = $(previewId);
                src = isBg ? (el.style.backgroundImage || '').replace(/^url\(["']?/, '').replace(/["']?\)$/, '') : el.getAttribute('src');
            }
            if (src) openCrop(type, src);
        });
    }
    bindAdjust('pf-avatar-adjust', 'avatar', 'pf-avatar-preview', false);
    bindAdjust('pf-banner-adjust', 'banner', 'pf-banner-preview', true);

    function bindRemove(type, previewId, thumbId, isBg) {
        var rm = $('pf-' + type + '-remove'); if (!rm) return;
        rm.addEventListener('change', function () {
            if (!rm.checked) return;
            $('pf-' + type + '-input').value = ''; orig[type] = null;
            var adj = $('pf-' + type + '-adjust'); if (adj) adj.hidden = true;
            [previewId, thumbId].forEach(function (pid) {
                var el = $(pid); if (!el) return;
                if (isBg) { el.style.backgroundImage = ''; el.setAttribute('data-empty', '1'); }
                else { el.style.display = 'none'; }
            });
            if (type === 'avatar') { $('pf-avatar-fallback').style.display = ''; $('pf-avatar-thumb-fb').style.display = ''; }
        });
    }
    bindRemove('avatar', 'pf-avatar-preview', 'pf-avatar-thumb', false);
    bindRemove('banner', 'pf-banner-preview', 'pf-banner-thumb', true);
})();
</script>
@endpush
@endsection

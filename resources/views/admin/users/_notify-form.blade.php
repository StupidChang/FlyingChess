{{-- 站內通知的表單。單一會員與群發共用;群發多一個「對象」下拉。
     收件人看到的是純文字(換行保留),連結只收站內路徑。 --}}
<form action="{{ $action }}" method="POST" class="admin-form"
      @isset($confirm) data-confirm="{{ $confirm }}" onsubmit="return confirm(this.dataset.confirm)" @endisset>
    @csrf
    @isset($audiences)
    <div class="form-group">
        <label for="nt-audience">發送對象</label>
        <select id="nt-audience" name="audience" class="form-input">
            @foreach($audiences as $value => $label)
            <option value="{{ $value }}" @selected(old('audience') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p style="font-size:.8rem;color:var(--text-dim);margin-top:4px">已封鎖的帳號不會收到。送出後無法收回。</p>
    </div>
    @endisset
    <div class="form-group">
        <label for="nt-title">標題</label>
        <input type="text" id="nt-title" name="title" maxlength="80" required class="form-input" value="{{ old('title') }}">
    </div>
    <div class="form-group">
        <label for="nt-body">內容</label>
        <textarea id="nt-body" name="body" rows="4" maxlength="1000" required class="form-input">{{ old('body') }}</textarea>
    </div>
    <div class="form-group">
        <label for="nt-url">連結（選填）</label>
        <input type="text" id="nt-url" name="url" maxlength="255" class="form-input" placeholder="/tw/game-hall" value="{{ old('url') }}">
        <p style="font-size:.8rem;color:var(--text-dim);margin-top:4px">點通知時前往的站內頁面，以 / 開頭。留空就停在通知頁。</p>
    </div>
    <button type="submit" class="btn">發送通知</button>
</form>

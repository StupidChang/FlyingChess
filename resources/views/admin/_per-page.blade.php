@php
    $currentPerPage = request('per_page', '20');
    $controlId = 'admin-per-page-'.($location ?? 'bottom');
    $showLinks = $showLinks ?? true;
@endphp
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:16px 0">
    <form method="GET" action="{{ url()->current() }}" style="display:flex;align-items:center;gap:8px">
        @foreach(request()->except(['page', 'per_page']) as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="{{ $controlId }}" style="font-size:.85rem;color:var(--text-dim)">每頁顯示</label>
        <select id="{{ $controlId }}" name="per_page" class="admin-search-input"
                style="width:auto;padding:7px 30px 7px 10px" onchange="this.form.submit()">
            @foreach(['20' => '20 筆', '50' => '50 筆', '100' => '100 筆', '200' => '200 筆', 'all' => '全部'] as $value => $label)
                <option value="{{ $value }}" @selected((string) $currentPerPage === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <span style="font-size:.82rem;color:var(--text-dim)">
        目前顯示 {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}／共 {{ $paginator->total() }} 筆
    </span>
    @if($showLinks)
        <div>{{ $paginator->links() }}</div>
    @endif
</div>

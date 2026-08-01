{{--
    可排序的表頭欄位。

    參數:
      $key    網址上的 sort 值,必須在控制器的白名單裡
      $label  顯示文字

    保留現有的篩選與搜尋(fullUrlWithQuery),但把 page 清掉 —— 換了排序還停在
    第 5 頁看到的是另一批資料,那不是使用者要的。
--}}
@php
    $active = request('sort') === $key;
    $dir = request('dir') === 'asc' ? 'asc' : 'desc';
    $next = $active && $dir === 'asc' ? 'desc' : 'asc';
@endphp
<th class="admin-th-sort {{ $active ? 'is-sorted' : '' }}">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next, 'page' => null]) }}"
       aria-label="{{ $label }}"
       @if($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
        {{ $label }}
        <span class="admin-sort-arrow" aria-hidden="true">{{ $active ? ($dir === 'asc' ? '▲' : '▼') : '↕' }}</span>
    </a>
</th>

{{--
    「全部」籤:把這一頁的篩選參數全部清掉。

    參數:
      $params  這一頁有哪些篩選參數,例如 ['category', 'audience', 'level', 'paid']

    刻意只清篩選,不動排序與每頁筆數 —— 那兩個是使用者對「怎麼看」的設定,
    跟「看哪些」是兩回事,清篩選時把排序一起洗掉會很煩。
--}}
@php
    $clear = ['page' => null];
    $anyActive = false;

    foreach ($params as $p) {
        $clear[$p] = null;
        if (! empty(request($p))) {
            $anyActive = true;
        }
    }
@endphp
<a href="{{ request()->fullUrlWithQuery($clear) }}"
   class="admin-filter-tab {{ $anyActive ? '' : 'active' }}">全部</a>

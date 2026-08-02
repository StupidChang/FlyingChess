{{--
    可排序的表頭欄位,而且可以疊好幾欄。

    參數:
      $key    網址上的 sort 值,必須在控制器的白名單裡
      $label  顯示文字

    點一下加進排序(遞增)、再點一下換成遞減、第三下把這一欄拿掉。網址是
    ?sort[]=category:asc&sort[]=level:asc,陣列順序就是優先順序,所以疊了兩欄
    以上時每一籤會標出自己是第幾順位。

    保留現有的篩選與搜尋(fullUrlWithQuery),但把 page 清掉 —— 換了排序還停在
    第 5 頁看到的是另一批資料。
--}}
@php
    $raw = (array) request('sort', []);

    // 舊寫法 ?sort=level&dir=asc(舊連結、書籤)也要看得懂
    if (count($raw) === 1 && ! str_contains((string) reset($raw), ':')) {
        $raw = [reset($raw).':'.(request('dir') === 'asc' ? 'asc' : 'desc')];
    }

    $specs = [];
    foreach ($raw as $item) {
        [$k, $d] = array_pad(explode(':', (string) $item, 2), 2, 'asc');
        if ($k !== '' && ! isset($specs[$k])) {
            $specs[$k] = $d === 'desc' ? 'desc' : 'asc';
        }
    }

    $dir = $specs[$key] ?? null;
    $position = $dir ? array_search($key, array_keys($specs), true) + 1 : null;

    /* 三段循環:沒排 → asc → desc → 移除。換方向時留在原本的順位上,
       不然改個方向就會被丟到最後,已經排好的優先順序整個亂掉。 */
    $next = $specs;
    if ($dir === null) {
        $next[$key] = 'asc';
    } elseif ($dir === 'asc') {
        $next[$key] = 'desc';
    } else {
        unset($next[$key]);
    }

    $nextParam = [];
    foreach ($next as $k => $d) {
        $nextParam[] = $k.':'.$d;
    }
@endphp
<th class="admin-th-sort {{ $dir ? 'is-sorted' : '' }}">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $nextParam ?: null, 'dir' => null, 'page' => null]) }}"
       @if($dir) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
        {{ $label }}
        <span class="admin-sort-arrow" aria-hidden="true">{{ $dir === 'asc' ? '▲' : ($dir === 'desc' ? '▼' : '↕') }}</span>
        @if($position && count($specs) > 1)
            <span class="admin-sort-order" aria-hidden="true">{{ $position }}</span>
        @endif
    </a>
</th>

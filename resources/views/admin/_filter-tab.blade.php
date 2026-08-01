{{--
    可複選的篩選籤。

    參數:
      $param  網址上的參數名,例如 'level'
      $value  這一籤的值
      $label  顯示文字

    點一下加入、再點一下移除,所以網址會長成 ?level[]=mild&level[]=medium。
    全部取消時參數整個拿掉,而不是留一個空陣列 —— 空陣列在網址上很醜,
    而且控制器兩種都要處理。換篩選時 page 一律清掉:條件變了還停在第 5 頁,
    看到的是另一批資料。
--}}
@php
    $current = array_map('strval', (array) request($param, []));
    $value = (string) $value;
    $active = in_array($value, $current, true);

    $next = $active
        ? array_values(array_diff($current, [$value]))
        : array_merge($current, [$value]);
@endphp
<a href="{{ request()->fullUrlWithQuery([$param => $next ?: null, 'page' => null]) }}"
   class="admin-filter-tab {{ $active ? 'active' : '' }}"
   aria-pressed="{{ $active ? 'true' : 'false' }}">{{ $label }}</a>

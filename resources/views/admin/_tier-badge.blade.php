{{--
    強度標籤。後台各處的強度／分級欄位都用這一支,顏色才不會一頁一個樣。

    參數:
      $key    原始的鍵。轉盤是 mild/medium/intense,題庫的骰子是「類別.強度」
              (action.gentle、prop.wild …)。骰子只有三階,對到 5 級裡的
              輕／中／重三個位置。
      $label  要顯示的文字。
--}}
@php
    $level = match (true) {
        $key === 'mild', str_ends_with($key, '.gentle') => 'mild',
        $key === 'mild_plus' => 'mild_plus',
        $key === 'medium', str_ends_with($key, '.bold') => 'medium',
        $key === 'medium_plus' => 'medium_plus',
        $key === 'intense', str_ends_with($key, '.wild') => 'intense',
        default => 'neutral',
    };
@endphp
<span class="badge-tier badge-tier--{{ $level }}">{{ $label }}</span>

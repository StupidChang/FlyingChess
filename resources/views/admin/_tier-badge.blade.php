{{--
    強度標籤。後台各處的強度／分級欄位都用這一支,顏色才不會一頁一個樣。

    參數:
      $key    原始的鍵。轉盤是 mild/medium/intense,題庫的骰子是「類別.強度」
              (action.gentle、prop.wild …),兩套詞彙都對應到同三個等級。
      $label  要顯示的文字。
--}}
@php
    $level = match (true) {
        $key === 'mild', str_ends_with($key, '.gentle') => 'mild',
        $key === 'medium', str_ends_with($key, '.bold') => 'medium',
        $key === 'intense', str_ends_with($key, '.wild') => 'intense',
        default => 'neutral',
    };
@endphp
<span class="badge-tier badge-tier--{{ $level }}">{{ $label }}</span>

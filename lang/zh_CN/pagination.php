<?php

// 純文字,不放 &laquo; / &raquo; 之類的 HTML entity —— 這兩個字串同時用在
// 按鈕文字與 aria-label,entity 在 aria-label 裡會被二次轉義,螢幕閱讀器
// 會直接念出「&raquo;」。方向指示由分頁 view 的 SVG 箭頭負責。
return [
    'previous' => '上一页',
    'next' => '下一页',
];

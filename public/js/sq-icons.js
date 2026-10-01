/* =====================================================
   格子類型圖示 —— 遊玩畫面、棋盤編輯器、大廳快速預覽共用
   題目類的格子(動作/大冒險/真心話/脫衣/喝酒)在遊玩畫面不上色,改用這組圖示分辨;
   顏色只留給會改變遊戲的格子(移動、性別、座位)。座位格(p1..p4)是一顆該座位
   棋子顏色的小圓,樣式在 board.css 的 .sq-seat-dot。
   要在 board.js 之前載入。
   ===================================================== */
const SQ_STROKE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';
const SQ_TYPE_ICONS = {
  action: SQ_STROKE + '<path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
  dare:   SQ_STROKE + '<path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/><path d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/></svg>',
  truth:  SQ_STROKE + '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.3c-.6.3-1 .9-1 1.6v.5M12 16.8h.01"/></svg>',
  strip:  SQ_STROKE + '<path d="M9 3.5 4 6l1.6 4.2L7.5 9.6V20h9V9.6l1.9.6L20 6l-5-2.5a3 3 0 0 1-6 0Z"/></svg>',
  drink:  SQ_STROKE + '<path d="M7.5 3.5h9l-.6 5.2a3.9 3.9 0 0 1-7.8 0L7.5 3.5ZM12 12.8v7.2M8.5 20h7"/></svg>',
  move:   SQ_STROKE + '<path d="M4 12h15M14 7l5 5-5 5"/></svg>',
  male:   SQ_STROKE + '<circle cx="10" cy="14" r="5.5"/><path d="M14 10l6-6M15 4h5v5"/></svg>',
  female: SQ_STROKE + '<circle cx="12" cy="9" r="5.5"/><path d="M12 14.5V21M9 18h6"/></svg>',
};
function sqTypeIconHtml(type) {
  if (/^p[1-4]$/.test(type)) return '<span class="sq-seat-dot seat-' + type.slice(1) + '"></span>';
  return SQ_TYPE_ICONS[type] || '';
}

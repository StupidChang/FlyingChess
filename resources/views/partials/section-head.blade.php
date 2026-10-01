{{--
    屬性測驗結果頁的區塊標題:一個圖示方塊加標題。

    參數
      icon    partials/article-icon 的圖示名(打錯字會退回小圓點,不會破版)
      title   標題文字(已經翻好的字串)
      colour  這一型的顏色代碼(rose / gold / indigo / green / neutral)

    顏色刻意只設在圖示方塊上,不設在整頁:.tt-c-* 設的是 --tt,而結果頁有十幾個
    元件都寫 var(--tt, …) 當後備色。把 --tt 掛到外層容器會一次改掉全部,包含
    長條、晶片、相關卡片的邊框 —— 那是另一件事,不該由「加圖示」順手改掉。
--}}
<h2 class="tt-sec-h">
    <span class="tt-sec-mark tt-c-{{ $colour ?? 'neutral' }}">@include('partials.article-icon', ['icon' => $icon, 'class' => 'tt-sec-ico'])</span>
    <span class="tt-sec-text">{{ $title }}</span>
</h2>

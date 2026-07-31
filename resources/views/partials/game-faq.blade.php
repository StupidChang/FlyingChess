{{--
    遊戲頁的可見 FAQ 區塊。

    參數:
      $faqKey  lang/*/faq.php 的 games 底下的鍵,例如 'card-game'

    沿用首頁 FAQ 的 class(faq-section / faq-item / faq-question / faq-answer),
    那些樣式 app.css 裡已經有了,所以不需要新增任何 CSS。

    第一題預設展開(open):<details> 收合起來的內容雖然在 HTML 裡,但把最常被問
    的那題直接攤開,對訪客與擷取內容的引擎都比較友善。

    :price 是 Premium 月費,從 config 帶入,免得日後改價還要動四個語言檔。
--}}
@php
    $faqItems = __('faq.games.'.$faqKey);
    // 佔位符集中在這裡:價格與看廣告的分鐘數都是設定值,寫死在語言檔的話
    // 一改設定,四個語系的 FAQ 就開始說謊(而且會被 AI 引擎照抄出去)。
    $faqVars = [
        ':price' => \App\Support\Pricing::entryPrice(),
        ':minutes' => \App\Support\PremiumAccess::rewardedMinutes(),
    ];
@endphp
@if(is_array($faqItems) && count($faqItems))
<section class="faq-section">
    <div class="faq-inner">
        <span class="section-label">{{ __('faq.label') }}</span>
        <h2 class="section-title">{{ __('faq.heading') }}</h2>
        <div class="faq-list">
            @foreach($faqItems as $i => $item)
            <details class="faq-item" @if($i === 0) open @endif>
                <summary class="faq-question">{{ $item['q'] }}</summary>
                <div class="faq-answer"><p>{{ strtr($item['a'], $faqVars) }}</p></div>
            </details>
            @endforeach
        </div>
    </div>
</section>
@endif

@extends('layouts.app')
@section('title', __('legal.terms_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('legal.terms_meta_description'))
@section('canonical', route('legal.terms'))
@section('content')
<div class="container" style="max-width:800px;padding:40px 16px">
    <h1 style="color:var(--gold);margin-bottom:8px">使用條款</h1>
    <p style="color:var(--text-dim);font-size:.9rem;margin-bottom:32px">最後更新：{{ date('Y') }} 年</p>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">一、服務說明</h2>
        <p>情侶飛行棋（以下簡稱「本站」）提供線上情侶飛行棋遊戲及自訂棋盤服務。本站包含成人向趣味內容，使用本服務即表示您確認已年滿 18 歲，並同意本條款之所有規定。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">二、使用資格</h2>
        <ul style="margin:0 0 0 20px;line-height:1.8">
            <li>您必須年滿 18 歲才能使用本站服務。</li>
            <li>您必須提供真實且有效的帳號資訊進行註冊。</li>
            <li>您不得使用機器人、爬蟲或自動化工具存取本站。</li>
        </ul>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">三、使用者行為規範</h2>
        <p>使用本站時，您同意不進行以下行為：</p>
        <ul style="margin:12px 0 0 20px;line-height:1.8">
            <li>上傳、輸入或散播含有違法、仇恨、歧視、或侵害他人權益的內容</li>
            <li>冒充他人或提供虛假資訊</li>
            <li>嘗試入侵、破壞或干擾本站的正常運作</li>
            <li>利用本站進行任何商業目的的非授權使用</li>
        </ul>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">四、自訂內容</h2>
        <p>您在本站建立的自訂棋盤及格子文字，由您自行負責其合法性與適當性。本站保留移除違反本條款或法律規定之內容的權利，恕不另行通知。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">五、帳號管理</h2>
        <p>您有責任妥善保管帳號及密碼。如發現帳號遭盜用，請立即聯絡我們。本站對因帳號管理不當造成的損失不承擔責任。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">六、免責聲明</h2>
        <p>本站服務以「現狀」提供，不保證服務不中斷或無錯誤。本站對因使用或無法使用本服務所造成的任何直接或間接損失，不承擔賠償責任。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">七、條款修改</h2>
        <p>本站保留隨時修改本條款的權利。修改後的條款將公告於本頁。繼續使用本站即視為接受修改後之條款。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">八、聯絡方式</h2>
        <p>如有任何問題，請聯絡：</p>
        <p style="margin-top:8px;color:var(--text-dim)">電子信箱：contact@flyingchessonline.com</p>
    </section>
</div>
@endsection

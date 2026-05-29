@extends('layouts.app')
@section('title', __('legal.privacy_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('legal.privacy_meta_description'))
@section('canonical', route('legal.privacy'))
@section('content')
<div class="container" style="max-width:800px;padding:40px 16px">
    <h1 style="color:var(--gold);margin-bottom:8px">隱私權政策</h1>
    <p style="color:var(--text-dim);font-size:.9rem;margin-bottom:32px">最後更新：{{ date('Y') }} 年</p>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">一、資料收集</h2>
        <p>本站（情侶飛行棋）在您使用服務時，可能收集以下資料：</p>
        <ul style="margin:12px 0 0 20px;line-height:1.8">
            <li>帳號資訊：電子信箱、使用者名稱（僅在您主動註冊時）</li>
            <li>遊戲資料：遊戲進度、自訂棋盤設定</li>
            <li>技術日誌：IP 位址、瀏覽器類型、存取時間（用於安全防護與系統維護）</li>
            <li>Cookie：用於維持登入狀態與功能偏好設定</li>
        </ul>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">二、Cookie 使用</h2>
        <p>本站使用 Cookie 維持您的登入階段與遊戲狀態。關閉 Cookie 可能導致部分功能無法正常運作。本站不使用追蹤型 Cookie 進行跨站行為分析。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">三、第三方廣告</h2>
        <p>本站可能顯示由第三方廣告服務商（如 Google AdSense）提供的廣告。這些服務商可能使用 Cookie 或網頁信標收集資料，以提供個人化廣告。您可透過廣告商的隱私設定頁面選擇退出個人化廣告。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">四、成人內容聲明</h2>
        <p>本站包含成人向趣味內容，僅供已年滿 18 歲之成年人使用。本站不主動收集未成年人之任何個人資料。若您發現有未成年人使用本站，請透過下方聯絡方式通報。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">五、資料安全</h2>
        <p>本站採用適當的技術措施保護您的個人資料，包含傳輸加密（HTTPS）及密碼雜湊儲存。然而，網際網路傳輸並無絕對安全的保障，請勿在本站儲存高度敏感的個人資訊。</p>
    </section>

    <section style="margin-bottom:32px">
        <h2 style="font-size:1.2rem;margin-bottom:12px">六、聯絡方式</h2>
        <p>如對本隱私權政策有任何疑問，或欲行使您的資料查詢、更正、刪除權利，請透過以下方式聯絡我們：</p>
        <p style="margin-top:8px;color:var(--text-dim)">電子信箱：contact@flyingchessonline.com</p>
    </section>

    <p style="font-size:.85rem;color:var(--text-dim);border-top:1px solid var(--border);padding-top:16px">
        本政策內容若有修改，將公告於本頁，並更新最後修改日期。繼續使用本站即表示您接受修改後之政策。
    </p>
</div>
@endsection

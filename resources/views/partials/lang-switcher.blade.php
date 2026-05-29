@php
    use App\Support\LocaleHelper;
    $current = app()->getLocale();
    $isMobile = $mobile ?? false;
    $unprefixedPath = LocaleHelper::stripLocalePrefix(request()->path());
@endphp

<div class="lang-switcher{{ $isMobile ? ' lang-switcher-mobile' : '' }}">
    <button
        type="button"
        class="lang-switcher-toggle"
        aria-haspopup="true"
        aria-expanded="false"
        onclick="this.parentElement.classList.toggle('open')"
        title="{{ __('ui.switch_language') }}">
        <span aria-hidden="true">🌐</span>
        <span class="lang-switcher-label">{{ LocaleHelper::supported()[$current]['native'] ?? 'Language' }}</span>
        <span class="toggle-arrow" aria-hidden="true">▾</span>
    </button>
    <ul class="lang-switcher-menu" role="menu">
        @foreach (LocaleHelper::supported() as $locale => $meta)
            <li role="none">
                <a role="menuitem"
                   hreflang="{{ $meta['hreflang'] }}"
                   href="{{ LocaleHelper::localizedUrl($locale, $unprefixedPath) }}"
                   class="lang-switcher-item @if($locale === $current) is-active @endif"
                   @if($locale === $current) aria-current="true" @endif>
                    {{ $meta['native'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>

{{-- Layout-only rules. Visual styling lives in public/css/app.css under "Premium polish". --}}
<style>
.lang-switcher{position:relative;display:inline-block;margin-left:8px;cursor:pointer;font-size:.88rem;line-height:1}
.lang-switcher-toggle{display:inline-flex;align-items:center;gap:6px;font:inherit;color:inherit;cursor:pointer}
.lang-switcher-toggle .toggle-arrow{font-size:.65rem;opacity:.7;transition:transform .2s}
.lang-switcher.open .lang-switcher-toggle .toggle-arrow{transform:rotate(180deg)}
.lang-switcher-menu{
  position:absolute;right:0;top:calc(100% + 6px);
  list-style:none;margin:0;
  z-index:120;
  display:none;
}
.lang-switcher.open .lang-switcher-menu{display:block}
.lang-switcher-item{text-decoration:none;white-space:nowrap}
/* Mobile variant: full width, inline (non-floating) menu */
.lang-switcher-mobile{display:block;margin:8px 0;width:100%}
.lang-switcher-mobile .lang-switcher-toggle{width:100%;justify-content:space-between;padding:9px 14px}
.lang-switcher-mobile .lang-switcher-menu{position:static;margin-top:6px;width:100%;display:none}
.lang-switcher-mobile.open .lang-switcher-menu{display:block}
</style>

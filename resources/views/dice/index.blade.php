@extends('layouts.app')
@section('title', __('minigame.dice_custom_title') . ' — ' . __('ui.site_name'))
@section('robots', 'noindex,nofollow')

@section('styles')
<style>
.md-wrap{max-width:720px;margin:0 auto;padding:40px 20px}
.md-head{text-align:center;margin-bottom:8px}
.md-head h1{font-size:1.6rem;font-weight:800;color:var(--text)}
.md-sub{text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:6px}
.md-back{display:block;text-align:center;color:var(--accent);font-size:.85rem;margin-bottom:24px}
.md-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px}
.md-card h2{font-size:1rem;font-weight:700;color:var(--text);margin-bottom:14px}
.md-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.md-field{flex:1 1 160px}
.md-field label{display:block;font-size:.8rem;color:var(--text-dim);margin-bottom:4px;font-weight:600}
.md-faces{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-bottom:12px}
.md-die{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px}
.md-die-cat{width:9px;height:9px;border-radius:3px;flex-shrink:0}
.md-cat-action{background:#e53935}.md-cat-part{background:#2563eb}.md-cat-time{background:#7c3aed}.md-cat-prop{background:#0d9488}
.md-die-info{flex:1;min-width:0}
.md-die-name{font-weight:700;color:var(--text)}
.md-die-faces{font-size:.82rem;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.md-die-actions{display:flex;gap:6px;flex-shrink:0}
.md-empty{text-align:center;color:var(--text-dim);padding:24px}
.md-err{color:#f87171;font-size:.82rem;margin-bottom:10px}
details.md-edit summary{cursor:pointer;color:var(--accent);font-size:.82rem;list-style:none}
details.md-edit summary::-webkit-details-marker{display:none}
</style>
@endsection

@section('content')
<div class="md-wrap">
    <div class="md-head"><h1>{{ __('minigame.dice_custom_title') }}</h1></div>
    <p class="md-sub">{{ __('minigame.dice_custom_subtitle') }}</p>
    <a href="{{ route('dice-game.show') }}" class="md-back">{{ __('minigame.dice_back_to_game') }}</a>

    @if($errors->any())
        <div class="md-card"><div class="md-err">{{ $errors->first() }}</div></div>
    @endif

    {{-- New die --}}
    <div class="md-card">
        <h2>{{ __('minigame.dice_custom_new') }}</h2>
        <form method="POST" action="{{ route('dice.store') }}">
            @csrf
            <div class="md-row">
                <div class="md-field">
                    <label>{{ __('minigame.dice_custom_name') }}</label>
                    <input type="text" name="name" class="form-control" maxlength="40" required value="{{ old('name') }}">
                </div>
                <div class="md-field">
                    <label>{{ __('minigame.dice_custom_category') }}</label>
                    <select name="category" class="form-control" required>
                        @foreach(\App\Models\Dice::CATEGORIES as $cat)
                            <option value="{{ $cat }}" @selected(old('category')===$cat)>{{ __('minigame.dice_label_'.$cat) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label style="font-size:.8rem;color:var(--text-dim);font-weight:600">{{ __('minigame.dice_custom_faces') }}</label>
            <div class="md-faces">
                @for($i=0;$i<6;$i++)
                    <input type="text" name="faces[]" class="form-control" maxlength="30" value="{{ old('faces.'.$i) }}">
                @endfor
            </div>
            <button type="submit" class="btn btn-gold">{{ __('minigame.dice_custom_save') }}</button>
        </form>
    </div>

    {{-- Existing dice --}}
    <div class="md-card">
        <h2>{{ __('minigame.dice_custom_title') }} ({{ $dice->count() }})</h2>
        @forelse($dice as $d)
            <div class="md-die">
                <span class="md-die-cat md-cat-{{ $d->category }}"></span>
                <div class="md-die-info">
                    <div class="md-die-name">{{ $d->name }} <span style="font-size:.75rem;color:var(--text-dim)">· {{ __('minigame.dice_label_'.$d->category) }}</span></div>
                    <div class="md-die-faces">{{ implode('、', $d->faces) }}</div>
                    <details class="md-edit">
                        <summary>✎ {{ __('minigame.dice_custom_name') }}</summary>
                        <form method="POST" action="{{ route('dice.update', $d) }}" style="margin-top:10px">
                            @csrf
                            @method('PATCH')
                            <div class="md-row">
                                <div class="md-field">
                                    <label>{{ __('minigame.dice_custom_name') }}</label>
                                    <input type="text" name="name" class="form-control" maxlength="40" required value="{{ $d->name }}">
                                </div>
                                <div class="md-field">
                                    <label>{{ __('minigame.dice_custom_category') }}</label>
                                    <select name="category" class="form-control" required>
                                        @foreach(\App\Models\Dice::CATEGORIES as $cat)
                                            <option value="{{ $cat }}" @selected($d->category===$cat)>{{ __('minigame.dice_label_'.$cat) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="md-faces">
                                @for($i=0;$i<6;$i++)
                                    <input type="text" name="faces[]" class="form-control" maxlength="30" value="{{ $d->faces[$i] ?? '' }}">
                                @endfor
                            </div>
                            <button type="submit" class="btn btn-sm btn-gold">{{ __('minigame.dice_custom_save') }}</button>
                        </form>
                    </details>
                </div>
                <div class="md-die-actions">
                    <form method="POST" action="{{ route('dice.destroy', $d) }}" onsubmit="return confirm(@json(__('minigame.dice_custom_delete_confirm')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline">{{ __('minigame.dice_custom_delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="md-empty">{{ __('minigame.dice_custom_empty') }}</p>
        @endforelse
    </div>
</div>
@endsection

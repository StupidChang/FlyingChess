{{--
    「逐漸升溫」開關。放在各遊戲的設定畫面,樣子與行為五個遊戲一致。

    開著:題目從最輕的一級開始,每 4 回合往上開放一級。
    關著:所有等級一開始就混在一起。
--}}
<label class="mg-escalate">
    {{-- 帶 name 是給真心話大冒險用的:那一頁是真的表單 POST,升溫要由伺服器決定。
         其餘四個遊戲在前端自己讀 .checked,多一個 name 不影響。 --}}
    <input type="checkbox" id="escalate-toggle" name="escalate" value="1">
    <span>
        <strong>{{ __('minigame.escalate_label') }}</strong>
        <em>{{ __('minigame.escalate_desc') }}</em>
    </span>
</label>

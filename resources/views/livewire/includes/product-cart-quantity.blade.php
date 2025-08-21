@php
$pid = $cart_item->id;
// cart option unit expected UPPERCASE: 'SQFT' (area) or 'PC' (pieces) etc.
$unit = $cart_item->options->unit ?? 'PC';
$isPC = ($unit === 'PC');
$isSHEET= ($unit === 'SHEET');
$isSQFT= ($unit === 'SQFT');
// ensure display_stock always numeric and prefer Livewire state
$display_stock = isset($check_quantity[$pid]) ? (float) $check_quantity[$pid] : (float) ($cart_item->options->stock ?? 0);
@endphp

@if($cart_instance === 'purchase')
<div class="input-group d-flex justify-content-center">
    <input wire:model.defer="quantity.{{ $pid }}"
        type="number"
        min="1"
        class="form-control"
        style="min-width: 40px; max-width: 90px;">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $cart_item->rowId }}', {{ $pid }})"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
@else
@if($isSQFT)
<div class="d-flex align-items-center justify-content-center gap-1">

    <input type="number"
        wire:model.defer="height.{{ $pid }}"
        step="0.01"
        min="0"
        class="form-control form-control-sm text-center"
        style="width: 50px;"
        placeholder="H"
        title="Height">

    <span class="mx-1">×</span>

    <input type="number"
        wire:model.defer="width.{{ $pid }}"
        step="0.01"
        min="0"
        class="form-control form-control-sm text-center"
        style="width: 50px;"
        placeholder="W"
        title="Width">

    <span class="mx-1">×</span>

    <input type="number"
        wire:model.defer="piece_qty.{{ $pid }}"
        step="1"
        min="1"
        class="form-control form-control-sm text-center"
        style="width: 50px;"
        placeholder="Qty"
        title="Number of Pieces">

    <span class="mx-1">=</span>

    <div class="input-group input-group-sm" style="width: 120px;">
        <input value="{{ number_format( (float)($height[$pid] ?? 0) * (float)($width[$pid] ?? 0) * (float)($piece_qty[$pid] ?? 0), 2 ) }}"
            class="form-control text-center"
            readonly
            style="background-color: #e9ecef;">
        <div class="input-group-append">
            <button type="button"
                wire:click="updateQuantity('{{ $cart_item->rowId }}', {{ $pid }})"
                class="btn btn-info">
                <i class="bi bi-check"></i>
            </button>
        </div>
    </div>
</div>

<div class="small text-muted ms-2" style="min-width: 80px; text-align: left;">

</div>
</div>
@elseif($isPC)
<div class="input-group d-flex justify-content-center">
    <input wire:model.defer="quantity.{{ $pid }}"
        type="number"
        min="1"
        class="form-control"
        style="min-width: 40px; max-width: 90px;">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $cart_item->rowId }}', {{ $pid }})"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>

    <div class="small text-muted ms-2" style="min-width: 80px; text-align: left;">

    </div>
</div>
@elseif($isSHEET)
<div class="d-flex align-items-center justify-content-start gap-2 mt-2">
    <input type="number"
        wire:model="sheets_used.{{ $pid }}"
        class="form-control form-control-sm text-center"
        style="width: 80px;"
        placeholder="Sheets"
        title="sheets_used">
    <span class="mx-1">=</span>

    <input type="number"
        wire:model="small_item_qty.{{ $pid }}"
        class="form-control form-control-sm text-center"
        style="width: 100px;"
        placeholder="Small Items">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $cart_item->rowId }}', {{ $pid }})"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
@endif
@endif
@php
$rowId = $cart_item->rowId;
$unit = $cart_item->options->unit ?? 'PC';

$isPC    = ($unit === 'PC');
$isSHEET = ($unit === 'SHEET');
$isSQFT  = ($unit === 'SQFT');

$display_stock = isset($check_quantity[$rowId])
    ? (float) $check_quantity[$rowId]
    : (float) ($cart_item->options->stock ?? 0);
@endphp

@if($cart_instance === 'purchase')
<div class="input-group d-flex justify-content-center">
    <input wire:model.defer="quantity.{{ $rowId }}"
        type="number" min="1"
        class="form-control"
        style="min-width: 40px; max-width: 90px;">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $rowId }}')"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
@else
@if($isSQFT)
<div class="d-flex align-items-center justify-content-center gap-1">
    <input type="number"
        wire:model.defer="height.{{ $rowId }}"
        step="0.01" min="0"
        class="form-control form-control-sm text-center"
        style="width: 50px;" placeholder="H">

    <span class="mx-1">×</span>

    <input type="number"
        wire:model.defer="width.{{ $rowId }}"
        step="0.01" min="0"
        class="form-control form-control-sm text-center"
        style="width: 50px;" placeholder="W">

    <span class="mx-1">×</span>

    <input type="number"
        wire:model.defer="piece_qty.{{ $rowId }}"
        step="1" min="1"
        class="form-control form-control-sm text-center"
        style="width: 50px;" placeholder="Qty">

    <span class="mx-1">=</span>

    <div class="input-group input-group-sm" style="width: 120px;">
        <input value="{{ number_format( (float)($height[$rowId] ?? 0) * (float)($width[$rowId] ?? 0) * (float)($piece_qty[$rowId] ?? 0), 2 ) }}"
            class="form-control text-center" readonly
            style="background-color: #e9ecef;">
        <div class="input-group-append">
            <button type="button"
                wire:click="updateQuantity('{{ $rowId }}')"
                class="btn btn-info">
                <i class="bi bi-check"></i>
            </button>
        </div>
    </div>
</div>
@elseif($isPC)
<div class="input-group d-flex justify-content-center">
    <input wire:model.defer="quantity.{{ $rowId }}"
        type="number" min="1"
        class="form-control"
        style="min-width: 40px; max-width: 90px;">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $rowId }}')"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
@elseif($isSHEET)
<div class="d-flex align-items-center justify-content-start gap-2 mt-2">
    <input type="number"
        wire:model="sheets_used.{{ $rowId }}"
        class="form-control form-control-sm text-center"
        style="width: 80px;" placeholder="Sheets">
    <span class="mx-1">=</span>
    <input type="number"
        wire:model="small_item_qty.{{ $rowId }}"
        class="form-control form-control-sm text-center"
        style="width: 100px;" placeholder="Small Items">
    <div class="input-group-append">
        <button type="button"
            wire:click="updateQuantity('{{ $rowId }}')"
            class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>
@endif
@endif

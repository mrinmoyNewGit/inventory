@php $pid = $cart_item->id; @endphp

@if($cart_instance === 'purchase')
    {{-- Purchase Qty --}}
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
    {{-- Sale Input --}}
    <div class="d-flex align-items-center justify-content-center gap-1">

        {{-- Height --}}
        <input type="number"
               wire:model.defer="height.{{ $pid }}"
               step="0.01"
               min="0"
               class="form-control form-control-sm text-center"
               style="width: 50px;"
               placeholder="H"
               title="Height (ft)">

        {{-- × --}}
        <span class="mx-1">×</span>

        {{-- Width --}}
        <input type="number"
               wire:model.defer="width.{{ $pid }}"
               step="0.01"
               min="0"
               class="form-control form-control-sm text-center"
               style="width: 50px;"
               placeholder="W"
               title="Width (ft)">

        {{-- × --}}
        <span class="mx-1">×</span>

        {{-- Piece Qty --}}
        <input type="number"
               wire:model.defer="piece_qty.{{ $pid }}"
               step="1"
               min="1"
               class="form-control form-control-sm text-center"
               style="width: 50px;"
               placeholder="Qty"
               title="Number of Pieces">

        {{-- = --}}
        <span class="mx-1">=</span>

        {{-- Total --}}
        <div class="input-group input-group-sm" style="width: 100px;">
            <input value="{{ number_format(($height[$pid] ?? 0) * ($width[$pid] ?? 0) * ($piece_qty[$pid] ?? 0), 2) }}"
                   class="form-control text-center"
                   readonly
                   style="background-color: #e9ecef;">
            <div class="input-group-append">
                <button type="button"
                        wire:click="updateQuantity('{{ $cart_item->rowId }}', {{ $pid }})"
                        class="btn btn-primary btn-sm">
                    <i class="bi bi-check"></i>
                </button>
            </div>
        </div>

    </div>
@endif

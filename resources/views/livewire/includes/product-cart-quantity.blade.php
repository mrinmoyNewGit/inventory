@php $pid = $cart_item->id; @endphp

@if($cart_instance === 'purchase')
    {{-- Original Purchase Input --}}
    <div class="input-group d-flex justify-content-center">
        <input wire:model="quantity.{{ $pid }}"
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
    {{-- Sale Input: Height × Width = Qty --}}
    <div x-data="{
        height: 0,
        width: 0,
        qty: 1,
        get total() {
            return (this.height * this.width * this.qty).toFixed(2);
        },
        updateQuantity() {
            $wire.set('quantity.{{ $pid }}', parseFloat(this.total));
        }
    }"
    x-init="updateQuantity();"
    @input.debounce.300ms="updateQuantity"
    class="d-flex align-items-center justify-content-center gap-1"
    >

        {{-- Height --}}
        <input type="number"
               x-model.number="height"
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
               x-model.number="width"
               step="0.01"
               min="0"
               class="form-control form-control-sm text-center"
               style="width: 50px;"
               placeholder="W"
               title="Width (ft)">

        {{-- = --}}
        <span class="mx-1">x</span>

        {{-- Quantity --}}
        <input type="number"
               x-model.number="qty"
               step="1"
               min="1"
               class="form-control form-control-sm text-center"
               style="width: 50px;"
               placeholder="Qty"
               title="Number of Pieces">

        {{-- Total Area Display --}}
        <span class="mx-1">=</span>
        <div class="input-group input-group-sm" style="width: 100px;">
            <input x-bind:value="total"
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

        {{-- Hidden sync --}}
        <input type="hidden" wire:model="quantity.{{ $pid }}" />
    </div>
@endif

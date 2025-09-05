<!-- Edit mode -->

    <div class="input-group d-flex justify-content-center">
        <input wire:model="unit_price.{{ $cart_item->rowId }}"
            style="min-width: 40px; max-width: 90px;"
            type="text"
            class="form-control"
            min="0">

        <div class="input-group-append">
            <button type="button"
                wire:click="updatePrice('{{ $cart_item->rowId }}')"
                @click="open{{ $cart_item->rowId }} = false"
                class="btn btn-info">
                <i class="bi bi-check"></i>
            </button>
        </div>
    </div>

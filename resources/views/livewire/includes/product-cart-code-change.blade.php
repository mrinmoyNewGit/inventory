<div class="input-group d-flex justify-content-center">
    <input wire:model="update_code.{{ $rowId }}"
           style="min-width: 40px; max-width: 90px;"
           type="text"
           class="form-control">

    <div class="input-group-append">
        <button type="button"
                wire:click="updateCode('{{ $rowId }}')"
                @click="$root.__x.$data.openCode = false"
                class="btn btn-info">
            <i class="bi bi-check"></i>
        </button>
    </div>
</div>

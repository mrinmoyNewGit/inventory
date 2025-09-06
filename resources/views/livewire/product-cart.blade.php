<div>
    <div>
        @if (session()->has('message'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="alert-body">
                <span>{{ session('message') }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        </div>
        @endif
        <div class="table-responsive position-relative">
            <div wire:loading.flex class="col-12 position-absolute justify-content-center align-items-center" style="top:0;right:0;left:0;bottom:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th class="align-middle">Product</th>
                        <th class="align-middle text-center">Net Unit Price</th>
                        <th class="align-middle text-center">Stock</th>
                        <th class="align-middle text-center">
                            @if($cart_instance === 'purchase')
                            Quantity
                            @else
                            Total Area (sqft)
                            @endif
                        </th>

                        <th class="align-middle text-center">Discount</th>
                        <!-- <th class="align-middle text-center">Tax</th> -->
                        <th class="align-middle text-center">Sub Total</th>
                        <th class="align-middle text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if($cart_items->isNotEmpty())
                    @foreach($cart_items as $cart_item)
                    <tr>
                        <td x-data="{ openCode: false }" wire:key="code-{{ $cart_item->rowId }}" class="align-middle">
                            @if ($cart_instance === 'purchase')
                                {{ $cart_item->name }}
                            @endif
                            <br>

                            <span x-show="!openCode" @click="openCode = true" class="badge badge-success p-1">
                                {{ $cart_item->options->code }}
                            </span>

                            <div x-show="openCode">
                                @include('livewire.includes.product-cart-code-change', ['rowId' => $cart_item->rowId])
                            </div>
                        </td>


                          <td x-data="{ openPrice: false }" wire:key="price-{{ $cart_item->rowId }}" class="align-middle text-center">
                            <span x-show="!openPrice" @click="openPrice = true">
                                {{ format_currency($cart_item->price) }}
                            </span>

                            <div x-show="openPrice">
                                @include('livewire.includes.product-cart-price', ['rowId' => $cart_item->rowId])
                            </div>
                        </td>



                        <td class="align-middle text-center text-center">
                            <span class="badge badge-info">{{ $cart_item->options->stock . ' ' . $cart_item->options->unit }}</span>
                        </td>

                        <td class="align-middle text-center">
                            @include('livewire.includes.product-cart-quantity')
                        </td>

                        <td class="align-middle text-center">
                            {{ format_currency($cart_item->options->product_discount) }}
                        </td>

                        <!-- <td class="align-middle text-center">
                            {{ format_currency($cart_item->options->product_tax) }}
                        </td> -->

                        <td class="align-middle text-center">
                            {{ format_currency($cart_item->subtotal) }}
                        </td>

                        <td class="align-middle text-center">
                            <a href="#" wire:click.prevent="removeItem('{{ $cart_item->rowId }}')">
                                <i class="bi bi-x-circle font-2xl text-danger"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="8" class="text-center">
                            <span class="text-danger">
                                Please search & select products!
                            </span>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="row justify-content-md-end">
        <div class="col-md-4">
            <div class="table-responsive">
                <table class="table table-striped">
                    <tr>
                        <th>Tax ({{ $global_tax }}%)</th>
                        <td>(+) {{ format_currency(Cart::instance($cart_instance)->tax()) }}</td>
                    </tr>
                    <tr>
                        <th>Discount ({{ $global_discount }})</th>
                        <td>(-) {{ format_currency(Cart::instance($cart_instance)->discount()) }}</td>
                    </tr>
                    <tr>
                        @if($cart_instance === 'purchase')
                        <th>Shipping</th>
                        @else
                        <th>Making Charge</th>
                        @endif
                        <input type="hidden" value="{{ $shipping }}" name="shipping_amount">
                        <td>(+) {{ format_currency($shipping) }}</td>
                    </tr>
                    <tr>
                        <th>Grand Total</th>
                        @php
                        $total_with_shipping = Cart::instance($cart_instance)->total() + (float) $shipping
                        @endphp
                        <th>
                            (=) {{ format_currency($total_with_shipping) }}
                        </th>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" name="total_amount" value="{{ $total_with_shipping }}">

    <div class="form-row">
        <div class="col-lg-4">
            <div class="form-group">
                <label for="tax_percentage">Tax (%)</label>
                <input wire:model.blur="global_tax" type="number" class="form-control" name="tax_percentage" min="0" max="100" value="{{ $global_tax }}" required>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-group">
                <label for="discount_percentage">Discount</label>
                <input wire:model.blur="global_discount" type="number" class="form-control" name="discount_percentage" min="0" value="{{ $global_discount }}" required>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-group">
                @if ($cart_instance === 'purchase')
                <label for="shipping_amount">Shipping</label>
                @else
                <label for="shipping_amount">Making Charge</label>
                @endif
                <input wire:model.blur="shipping" type="number" class="form-control" name="shipping_amount" min="0" value="0" required step="0.01">
            </div>
        </div>
    </div>
</div>
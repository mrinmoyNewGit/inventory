<?php

namespace App\Livewire;

use Gloudemans\Shoppingcart\Facades\Cart;
use Livewire\Component;
use Modules\Product\Entities\Product;

class ProductCart extends Component
{
    public $listeners = ['productSelected', 'discountModalRefresh'];

    public $cart_instance;
    public $global_discount;
    public $global_tax;
    public $shipping;

    public $quantity = [];
    public $check_quantity = [];
    public $discount_type = [];
    public $item_discount = [];
    public $unit_price = [];
    public $update_code = [];
    public $data;

    public $height = [];
    public $width = [];
    public $piece_qty = [];
    public $sheets_used = [];
    public $small_item_qty = [];

    protected const SQM_TO_SQFT = 10.7639;

    public function mount($cartInstance, $data = null)
    {
        $this->cart_instance = $cartInstance;

        if ($data) {
            $this->data = $data;
            $this->global_discount = $data->discount_percentage;
            $this->global_tax = $data->tax_percentage;
            $this->shipping = $data->shipping_amount;
            $this->updatedGlobalTax();
            $this->updatedGlobalDiscount();
        } else {
            $this->global_discount = 0;
            $this->global_tax = 0;
            $this->shipping = 0.00;
        }

        $this->refreshCart();
    }

    public function render()
    {
        $cart_items = Cart::instance($this->cart_instance)->content();
        return view('livewire.product-cart', [
            'cart_items' => $cart_items
        ]);
    }

    protected function productIsSQM(Product $product): bool
    {
        return ($product->product_unit === 'SQM');
    }

    public function productSelected($product)
    {
        $cart = Cart::instance($this->cart_instance);
        $product_model = Product::findOrFail($product['id']);

        $height = (float)($product['height'] ?? 1);
        $width = (float)($product['width'] ?? 1);
        $piece_qty = (float)($product['piece_qty'] ?? 1);
        $sheets_used = (float)($product['sheets_used'] ?? 0);
        $small_item_qty = (float)($product['small_item_qty'] ?? 0);

        $qty = 1.0;
        $price = (float)($product_model->product_price ?? 0);
        $stock = (float)($product_model->product_quantity ?? 0);
        $unit = ($product_model->product_unit ?? 'PC');

        if ($this->productIsSQM($product_model) && $this->cart_instance === 'sale') {
            $stock = round($product_model->product_quantity * self::SQM_TO_SQFT, 2);
            $price = $product_model->price_per_sqft ?: $product_model->product_price;
            $unit = 'SQFT';
            $qty = max(0, $height) * max(0, $width) * max(0, $piece_qty);
        } elseif ($unit === 'SHEET') {
            $qty = $sheets_used > 0 ? $sheets_used : ($small_item_qty > 0 ? 1 : 1);
            $stock = (float) $product_model->product_quantity;
        } else {
            $price = $product_model->product_price;
            $stock = $product_model->product_quantity;
        }

        $sub_total = $qty * $price;

        $cart->add([
            'id'      => $product_model->id,
            'name'    => $product_model->product_name,
            'qty'     => $qty,
            'price'   => $price,
            'weight'  => 1,
            'options' => [
                'uniq'                  => uniqid(), // 🔑 ensures rowId unique
                'product_discount'      => 0.00,
                'product_discount_type' => 'fixed',
                'sub_total'             => $sub_total,
                'code'                  => $product_model->product_code,
                'stock'                 => $stock,
                'unit'                  => $unit,
                'product_tax'           => 0.00,
                'unit_price'            => $price,
                'height'                => $height,
                'width'                 => $width,
                'piece_qty'             => $piece_qty,
                'sheets_used'           => $sheets_used,
                'small_item_qty'        => $small_item_qty,
            ],
        ]);

        $this->refreshCart();
    }

    protected function refreshCart()
    {
        $cart_items = Cart::instance($this->cart_instance)->content();

        foreach ($cart_items as $cart_item) {
            $rowId = $cart_item->rowId;

            $this->check_quantity[$rowId]  = (float) ($cart_item->options->stock ?? 0);
            $this->quantity[$rowId]        = (float) $cart_item->qty;
            $this->unit_price[$rowId]      = (float) $cart_item->price;
            $this->discount_type[$rowId]   = $cart_item->options->product_discount_type ?? 'fixed';
            $this->item_discount[$rowId]   = (float) ($cart_item->options->product_discount ?? 0);

            $this->height[$rowId]          = (float) ($cart_item->options->height ?? 0);
            $this->width[$rowId]           = (float) ($cart_item->options->width ?? 0);
            $this->piece_qty[$rowId]       = (float) ($cart_item->options->piece_qty ?? 0);
            $this->sheets_used[$rowId]     = (float) ($cart_item->options->sheets_used ?? 0);
            $this->small_item_qty[$rowId]  = (float) ($cart_item->options->small_item_qty ?? 0);
        }
    }

    public function updateQuantity($row_id)
    {
        $cart = Cart::instance($this->cart_instance);
        $cart_item = $cart->get($row_id);
        if (!$cart_item) return;

        $unit = $cart_item->options->unit ?? 'PC';

        if (in_array($unit, ['SQFT', 'SQM'], true) && ($this->cart_instance == 'sale' || $this->cart_instance == 'purchase_return')) {
            $calculated_qty = ($this->height[$row_id] ?? 0)
                * ($this->width[$row_id] ?? 0)
                * ($this->piece_qty[$row_id] ?? 0);
            $this->quantity[$row_id] = $calculated_qty;
        } elseif ($unit === 'SHEET' && $this->cart_instance == 'sale') {
            $sheets_used = (float) ($this->sheets_used[$row_id] ?? 0);
            $small_items = (float) ($this->small_item_qty[$row_id] ?? 0);
            $this->quantity[$row_id] = $sheets_used > 0 ? $sheets_used : ($small_items > 0 ? 1 : 1);
        }

        $cart->update($row_id, $this->quantity[$row_id]);

        $cart->update($row_id, [
            'options' => array_merge($cart_item->options->toArray(), [
                'height'        => $this->height[$row_id] ?? null,
                'width'         => $this->width[$row_id] ?? null,
                'piece_qty'     => $this->piece_qty[$row_id] ?? null,
                'sheets_used'   => $this->sheets_used[$row_id] ?? null,
                'small_item_qty' => $this->small_item_qty[$row_id] ?? null,
            ])
        ]);

        $this->refreshCart();
    }

    public function removeItem($row_id)
    {
        Cart::instance($this->cart_instance)->remove($row_id);
    }

    public function updatedGlobalTax()
    {
        Cart::instance($this->cart_instance)->setGlobalTax((int)$this->global_tax);
    }

    public function updatedGlobalDiscount()
    {
        $discount = (float) $this->global_discount; // ensure float
        Cart::instance($this->cart_instance)->setGlobalFixedDiscount($discount);
    }


    public function updatedDiscountType($value, $row_id)
    {
        $this->item_discount[$row_id] = 0;
    }

    public function discountModalRefresh($row_id)
    {
        $this->updateQuantity($row_id);
    }

    public function setProductDiscount($row_id)
    {
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);
        if (!$cart_item) return;

        if ($this->discount_type[$row_id] == 'fixed') {
            $new_price = ($cart_item->price + ($cart_item->options->product_discount ?? 0)) - $this->item_discount[$row_id];
            $discount_amount = $this->item_discount[$row_id];
        } else {
            $discount_amount = ($cart_item->price + ($cart_item->options->product_discount ?? 0)) * ($this->item_discount[$row_id] / 100);
            $new_price = ($cart_item->price + ($cart_item->options->product_discount ?? 0)) - $discount_amount;
        }

        Cart::instance($this->cart_instance)->update($row_id, [
            'price' => $new_price
        ]);

        $this->updateCartOptions($row_id, $cart_item, $discount_amount);

        session()->flash('discount_message' . $row_id, 'Discount added to the product!');
    }

    public function updatePrice($row_id)
    {
        $cart = Cart::instance($this->cart_instance);
        $cart_item = $cart->get($row_id);
        if (!$cart_item) return;

        $newPrice = (float) ($this->unit_price[$row_id] ?? $cart_item->price);

        $cart->update($row_id, [
            'price'   => $newPrice,
            'options' => array_merge($cart_item->options->toArray(), [
                'unit_price' => $newPrice,
            ]),
        ]);

        $this->refreshCart();
    }

    public function updateCode($row_id)
    {
        $cart = Cart::instance($this->cart_instance);
        $cart_item = $cart->get($row_id);
        if (!$cart_item) return;

        // New code or fallback to old code
        $newCode = $this->update_code[$row_id] ?? $cart_item->options->code;

        $cart->update($row_id, [
            'options' => array_merge($cart_item->options->toArray(), [
                'code' => $newCode, // update code instead of price
            ]),
        ]);

        $this->refreshCart();
    }


    public function calculate($product, $new_price = null)
    {
        if ($new_price) {
            $product_price = $new_price;
        } else {
            $this->unit_price[$product['id']] = $product['product_price'];
            if ($this->cart_instance == 'purchase' || $this->cart_instance == 'purchase_return') {
                $this->unit_price[$product['id']] = $product['product_cost'];
            }
            $product_price = $this->unit_price[$product['id']];
        }

        $price = $product_price;
        $unit_price = $product_price;
        $product_tax = 0;
        $sub_total = $product_price;

        if ($product['product_tax_type'] == 1) {
            $price = $product_price + ($product_price * ($product['product_order_tax'] / 100));
            $unit_price = $product_price;
            $product_tax = $product_price * ($product['product_order_tax'] / 100);
            $sub_total = $price;
        } elseif ($product['product_tax_type'] == 2) {
            $unit_price = $product_price - ($product_price * ($product['product_order_tax'] / 100));
            $product_tax = $product_price * ($product['product_order_tax'] / 100);
        }

        return [
            'price' => $price,
            'unit_price' => $unit_price,
            'product_tax' => $product_tax,
            'sub_total' => $sub_total
        ];
    }

    public function updateCartOptions($row_id, $cart_item, $discount_amount)
    {
        Cart::instance($this->cart_instance)->update($row_id, ['options' => [
            'sub_total'             => $cart_item->price * $cart_item->qty,
            'code'                  => $cart_item->options->code,
            'stock'                 => $cart_item->options->stock,
            'unit'                  => $cart_item->options->unit,
            'product_tax'           => $cart_item->options->product_tax,
            'unit_price'            => $cart_item->options->unit_price,
            'product_discount'      => $discount_amount,
            'product_discount_type' => $this->discount_type[$row_id],
            'height'                => $cart_item->options->height ?? 1,
            'width'                 => $cart_item->options->width ?? 1,
            'piece_qty'             => $cart_item->options->piece_qty ?? 1,
            'sheets_used'           => $cart_item->options->sheets_used ?? 0,
            'small_item_qty'        => $cart_item->options->small_item_qty ?? 0,
        ]]);
    }
}

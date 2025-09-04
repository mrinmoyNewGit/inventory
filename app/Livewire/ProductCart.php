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
    public $data;

    public $height = [];
    public $width = [];
    public $piece_qty = [];
    // NEW: fields for SHEET logic
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

            $cart_items = Cart::instance($this->cart_instance)->content();

            foreach ($cart_items as $cart_item) {
                $pid = $cart_item->id;

                // Ensure numeric stock (not array)
                $this->check_quantity[$pid] = (float) ($cart_item->options->stock ?? 0);

                $this->quantity[$pid] = (float) $cart_item->qty;
                $this->unit_price[$pid] = (float) $cart_item->price;
                $this->discount_type[$pid] = $cart_item->options->product_discount_type ?? 'fixed';

                if ($this->discount_type[$pid] === 'fixed') {
                    $this->item_discount[$pid] = (float) ($cart_item->options->product_discount ?? 0);
                } elseif ($this->discount_type[$pid] === 'percentage') {
                    $price = (float) $cart_item->price;
                    $discountValue = (float) ($cart_item->options->product_discount ?? 0);
                    $this->item_discount[$pid] = $price > 0 ? round(100 * ($discountValue / $price)) : 0;
                } else {
                    $this->item_discount[$pid] = 0;
                }

                $this->height[$pid] = (float) ($cart_item->options->height ?? 1);
                $this->width[$pid] = (float) ($cart_item->options->width ?? 1);
                $this->piece_qty[$pid] = (float) ($cart_item->options->piece_qty ?? 1);

                $this->sheets_used[$pid] = (float) ($cart_item->options->sheets_used ?? 0);
                $this->small_item_qty[$pid] = (float) ($cart_item->options->small_item_qty ?? 0);
            }
        } else {
            $this->global_discount = 0;
            $this->global_tax = 0;
            $this->shipping = 0.00;

            $this->check_quantity = [];
            $this->quantity = [];
            $this->unit_price = [];
            $this->discount_type = [];
            $this->item_discount = [];

            $this->height = [];
            $this->width = [];
            $this->piece_qty = [];

            $this->sheets_used = [];
            $this->small_item_qty = [];
        }
    }

    public function render()
    {
        $cart_items = Cart::instance($this->cart_instance)->content();

        return view('livewire.product-cart', [
            'cart_items' => $cart_items
        ]);
    }

    // Strict uppercase check as requested
    protected function productIsSQM(Product $product): bool
    {
        return ($product->product_unit === 'SQM');
    }

    public function productSelected($product)
    {
        $cart = Cart::instance($this->cart_instance);

        $exists = $cart->search(function ($cartItem) use ($product) {
            return $cartItem->id == $product['id'];
        });

        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product exists in the cart!');
            return;
        }

        $product_model = Product::findOrFail($product['id']);

        // defaults
        $height = isset($product['height']) ? (float) $product['height'] : 1;
        $width = isset($product['width']) ? (float) $product['width'] : 1;
        $piece_qty = isset($product['piece_qty']) ? (float) $product['piece_qty'] : 1;
        $sheets_used = isset($product['sheets_used']) ? (float) $product['sheets_used'] : 0;
        $small_item_qty = isset($product['small_item_qty']) ? (float) $product['small_item_qty'] : 0;

        $qty = 1.0;
        $price = (float) ($product_model->product_price ?? 0);
        $stock = (float) ($product_model->product_quantity ?? 0);
        $unit = ($product_model->product_unit ?? 'PC'); // UPPERCASE expected in DB

        // SQM -> convert to SQFT in cart
        if ($this->productIsSQM($product_model) && $this->cart_instance === 'sale') {
            $stock = round($product_model->product_quantity * self::SQM_TO_SQFT, 2);

            if (isset($product_model->price_per_sqft) && $product_model->price_per_sqft) {
                $price = (float) $product_model->price_per_sqft;
            } else {
                $price = (float) $product_model->product_price;
            }

            $unit = 'SQFT';
            $qty = max(0, $height) * max(0, $width) * max(0, $piece_qty); // in SQFT
        }
        // SHEET logic
        elseif ($unit === 'SHEET') {
            // Default: 1 sheet = 1 qty
            if ($sheets_used > 0) {
                $qty = $sheets_used;
            } elseif ($small_item_qty > 0) {
                // If only small items entered, user must decide how many sheets needed
                // System will not auto-calculate, just fallback to 1 for safety
                $qty = 1;
            } else {
                $qty = 1;
            }
            $stock = (float) $product_model->product_quantity;
        }
        // PC or other units
        else {
            $price = (float) $product_model->product_price;
            $stock = (float) $product_model->product_quantity;
            $unit = ($product_model->product_unit ?? 'PC');
        }

        $sub_total = $qty * $price;

        // add to cart
        $cart->add([
            'id'      => $product_model->id,
            'name'    => $product_model->product_name,
            'qty'     => $qty,
            'price'   => $price,
            'weight'  => 1,
            'options' => [
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

        $pid = $product_model->id;
        $this->check_quantity[$pid] = $stock;
        $this->quantity[$pid] = $qty;
        $this->discount_type[$pid] = 'fixed';
        $this->item_discount[$pid] = 0;
        $this->height[$pid] = $height;
        $this->width[$pid] = $width;
        $this->piece_qty[$pid] = $piece_qty;
        $this->sheets_used[$pid] = $sheets_used;
        $this->small_item_qty[$pid] = $small_item_qty;
        $this->unit_price[$pid] = $price;
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
        // Cart::instance($this->cart_instance)->setGlobalDiscount((int)$this->global_discount);
        Cart::instance($this->cart_instance)->setGlobalFixedDiscount($this->global_discount);

    }

    /**
     * Handles updates to cart quantity depending on unit type.
     * - SQM → area calculation
     * - PC → direct quantity
     * - SHEET → sheets_used OR small items to sheets conversion
     */
    public function updateQuantity($row_id, $product_id)
    {
        $cart = Cart::instance($this->cart_instance);
        $cart_item = $cart->get($row_id);
        if (!$cart_item) return;

        $unit = $cart_item->options->unit ?? 'PC';
        $isAreaCartUnit = in_array($unit, ['SQFT', 'SQM'], true);

        // SQM logic
        if ($isAreaCartUnit && ($this->cart_instance == 'sale' || $this->cart_instance == 'purchase_return')) {
            $calculated_qty = ($this->height[$product_id] ?? 0)
                * ($this->width[$product_id] ?? 0)
                * ($this->piece_qty[$product_id] ?? 0);

            $this->quantity[$product_id] = $calculated_qty;
            $available = (float) ($this->check_quantity[$product_id] ?? ($cart_item->options->stock ?? 0));

            if ($available < $calculated_qty) {
                session()->flash('message', 'The requested quantity is not available in stock.');
                return;
            }
        }
        // SHEET logic
        elseif ($unit === 'SHEET') {
            $sheets_used = (float) ($this->sheets_used[$product_id] ?? 0);
            $small_items = (float) ($this->small_item_qty[$product_id] ?? 0);

            if ($sheets_used > 0) {
                $this->quantity[$product_id] = $sheets_used;
            } elseif ($small_items > 0) {
                // user decides conversion externally, fallback assume 1 sheet
                $this->quantity[$product_id] = 1;
            } else {
                $this->quantity[$product_id] = 1;
            }

            $available = (float) ($this->check_quantity[$product_id] ?? ($cart_item->options->stock ?? 0));
            if ($available < $this->quantity[$product_id]) {
                session()->flash('message', 'The requested number of sheets is not available in stock.');
                return;
            }
        }
        // PC or other
        else {
            $available = (float) ($this->check_quantity[$product_id] ?? ($cart_item->options->stock ?? 0));
            if ($available < (float) ($this->quantity[$product_id] ?? 0)) {
                session()->flash('message', 'The requested quantity is not available in stock.');
                return;
            }
        }

        $cart->update($row_id, $this->quantity[$product_id]);

        $cart_item = $cart->get($row_id);

        $options = [
            'sub_total'             => $cart_item->price * $cart_item->qty,
            'code'                  => $cart_item->options->code ?? '',
            'stock'                 => $cart_item->options->stock ?? null,
            'unit'                  => $cart_item->options->unit ?? null,
            'product_tax'           => $cart_item->options->product_tax ?? null,
            'unit_price'            => $cart_item->options->unit_price ?? null,
            'product_discount'      => $cart_item->options->product_discount ?? null,
            'product_discount_type' => $cart_item->options->product_discount_type ?? null,
            'height'                => $this->height[$product_id] ?? $cart_item->options->height ?? 1,
            'width'                 => $this->width[$product_id] ?? $cart_item->options->width ?? 1,
            'piece_qty'             => $this->piece_qty[$product_id] ?? $cart_item->options->piece_qty ?? 1,
            'sheets_used'           => $this->sheets_used[$product_id] ?? $cart_item->options->sheets_used ?? 0,
            'small_item_qty'        => $this->small_item_qty[$product_id] ?? $cart_item->options->small_item_qty ?? 0,
        ];

        $cart->update($row_id, ['options' => $options]);
    }

    public function updatedDiscountType($value, $name)
    {
        $this->item_discount[$name] = 0;
    }

    public function discountModalRefresh($product_id, $row_id)
    {
        $this->updateQuantity($row_id, $product_id);
    }

    public function setProductDiscount($row_id, $product_id)
    {
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        if (!$cart_item) return;

        if ($this->discount_type[$product_id] == 'fixed') {
            Cart::instance($this->cart_instance)
                ->update($row_id, [
                    'price' => ($cart_item->price + ($cart_item->options->product_discount ?? 0)) - $this->item_discount[$product_id]
                ]);

            $discount_amount = $this->item_discount[$product_id];

            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);
        } elseif ($this->discount_type[$product_id] == 'percentage') {
            $discount_amount = ($cart_item->price + ($cart_item->options->product_discount ?? 0)) * ($this->item_discount[$product_id] / 100);

            Cart::instance($this->cart_instance)
                ->update($row_id, [
                    'price' => ($cart_item->price + ($cart_item->options->product_discount ?? 0)) - $discount_amount
                ]);

            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);
        }

        session()->flash('discount_message' . $product_id, 'Discount added to the product!');
    }

    public function updatePrice($row_id, $product_id)
    {
        $product = Product::findOrFail($product_id);
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);
        if (!$cart_item) return;

        Cart::instance($this->cart_instance)->update($row_id, ['price' => $this->unit_price[$product['id']]]);

        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => [
                'sub_total'             => $this->calculate($product, $this->unit_price[$product['id']])['sub_total'],
                'code'                  => $cart_item->options->code,
                'stock'                 => $cart_item->options->stock,
                'unit'                  => $cart_item->options->unit,
                'product_tax'           => $this->calculate($product, $this->unit_price[$product['id']])['product_tax'],
                'unit_price'            => $this->calculate($product, $this->unit_price[$product['id']])['unit_price'],
                'product_discount'      => $cart_item->options->product_discount,
                'product_discount_type' => $cart_item->options->product_discount_type,
                'height'                => $cart_item->options->height ?? 1,
                'width'                 => $cart_item->options->width ?? 1,
                'piece_qty'             => $cart_item->options->piece_qty ?? 1,
                'sheets_used'           => $cart_item->options->sheets_used ?? 0,
                'small_item_qty'        => $cart_item->options->small_item_qty ?? 0,
            ]
        ]);
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
        $price = 0;
        $unit_price = 0;
        $product_tax = 0;
        $sub_total = 0;

        if ($product['product_tax_type'] == 1) {
            $price = $product_price + ($product_price * ($product['product_order_tax'] / 100));
            $unit_price = $product_price;
            $product_tax = $product_price * ($product['product_order_tax'] / 100);
            $sub_total = $product_price + ($product_price * ($product['product_order_tax'] / 100));
        } elseif ($product['product_tax_type'] == 2) {
            $price = $product_price;
            $unit_price = $product_price - ($product_price * ($product['product_order_tax'] / 100));
            $product_tax = $product_price * ($product['product_order_tax'] / 100);
            $sub_total = $product_price;
        } else {
            $price = $product_price;
            $unit_price = $product_price;
            $product_tax = 0.00;
            $sub_total = $product_price;
        }

        return ['price' => $price, 'unit_price' => $unit_price, 'product_tax' => $product_tax, 'sub_total' => $sub_total];
    }

    public function updateCartOptions($row_id, $product_id, $cart_item, $discount_amount)
    {
        Cart::instance($this->cart_instance)->update($row_id, ['options' => [
            'sub_total'             => $cart_item->price * $cart_item->qty,
            'code'                  => $cart_item->options->code,
            'stock'                 => $cart_item->options->stock,
            'unit'                  => $cart_item->options->unit,
            'product_tax'           => $cart_item->options->product_tax,
            'unit_price'            => $cart_item->options->unit_price,
            'product_discount'      => $discount_amount,
            'product_discount_type' => $this->discount_type[$product_id],
            'height'                => $cart_item->options->height ?? 1,
            'width'                 => $cart_item->options->width ?? 1,
            'piece_qty'             => $cart_item->options->piece_qty ?? 1,
            'sheets_used'           => $cart_item->options->sheets_used ?? 0,
            'small_item_qty'        => $cart_item->options->small_item_qty ?? 0,
        ]]);
    }
}

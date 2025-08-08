<?php

namespace App\Livewire;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Request;
use Livewire\Component;
use Modules\Product\Entities\Product;

class ProductCart extends Component
{

    public $listeners = ['productSelected', 'discountModalRefresh'];

    public $cart_instance;
    public $global_discount;
    public $global_tax;
    public $shipping;
    public $quantity;
    public $check_quantity;
    public $discount_type;
    public $item_discount;
    public $unit_price;
    public $data;
    // For dimension-based sales (height × width × piece_qty = sqft)
    public $height = [];
    public $width = [];
    public $piece_qty = [];


    private $product;

    // public function mount($cartInstance, $data = null)
    // {
    //     $this->cart_instance = $cartInstance;

    //     if ($data) {
    //         $this->data = $data;

    //         $this->global_discount = $data->discount_percentage;
    //         $this->global_tax = $data->tax_percentage;
    //         $this->shipping = $data->shipping_amount;

    //         $this->updatedGlobalTax();
    //         $this->updatedGlobalDiscount();

    //         $cart_items = Cart::instance($this->cart_instance)->content();

    //         foreach ($cart_items as $cart_item) {
    //             $this->check_quantity[$cart_item->id] = [$cart_item->options->stock];
    //             $this->quantity[$cart_item->id] = $cart_item->qty;
    //             $this->unit_price[$cart_item->id] = $cart_item->price;
    //             $this->discount_type[$cart_item->id] = $cart_item->options->product_discount_type;
    //             if ($cart_item->options->product_discount_type == 'fixed') {
    //                 $this->item_discount[$cart_item->id] = $cart_item->options->product_discount;
    //             } elseif ($cart_item->options->product_discount_type == 'percentage') {
    //                 $this->item_discount[$cart_item->id] = round(100 * ($cart_item->options->product_discount / $cart_item->price));
    //             }
    //         }
    //     } else {
    //         $this->global_discount = 0;
    //         $this->global_tax = 0;
    //         $this->shipping = 0.00;
    //         $this->check_quantity = [];
    //         $this->quantity = [];
    //         $this->unit_price = [];
    //         $this->discount_type = [];
    //         $this->item_discount = [];
    //     }
    // }
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
            // dd($cart_items);

            foreach ($cart_items as $cart_item) {
                $product_id = $cart_item->id;

                $this->check_quantity[$product_id] = [$cart_item->options->stock];
                $this->quantity[$product_id] = $cart_item->qty;
                $this->unit_price[$product_id] = $cart_item->price;
                $this->discount_type[$product_id] = $cart_item->options->product_discount_type;

                // Initialize discount value
                if ($cart_item->options->product_discount_type == 'fixed') {
                    $this->item_discount[$product_id] = $cart_item->options->product_discount;
                } elseif ($cart_item->options->product_discount_type == 'percentage') {
                    $this->item_discount[$product_id] = round(100 * ($cart_item->options->product_discount / $cart_item->price));
                }

                // ✅ Initialize dimensional inputs (if present)
                $this->height[$cart_item->id] = $cart_item->options->height ?? 1;
                $this->width[$cart_item->id] = $cart_item->options->width ?? 1;
                $this->piece_qty[$cart_item->id] = $cart_item->options->piece_qty ?? 1;
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

            // ✅ Also initialize dimensional properties
            $this->height = [];
            $this->width = [];
            $this->piece_qty = [];
        }
    }

    public function render()
    {
        $cart_items = Cart::instance($this->cart_instance)->content();

        return view('livewire.product-cart', [
            'cart_items' => $cart_items
        ]);
    }

    public function productSelected($product)
    {
        $cart = Cart::instance($this->cart_instance);

        $exists = $cart->search(function ($cartItem, $rowId) use ($product) {
            return $cartItem->id == $product['id'];
        });

        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product exists in the cart!');
            return;
        }

        $product_model = Product::findOrFail($product['id']);

        // Accept dimensional inputs if passed from frontend
        $height = $product['height'] ?? 1;
        $width = $product['width'] ?? 1;
        $piece_qty = $product['piece_qty'] ?? 1;

        $qty = 1;
        if (strtolower($product_model->product_unit) === 'sqm' && $this->cart_instance === 'sale') {
            $price = round($product_model->price_per_sqft, 2);
            $stock = round($product_model->stock_in_sqft, 2);
            $unit = 'sqft';

            // Calculate actual sqft = height × width × number of pieces
            $qty = $height * $width * $piece_qty;
        } else {
            $price = $product_model->product_price;
            $stock = $product_model->product_quantity;
            $unit = $product_model->product_unit ?? 'pcs';
        }

        $sub_total = $qty * $price;

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

                // ✅ dimensional info
                'height'     => $height,
                'width'      => $width,
                'piece_qty'  => $piece_qty,
            ],
        ]);

        $this->check_quantity[$product_model->id] = $stock;
        $this->quantity[$product_model->id] = $qty;
        $this->discount_type[$product_model->id] = 'fixed';
        $this->item_discount[$product_model->id] = 0;
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
        Cart::instance($this->cart_instance)->setGlobalDiscount((int)$this->global_discount);
    }

    // public function updateQuantity($row_id, $product_id)
    // {
    //     if ($this->cart_instance == 'sale' || $this->cart_instance == 'purchase_return') {
    //         if ($this->check_quantity[$product_id] < $this->quantity[$product_id]) {
    //             session()->flash('message', 'The requested quantity is not available in stock.');
    //             return;
    //         }
    //     }

    //     Cart::instance($this->cart_instance)->update($row_id, $this->quantity[$product_id]);

    //     $cart_item = Cart::instance($this->cart_instance)->get($row_id);

    //     Cart::instance($this->cart_instance)->update($row_id, [
    //         'options' => [
    //             'sub_total'             => $cart_item->price * $cart_item->qty,
    //             'code'                  => $cart_item->options->code,
    //             'stock'                 => $cart_item->options->stock,
    //             'unit'                  => $cart_item->options->unit,
    //             'product_tax'           => $cart_item->options->product_tax,
    //             'unit_price'            => $cart_item->options->unit_price,
    //             'product_discount'      => $cart_item->options->product_discount,
    //             'product_discount_type' => $cart_item->options->product_discount_type,
    //         ]
    //     ]);
    // }
    public function updateQuantity($row_id, $product_id)
    {
        if ($this->cart_instance == 'sale' || $this->cart_instance == 'purchase_return') {
            if ($this->check_quantity[$product_id] < $this->quantity[$product_id]) {
                session()->flash('message', 'The requested quantity is not available in stock.');
                return;
            }
        }

        // Update quantity
        Cart::instance($this->cart_instance)->update($row_id, $this->quantity[$product_id]);

        // Get updated cart item
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        // Merge old options with new sub_total (preserves height, width, piece_qty, etc.)
        $updated_options = array_merge($cart_item->options->toArray(), [
            'sub_total' => $cart_item->price * $cart_item->qty,
        ]);

        // Update options
        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => $updated_options,
        ]);
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

        if ($this->discount_type[$product_id] == 'fixed') {
            Cart::instance($this->cart_instance)
                ->update($row_id, [
                    'price' => ($cart_item->price + $cart_item->options->product_discount) - $this->item_discount[$product_id]
                ]);

            $discount_amount = $this->item_discount[$product_id];

            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);
        } elseif ($this->discount_type[$product_id] == 'percentage') {
            $discount_amount = ($cart_item->price + $cart_item->options->product_discount) * ($this->item_discount[$product_id] / 100);

            Cart::instance($this->cart_instance)
                ->update($row_id, [
                    'price' => ($cart_item->price + $cart_item->options->product_discount) - $discount_amount
                ]);

            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);
        }

        session()->flash('discount_message' . $product_id, 'Discount added to the product!');
    }

    public function updatePrice($row_id, $product_id)
    {
        $product = Product::findOrFail($product_id);

        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

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

    // public function updateCartOptions($row_id, $product_id, $cart_item, $discount_amount)
    // {
    //     Cart::instance($this->cart_instance)->update($row_id, ['options' => [
    //         'sub_total'             => $cart_item->price * $cart_item->qty,
    //         'code'                  => $cart_item->options->code,
    //         'stock'                 => $cart_item->options->stock,
    //         'unit'                  => $cart_item->options->unit,
    //         'product_tax'           => $cart_item->options->product_tax,
    //         'unit_price'            => $cart_item->options->unit_price,
    //         'product_discount'      => $discount_amount,
    //         'product_discount_type' => $this->discount_type[$product_id],
    //     ]]);
    // }
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

            // ✅ retain dimensional data
            'height'     => $cart_item->options->height ?? 1,
            'width'      => $cart_item->options->width ?? 1,
            'piece_qty'  => $cart_item->options->piece_qty ?? 1,
        ]]);
    }
}

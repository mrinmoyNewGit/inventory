<?php

namespace Modules\Sale\Http\Controllers;

use Modules\Sale\DataTables\SalesDataTable;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Product;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Http\Requests\StoreSaleRequest;
use Modules\Sale\Http\Requests\UpdateSaleRequest;

class SaleController extends Controller
{
    public function index(SalesDataTable $dataTable)
    {
        abort_if(Gate::denies('access_sales'), 403);

        return $dataTable->render('sale::index');
    }

    public function create()
    {
        abort_if(Gate::denies('create_sales'), 403);

        Cart::instance('sale')->destroy();

        return view('sale::create');
    }

    public function store(StoreSaleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $due_amount = $request->total_amount - $request->paid_amount;

            if ($due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $sale = Sale::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'customer_name' => Customer::findOrFail($request->customer_id)->customer_name,
                'tax_percentage' => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount' => $request->shipping_amount * 100,
                'paid_amount' => $request->paid_amount * 100,
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'status' => $request->status,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('sale')->tax() * 100,
                'discount_amount' => Cart::instance('sale')->discount() * 100,
            ]);

            foreach (Cart::instance('sale')->content() as $cart_item) {
                // Retrieve dimensions and piece quantity from options (set via Livewire)
                $height = $cart_item->options->height ?? null;
                $width = $cart_item->options->width ?? null;
                $piece_qty = $cart_item->options->piece_qty ?? null;

                SaleDetails::create([
                    'sale_id' => $sale->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty, // already in sqft (as per your comment)
                    'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->options->unit_price * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                    'height' => $height,
                    'width' => $width,
                    'piece_qty' => $piece_qty,
                ]);

                // Stock update logic (case-insensitive status & unit handling)
                if (in_array(strtolower($request->status), ['shipped', 'completed'])) {
                    $product = Product::findOrFail($cart_item->id);

                    // If product unit is SQM (case-insensitive), convert sqft -> sqm before deduct
                    if (strtoupper($product->product_unit) === 'SQM') {
                        $qty_in_sqm = $cart_item->qty / 10.7639;
                        $qty_in_sqm = round($qty_in_sqm, 4);
                        $product->decrement('product_quantity', $qty_in_sqm);
                    } else {
                        $product->decrement('product_quantity', $cart_item->qty);
                    }
                }
            }

            Cart::instance('sale')->destroy();

            if ($sale->paid_amount > 0) {
                SalePayment::create([
                    'date' => $request->date,
                    'reference' => 'INV/' . $sale->reference,
                    'amount' => $sale->paid_amount,
                    'sale_id' => $sale->id,
                    'payment_method' => $request->payment_method,
                ]);
            }
        });

        toast('Sale Created!', 'success');
        return redirect()->route('sales.index');
    }

    public function show(Sale $sale)
    {
        abort_if(Gate::denies('show_sales'), 403);

        $customer = Customer::findOrFail($sale->customer_id);

        return view('sale::show', compact('sale', 'customer'));
    }

    public function edit(Sale $sale)
    {
        abort_if(Gate::denies('edit_sales'), 403);

        $sale_details = $sale->saleDetails;

        Cart::instance('sale')->destroy();

        $cart = Cart::instance('sale');

        foreach ($sale_details as $sale_detail) {
            $product = Product::findOrFail($sale_detail->product_id);

            // Convert stock and unit if product is in SQM
            $stock = $product->product_unit === 'SQM'
                ? round($product->product_quantity * 10.7639, 2)
                : $product->product_quantity;

            $unit = $product->product_unit === 'SQM' ? 'sqft' : ($product->product_unit ?? 'pcs');

            $cart->add([
                'id'      => $sale_detail->product_id,
                'name'    => $sale_detail->product_name,
                'qty'     => $sale_detail->quantity,
                'price'   => $sale_detail->price,
                'weight'  => 1,
                'options' => [
                    'product_discount'      => $sale_detail->product_discount_amount,
                    'product_discount_type' => $sale_detail->product_discount_type,
                    'sub_total'             => $sale_detail->sub_total,
                    'code'                  => $sale_detail->product_code,
                    'stock'                 => $stock,
                    'unit'                  => $unit,
                    'product_tax'           => $sale_detail->product_tax_amount,
                    'unit_price'            => $sale_detail->unit_price,

                    // ✅ New fields for dimensional data
                    'height'     => $sale_detail->height,
                    'width'      => $sale_detail->width,
                    'piece_qty'  => $sale_detail->piece_qty,
                ],
            ]);
        }

        return view('sale::edit', compact('sale'));
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        DB::transaction(function () use ($request, $sale) {

            $due_amount = $request->total_amount - $request->paid_amount;

            if ($due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            // Restore previous stock (only if previous sale status had deducted stock)
            foreach ($sale->saleDetails as $sale_detail) {
                if (in_array(strtolower($sale->status), ['shipped', 'completed'])) {
                    $product = Product::findOrFail($sale_detail->product_id);

                    if (strtoupper($product->product_unit) === 'SQM') {
                        // sale_detail->quantity is stored in sqft, convert to sqm to restore
                        $restored_qty = $sale_detail->quantity / 10.7639;
                        $restored_qty = round($restored_qty, 4);
                    } else {
                        $restored_qty = $sale_detail->quantity;
                    }

                    $product->increment('product_quantity', $restored_qty);
                }

                // remove old sale detail row
                $sale_detail->delete();
            }

            // Update sale record
            $sale->update([
                'date'                => $request->date,
                'reference'           => $request->reference,
                'customer_id'         => $request->customer_id,
                'customer_name'       => Customer::findOrFail($request->customer_id)->customer_name,
                'tax_percentage'      => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount'     => $request->shipping_amount * 100,
                'paid_amount'         => $request->paid_amount * 100,
                'total_amount'        => $request->total_amount * 100,
                'due_amount'          => $due_amount * 100,
                'status'              => $request->status,
                'payment_status'      => $payment_status,
                'payment_method'      => $request->payment_method,
                'note'                => $request->note,
                'tax_amount'          => Cart::instance('sale')->tax() * 100,
                'discount_amount'     => Cart::instance('sale')->discount() * 100,
            ]);

            // Store new sale details and deduct stock according to new status
            foreach (Cart::instance('sale')->content() as $cart_item) {
                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $cart_item->id,
                    'product_name'            => $cart_item->name,
                    'product_code'            => $cart_item->options->code,
                    'quantity'                => $cart_item->qty,
                    'price'                   => $cart_item->price * 100,
                    'unit_price'              => $cart_item->options->unit_price * 100,
                    'sub_total'               => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type'   => $cart_item->options->product_discount_type,
                    'product_tax_amount'      => $cart_item->options->product_tax * 100,

                    // ✅ New fields (for dimension-based products)
                    'height'    => $cart_item->options->height ?? null,
                    'width'     => $cart_item->options->width ?? null,
                    'piece_qty' => $cart_item->options->piece_qty ?? null,
                ]);

                if (in_array(strtolower($request->status), ['shipped', 'completed'])) {
                    $product = Product::findOrFail($cart_item->id);

                    if (strtoupper($product->product_unit) === 'SQM') {
                        $deduct_qty = $cart_item->qty / 10.7639; // from sqft to sqm
                        $deduct_qty = round($deduct_qty, 4);
                    } else {
                        $deduct_qty = $cart_item->qty;
                    }

                    $product->decrement('product_quantity', $deduct_qty);
                }
            }

            Cart::instance('sale')->destroy();
        });

        toast('Sale Updated!', 'info');

        return redirect()->route('sales.index');
    }

    public function destroy(Sale $sale)
    {
        abort_if(Gate::denies('delete_sales'), 403);

        $sale->delete();

        toast('Sale Deleted!', 'warning');

        return redirect()->route('sales.index');
    }
}

@extends('layouts.app')

@section('title', 'Sales Details')

@section('breadcrumb')
<ol class="breadcrumb border-0 m-0">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">Details</li>
</ol>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center">
                    <div>
                        Reference: <strong>{{ $sale->reference }}</strong>
                    </div>
                    <a target="_blank" class="btn btn-sm btn-secondary mfs-auto mfe-1 d-print-none" href="{{ route('sales.pdf', $sale->id) }}">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    <a target="_blank" class="btn btn-sm btn-info mfe-1 d-print-none" href="{{ route('sales.pdf', $sale->id) }}">
                        <i class="bi bi-save"></i> Save
                    </a>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-4 mb-3 mb-md-0">
                            <h5 class="mb-2 border-bottom pb-2">Company Info:</h5>
                            <div><strong>{{ settings()->company_name }}</strong></div>
                            <div>{{ settings()->company_address }}</div>
                            <div>Email: {{ settings()->company_email }}</div>
                            <div>Phone: {{ settings()->company_phone }}</div>
                        </div>

                        <div class="col-sm-4 mb-3 mb-md-0">
                            <h5 class="mb-2 border-bottom pb-2">Customer Info:</h5>
                            <div><strong>{{ $customer->customer_name }}</strong></div>
                            <div>{{ $customer->address }}</div>
                            <div>Email: {{ $customer->customer_email }}</div>
                            <div>Phone: {{ $customer->customer_phone }}</div>
                        </div>

                        <div class="col-sm-4 mb-3 mb-md-0">
                            <h5 class="mb-2 border-bottom pb-2">Invoice Info:</h5>
                            <div>Invoice: <strong>INV/{{ $sale->reference }}</strong></div>
                            <div>Date: {{ \Carbon\Carbon::parse($sale->date)->format('d M, Y') }}</div>
                            <div>
                                Status: <strong>{{ $sale->status }}</strong>
                            </div>
                            <div>
                                Payment Status: <strong>{{ $sale->payment_status }}</strong>
                            </div>
                        </div>
                    </div>

                    @php
                    // helpers
                    $norm = fn($u) => $u ? strtolower(trim($u)) : null;

                    $isPieceUnit = function($u) {
                    if (!$u) return false;
                    $u = strtolower(trim($u));
                    return in_array($u, ['pc','pcs','piece','pieces','pc(s)'], true);
                    };
                    $isSheetUnit = function($u) {
                    if (!$u) return false;
                    $u = strtolower(trim($u));
                    return in_array($u, ['sheet', 'sheets'], true);
                    };

                    $isAreaUnit = function($u) {
                    if (!$u) return false;
                    $u = strtolower(trim($u));
                    return in_array($u, ['sqm','m2','m^2','square meter','square meters','sqft','ft2','ft^2','square feet','square foot','sq ft','sq. ft.'], true);
                    };

                    // Prepare rows and detect whether all rows are PC
                    use Modules\Product\Entities\Product;
                    $rows = [];
                    $allPc = true;
                    foreach ($sale->saleDetails as $sd) {
                    // Prefer a relation if exists, else fetch product
                    $product = $sd->relationLoaded('product') ? $sd->product : Product::find($sd->product_id);

                    // Determine product unit
                    $productUnit = null;
                    if ($product && !empty($product->product_unit)) {
                    $productUnit = $product->product_unit;
                    } elseif (!empty($sd->options['unit'])) {
                    $productUnit = $sd->options['unit'];
                    } elseif (!empty($sd->original_unit)) {
                    $productUnit = $sd->original_unit;
                    } elseif (!empty($sd->product_unit)) {
                    $productUnit = $sd->product_unit;
                    }

                    $normalized = $norm($productUnit);
                    $isPc = $isPieceUnit($normalized);
                    $isArea = $isAreaUnit($normalized) || $isAreaUnit($sd->options['unit'] ?? null);

                    if (!$isPc) $allPc = false;

                    $rows[] = [
                    'sd' => $sd,
                    'product' => $product,
                    'unit' => $productUnit,
                    'normalized' => $normalized,
                    'isPc' => $isPc,
                    'isArea' => $isArea,
                    'isSheet' => $isSheetUnit($normalized),
                    ];
                    }
                    @endphp
                    @php
                    $showDimensions = collect($rows)->contains(fn($r) => $r['isPc'] || $r['isSheet']);
                    @endphp

                    <div class="table-responsive-sm">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="align-middle">Product</th>
                                    <th class="align-middle">Net Unit Price</th>

                                    {{-- Only show these when at least one item is area-type --}}
                                    @unless($showDimensions)
                                    <th class="align-middle">Height</th>
                                    <th class="align-middle">Width</th>
                                    <th class="align-middle">Pieces</th>
                                    @endunless

                                    <th class="align-middle">Quantity</th>
                                    <th class="align-middle">Discount</th>
                                    <th class="align-middle">Tax</th>
                                    <th class="align-middle">Sub Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $r)
                                @php
                                $item = $r['sd'];
                                $product = $r['product'];
                                $isPc = $r['isPc'];
                                $unitRaw = $r['unit'] ?? ($item->options['unit'] ?? null);

                                // display label: convert SQM label to SQFT, otherwise uppercase product unit
                                $unitLabel = $unitRaw ?? '';
                                if (strtolower($unitLabel) === 'sqm') $unitLabel = 'SQFT';
                                else $unitLabel = $unitLabel ? $unitLabel : '';

                                // dims: prefer sale_detail columns then options, else 0
                                $h = $item->height ?? ($item->options['height'] ?? 0);
                                $w = $item->width ?? ($item->options['width'] ?? 0);
                                $p = $item->piece_qty ?? ($item->options['piece_qty'] ?? 0);

                                $displayH = $isPc ? 0 : ($h ?? 0);
                                $displayW = $isPc ? 0 : ($w ?? 0);
                                $displayP = $isPc ? 0 : ($p ?? 0);
                                if($unitLabel === 'SHEET') $displayQty = $item->small_item_qty;
                                else $displayQty = $item->quantity ?? '';
                                @endphp

                                <tr>
                                    <td class="align-middle">{{ $item->product_code }}</td>
                                    <td class="align-middle">{{ format_currency($item->unit_price) }}</td>

                                    @unless($showDimensions)
                                    <td class="align-middle text-center">{{ $displayH }}</td>
                                    <td class="align-middle text-center">{{ $displayW }}</td>
                                    <td class="align-middle text-center">{{ $displayP }}</td>
                                    @endunless

                                    <td class="align-middle">
                                        <div class="d-flex align-items-center" style="column-gap: 1px;">
                                            <span>{{ $displayQty }}</span>
                                            <span class="text-muted small">
                                                @if($unitLabel === 'SHEET')
                                                PC
                                                @else
                                                {{ $unitLabel }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>



                                    <td class="align-middle">{{ format_currency($item->product_discount_amount) }}</td>
                                    <td class="align-middle">{{ format_currency($item->product_tax_amount) }}</td>
                                    <td class="align-middle">{{ format_currency($item->sub_total) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 col-sm-5 ml-md-auto">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td class="left"><strong>Discount ({{ $sale->discount_percentage }}%)</strong></td>
                                        <td class="right">{{ format_currency($sale->discount_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Tax ({{ $sale->tax_percentage }}%)</strong></td>
                                        <td class="right">{{ format_currency($sale->tax_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Shipping</strong></td>
                                        <td class="right">{{ format_currency($sale->shipping_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="left"><strong>Grand Total</strong></td>
                                        <td class="right"><strong>{{ format_currency($sale->total_amount) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Details</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 20px; }
        .card { border: 1px solid #ddd; border-radius: 4px; padding: 15px; }
        .row { width: 100%; display: table; }
        .col-xs-4 { display: table-cell; width: 33%; vertical-align: top; padding: 5px; }
        .col-xs-12 { width: 100%; display: block; }
        .col-xs-offset-8 { margin-left: 66%; }
        h4 { font-size: 14px; margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        table th { background: #f5f5f5; font-weight: bold; }
        .table-striped tbody tr:nth-child(odd) { background: #f9f9f9; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
            background-color: #28a745;
            border-radius: 3px;
        }
        .right { text-align: right; }
        .left { text-align: left; }
        .footer { margin-top: 25px; text-align: center; font-style: italic; font-size: 11px; }
        img.logo { max-width: 180px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div>
    <div class="col-xs-12">
        <div class="text-center mb-4">
            <img class="logo" src="{{ public_path('images/logo-dark.png') }}" alt="Logo">
            <h4>
                <span>Reference:</span> <strong>{{ $purchase->reference }}</strong>
            </h4>
        </div>
        <div class="card">
            <div class="row mb-4">
                <div class="col-xs-4">
                    <h4 class="mb-2" style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Company Info:</h4>
                    <div><strong>{{ settings()->company_name }}</strong></div>
                    <div>{{ settings()->company_address }}</div>
                    <div>Email: {{ settings()->company_email }}</div>
                    <div>Phone: {{ settings()->company_phone }}</div>
                </div>

                <div class="col-xs-4">
                    <h4 class="mb-2" style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Supplier Info:</h4>
                    <div><strong>{{ $supplier->supplier_name }}</strong></div>
                    <div>{{ $supplier->address }}</div>
                    <div>Email: {{ $supplier->supplier_email }}</div>
                    <div>Phone: {{ $supplier->supplier_phone }}</div>
                </div>

                <div class="col-xs-4">
                    <h4 class="mb-2" style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Invoice Info:</h4>
                    <div>Invoice: <strong>INV/{{ $purchase->reference }}</strong></div>
                    <div>Date: {{ \Carbon\Carbon::parse($purchase->date)->format('d M, Y') }}</div>
                    <div>Status: <strong>{{ $purchase->status }}</strong></div>
                    <div>Payment Status: <strong>{{ $purchase->payment_status }}</strong></div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Net Unit Price</th>
                        <th>Quantity</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Sub Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchase->purchaseDetails as $item)
                        <tr>
                            <td>
                                {{ $item->product_name }} <br>
                                <span class="badge">{{ $item->product_code }}</span>
                            </td>
                            <td>{{ format_currency($item->unit_price) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ format_currency($item->product_discount_amount) }}</td>
                            <td>{{ format_currency($item->product_tax_amount) }}</td>
                            <td>{{ format_currency($item->sub_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-xs-4 col-xs-offset-8">
                    <table>
                        <tr>
                            <td class="left"><strong>Discount ({{ $purchase->discount_percentage }}%)</strong></td>
                            <td class="right">{{ format_currency($purchase->discount_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="left"><strong>Tax ({{ $purchase->tax_percentage }}%)</strong></td>
                            <td class="right">{{ format_currency($purchase->tax_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="left"><strong>Shipping</strong></td>
                            <td class="right">{{ format_currency($purchase->shipping_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="left"><strong>Grand Total</strong></td>
                            <td class="right"><strong>{{ format_currency($purchase->total_amount) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="footer">
                <p>{{ settings()->company_name }} &copy; {{ date('Y') }}.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>

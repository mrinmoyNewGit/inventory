<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale Details</title>
    <style>
        @page {
            margin: 15px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .card {
            border: 1px solid #ddd;
            padding: 15px;
        }
        h4 {
            font-size: 14px;
            margin: 0 0 8px 0;
        }
        .row {
            width: 100%;
            display: table;
            table-layout: fixed;
        }
        .col-4 {
            display: table-cell;
            width: 33%;
            vertical-align: top;
            padding: 0 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 12px;
            text-align: left;
        }
        .table th {
            background: #f9f9f9;
        }
        .summary-table {
            width: 50%;
            float: right;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .summary-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }
        .summary-table td.left {
            text-align: left;
        }
        .summary-table td.right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="text-center mb-4">
        <img width="180" src="{{ public_path('images/logo-dark.png') }}" alt="Logo"><br>
        <h4>
            <span>Reference:</span> <strong>{{ $sale->reference }}</strong>
        </h4>
    </div>

    <div class="card">
        <div class="row mb-4">
            <div class="col-4">
                <h4 style="border-bottom:1px solid #ddd;padding-bottom:6px;">Company Info:</h4>
                <div><strong>{{ settings()->company_name }}</strong></div>
                <div>{{ settings()->company_address }}</div>
                <div>Email: {{ settings()->company_email }}</div>
                <div>Phone: {{ settings()->company_phone }}</div>
            </div>
            <div class="col-4">
                <h4 style="border-bottom:1px solid #ddd;padding-bottom:6px;">Customer Info:</h4>
                <div><strong>{{ $customer->customer_name }}</strong></div>
                <div>{{ $customer->address }}</div>
                <div>Email: {{ $customer->customer_email }}</div>
                <div>Phone: {{ $customer->customer_phone }}</div>
            </div>
            <div class="col-4">
                <h4 style="border-bottom:1px solid #ddd;padding-bottom:6px;">Invoice Info:</h4>
                <div>Invoice: <strong>INV/{{ $sale->reference }}</strong></div>
                <div>Date: {{ \Carbon\Carbon::parse($sale->date)->format('d M, Y') }}</div>
                <div>Status: <strong>{{ $sale->status }}</strong></div>
                <div>Payment Status: <strong>{{ $sale->payment_status }}</strong></div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Height</th>
                    <th>Width</th>
                    <th>Piece Qty</th>
                    <th>Net Unit Price</th>
                    <th>Quantity</th>
                    <th>Discount</th>
                    <th>Tax</th>
                    <th>Sub Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleDetails as $item)
                <tr>
                    <td>{{ $item->product_code }}</td>
                    <td>{{ $item->height }}</td>
                    <td>{{ $item->width }}</td>
                    <td>{{ $item->piece_qty }}</td>
                    <td>{{ format_currency($item->unit_price) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ format_currency($item->product_discount_amount) }}</td>
                    <td>{{ format_currency($item->product_tax_amount) }}</td>
                    <td>{{ format_currency($item->sub_total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
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

        <!-- <div class="footer">
            {{ settings()->company_name }} &copy; {{ date('Y') }}.
        </div> -->
    </div>
</body>
</html>

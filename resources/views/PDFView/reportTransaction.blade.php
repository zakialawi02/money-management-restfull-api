<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Report</title>

        <style>
            body {
                font-family: 'Figtree', sans-serif, Arial;
            }

            p {
                margin: 3px;
                padding: 0;
                font-size: 15px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                text-align: left;
            }

            td {
                padding: 5px;
            }

            th,
            td {
                border-bottom: 1px solid #ddd;
                padding: 5px;
            }

            .text-right {
                text-align: right;
            }

            .text-left {
                text-align: left;
            }

            .text-center {
                text-align: center;
            }
        </style>
    </head>

    <body>
        <center style="margin-top: 20px; margin-bottom: 20px">
            <h1 style="margin: 0;">{{ $title }}</h1>
            <h2 style="margin: 5px;">Pocket Accounts: {{ $pocket->name }}</h2>
        </center>

        <div style="padding: 10px;">
            <p style="font-weight: bold; margin: 0;">
                Transaction on {{ $date }}
            </p>
            <p>Total Expense: {{ $total_amount['expense'] < 0 ? '-' : '' }}Rp {{ number_format(abs($total_amount['expense']), 0, ',', '.') }}</p>

            <div style="padding: 10px; margin-top: 20px; margin-bottom: 20px">
                <table>
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Amount</th>
                            <th class="text-right">Category</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($data as $transaction)
                            <tr>
                                <td class="text-left">{{ $transaction->date->format('d F Y') }}</td>
                                <td class="text-left" style="color: {{ $transaction->type === 'income' ? 'green' : 'red' }}">{{ $transaction->type }}</td>
                                <td class="text-left" style="color: {{ $transaction->type === 'income' ? 'green' : 'red' }}">{{ $transaction->type === 'income' ? '+' : '-' }}Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <span style="background-color:{{ $transaction->category->color }}; color: black; padding: 2px 5px; border-radius: 3px;">
                                        {{ $transaction->category->name }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach

                        <tr style="font-weight: bold;">
                            <td class="text-left" colspan="2">Total Income</td>
                            <td class="text-left" colspan="2">
                                {{ $total_amount['income'] < 0 ? '-' : '' }}Rp {{ number_format(abs($total_amount['income']), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td class="text-left" colspan="2">Total Expense</td>
                            <td class="text-left" colspan="2">
                                {{ $total_amount['expense'] < 0 ? '-' : '' }}Rp {{ number_format(abs($total_amount['expense']), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td class="text-left" colspan="2">Profit</td>
                            <td class="text-left" style="color: {{ $total_amount['total'] < 0 ? 'red' : 'green' }}" colspan="2">
                                {{ $total_amount['total'] < 0 ? '-' : '' }}Rp {{ number_format(abs($total_amount['total']), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td class="text-left" colspan="2">Final balance</td>
                            <td class="text-left" style="color: {{ $total_amount['balance_end'] < 0 ? 'red' : 'green' }}" colspan="2">
                                {{ $total_amount['balance_end'] < 0 ? '-' : '' }}Rp {{ number_format(abs($total_amount['balance_end']), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </body>

</html>

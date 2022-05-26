<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        table {
            width: 50%;
        }
        tr {
            border: 1px solid black;
        }
        td, th {
            width: 200px;
        }
    </style>
</head>
<body>
    <table class="table">
        <tr>
            <td>ID</td>
            <td>Email</td>
            <td>Balance</td>
            <td>Transactions Total</td>
            <td>Status</td>
        </tr>
        @foreach($users as $user)
        @if($user->tokenBalance != 0 || $user->tokenBalance != '')
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->tokenBalance }}</td>
            <td>
                @php
                    $totalCRNO  = 0;
                    $trnxs = \App\Models\Transaction::where('user', $user->id)->where('status', 'approved')->get();
                @endphp
                @foreach($trnxs as $trnx)
                    @php $totalCRNO = $totalCRNO + $trnx->total_tokens @endphp
                @endforeach
                {{ $totalCRNO }}
            </td>
            <td>
                @if($user->tokenBalance != $totalCRNO && $totalCRNO != 0)
                 <b>CHECK</b>
                @endif
            </td>
        </tr>
        @endif
        @endforeach
    </table>
</body>
</html>
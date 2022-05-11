@extends('layouts.user')
@section('title', __('User Transactions'))

@push('header')
<script type="text/javascript">
    var view_transaction_url = "{{ route('user.ajax.transactions.view') }}";
</script>
@endpush

@section('content')
@include('layouts.messages')
<div class="card content-area content-area-mh">
    <div class="card-innr">
        <div class="card-head">
            <h4 class="card-title">{{__('Order Detail')}}</h4>
        </div>
        <div class="gaps-1x"></div>
        {{-- Custom --}}

        <div class="row" style="padding: 30px;">
            <div>
                <p style="font-weight: bold; font-size:18px;" class="text-green">Your Order no. {{$transaction->tnx_id}} has been placed successfully.</p>
            </div>
            <br>
            <p>Please send <strong>{{$transaction->amount . ' ' . strtoupper($transaction->receive_currency) }}</strong> to the address below.<br>The token balance will appear in your account only after transaction gets 6 confirmation and approved by our team.</p>
            
        
            <strong>Payment to the following {{ strtoupper($transaction->receive_currency) }} Wallet Address</strong>
        
        </div>
        
        <div class="row">
            <div class="col-sm-2">
                <img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl={{ $transaction->payment_to }}&choe=UTF-8" />
            </div>
            <div class="col-sm-10" style="margin-top: 18px;">
                <input type="text" id="myInput" class="copy-address ignore" value="{{ $transaction->payment_to }}" disabled="" readonly="">
                <br><br>
                <button class="btn btn-success" onclick="myFunction()">Copy address</button>
                <span id="addressCopiedMessage" style="font-weight: bold" class="text-success text-bold"></span>
            </div>
        </div>

        {{-- end Custom --}}
      
    </div>{{-- .card-innr --}}
</div>{{-- .card --}}
@endsection
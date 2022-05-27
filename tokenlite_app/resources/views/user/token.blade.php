@extends('layouts.user')
@section('title', __('Purchase Token'))

@section('content')
@php
$has_sidebar = false;
$content_class = 'col-lg-8';

$current_date = time();
$upcoming = is_upcoming();

$_b = 0; 
$bc = base_currency();
$default_method = token_method();
$symbol = token_symbol();
$method = strtolower($default_method);
$min_token = ($minimum) ? $minimum : active_stage()->min_purchase;

$sold_token = (active_stage()->soldout + active_stage()->soldlock);
$have_token = (active_stage()->total_tokens - $sold_token);
$sales_ended = (($sold_token >= active_stage()->total_tokens) || ($have_token < $min_token)) ? true : false;

$is_method = is_method_valid();

$sl_01 = ($is_method) ? '01 ' : '';
$sl_02 = ($sl_01) ? '02 ' : '';
$sl_03 = ($sl_02) ? '03 ' : '';


$exc_rate = (!empty($currencies)) ? json_encode($currencies) : '{}';
$token_price = (!empty($price)) ? json_encode($price) : '{}';
$amount_bonus = (!empty($bonus_amount)) ? json_encode($bonus_amount) : '{1 : 0}';
$decimal_min = (token('decimal_min')) ? token('decimal_min') : 0;
$decimal_max = (token('decimal_max')) ? token('decimal_max') : 0;

@endphp

@include('layouts.messages')
@if ($upcoming)
<div class="alert alert-dismissible fade show alert-info" role="alert">
    <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&nbsp;</a>
    {{ __('Sales Start at') }} - {{ _date(active_stage()->start_date) }}
</div>
@endif
<div class="content-area card">
    <div class="card-innr">
        <form action="{{ route('user.payments') }}" method="post" class="token-purchase">
            @csrf
            <div class="card-head">
                <h4 class="card-title">
                {{ __('Choose currency and calculate :SYMBOL token price', ['symbol' => $symbol]) }}
                </h4>
            </div>
            <div class="card-text">
                <p>{{ __('You can buy our :SYMBOL token using the below currency choices to become part of our project.', ['symbol' => $symbol]) }}</p>
            </div>

            @if($is_method==true)
            <div class="token-currency-choose payment-list">
                <div class="row guttar-15px">
                    <div style="width:100%;padding:0 15px;">
                        <select id="payCryptoMethod" name="paymethod" class="select select-block select-bordered active_method pay-method select2-hidden-accessible">
                            {{-- @foreach($pm_currency as $gt => $full)
                            @if(token('purchase_'.$gt) == 1 || $method==$gt)
                                <option id="pay{{ $gt }}"  value="{{ $gt }}" {{ $preferredCrypto == strtoupper($gt) ? 'selected' : '' }}>{{ $full . ' (' . strtoupper($gt) . ')' }}</option>
                            @endif
                            @endforeach --}} 
                            <option value="eth" {{ $preferredCrypto == strtoupper("ETH") ? 'selected' : '' }} >Ethereum (ETH)</option>
                            <option value="btc" {{ $preferredCrypto == strtoupper("BTC") ? 'selected' : '' }} >Bitcoin (BTC)</option>
                            {{-- <option value="matic" {{ $preferredCrypto == strtoupper("MATIC") ? 'selected' : '' }} >Polygon (MATIC)</option> --}}
                            {{-- <option value="xrp" {{ $preferredCrypto == strtoupper("XRP") ? 'selected' : '' }} >Ripple (XRP)</option> --}}
                            <option value="ltc" {{ $preferredCrypto == strtoupper("LTC") ? 'selected' : '' }} >Litecoin (LTC)</option>
                            <option value="usdc" {{ $preferredCrypto == strtoupper("USDC") ? 'selected' : '' }} >USD Coin (USDC)</option>
                            {{-- <option value="bch" {{ $preferredCrypto == strtoupper("BCH") ? 'selected"' : '' }} >Bitcoin Cash (BCH)</option> --}} 
                            <option value="usdt" {{ $preferredCrypto == strtoupper("USDTERC20") ? 'selected' : '' }} >Tether - ERC20 (USDTERC20)</option>
                            <option value="usdt" {{ $preferredCrypto == strtoupper("USDTTRC20") ? 'selected' : '' }} >Tether - TRC20 (USDTTRC20)</option>
                            <option value="dash" {{ $preferredCrypto == strtoupper("DASH") ? 'selected' : '' }} >Dash (DASH)</option>
                            <option value="bnb" {{ $preferredCrypto == strtoupper("BNBBSC") ? 'selected' : '' }} >Binance Smart Chain - BEP20 (BNBBSC)</option>
                            <option value="nano" {{ $preferredCrypto == strtoupper("NANO") ? 'selected' : '' }} >Nano (NANO)</option>
                            <option value="egld" {{ $preferredCrypto == strtoupper("EGLD") ? 'selected' : '' }} >Elrond (EGLD)</option>
                            {{-- <option value="rvn" {{ $preferredCrypto == strtoupper("RVN") ? 'selected' : '' }} >Ravencoin (RVN)</option> --}}
                            <option value="doge" {{ $preferredCrypto == strtoupper("DOGE") ? 'selected' : '' }} >Dogecoin (DOGE)</option>
                            <option value="sol" {{ $preferredCrypto == strtoupper("SOL") ? 'selected' : '' }} >Solana (SOL)</option>
                            {{-- <option value="dot" {{ $preferredCrypto == strtoupper("DOT") ? 'selected' : '' }} >Polkadot (DOT)</option> --}}
                            <option value="shib" {{ $preferredCrypto == strtoupper("SHIB") ? 'selected' : '' }} >Shiba Inu (SHIB)</option>
                            <option value="sand" {{ $preferredCrypto == strtoupper("SAND") ? 'selected' : '' }} >The Sandbox (SAND)</option>
                            <option value="mana" {{ $preferredCrypto == strtoupper("MANA") ? 'selected' : '' }} >Decentraland (MANA)</option>
                        </select>
                    </div>
                </div>
            </div>
            @else 
            <div class="token-currency-default payment-item-default">
                <input class="pay-method" type="hidden" id="pay{{ base_currency() }}" name="paymethod" value="{{ base_currency() }}" checked>
            </div>
            @endif
            
            <div class="card-head">
                <h4 class="card-title">{{ __('Amount of contribute') }}</h4>
            </div>
            <div class="card-text">
                <p>{{ __('Enter the amount you would like to contribute in order to calculate the amount of tokens you will receive. The calculator below helps to convert the required quantity of tokens into the amount of your selected currency.') }}</p>
            </div>
            @php
            $calc = token('calculate');
            $input_hidden_token = ($calc=='token') ? '<input class="pay-amount" type="hidden" id="pay-amount" value="">' : '';
            $input_hidden_amount = ($calc=='pay') ? '<input class="token-number" type="hidden" id="token-number" value="">' : ''; 
            
            $input_token_purchase = '<div class="token-pay-amount payment-get">'.$input_hidden_token.'<input class="input-bordered input-with-hint token-number" required type="text" id="token-number" value="" min="'.$min_token.'" max="'.$stage->max_purchase.'" ><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-get-cur payment-cal-cur ucap">'.$symbol.'</span></div></div>';
            $input_token_purchase = '<div class="token-pay-amount payment-get">'.$input_hidden_token.'<input class="input-bordered input-with-hint token-number" required type="text" id="token-number" value="" min="'.$min_token.'" max="'.$stage->max_purchase.'" ><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-get-cur payment-cal-cur ucap">'.$symbol.'</span></div></div>';
            $input_pay_amount = '<div class="token-pay-amount payment-from">'.$input_hidden_amount.'<input class="input-bordered input-with-hint pay-amount" type="text" id="pay-amount" value=""><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-from-cur payment-cal-cur pay-currency ucap">'.$method.'</span></div></div>';
            $input_token_purchase_num = '<div class="token-received"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 class="token-amount token-number-u" >0.00</h5><div class="token-symbol">'.$symbol.'</div></div></div>';
            $input_pay_amount_num = '<div class="token-received token-received-alt"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 id="totalCrypto" class="token-amount pay-amount-u" >0.00</h5><div id="crpSymbol" class="token-symbol ucap">'.$method.'</div></div></div>';
            $show_in_usd = '<div class="token-received token-received-alt"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 id="totalUSD" class="token-amount " >0.00</h5><div class="token-symbol ucap">USD</div></div></div>';
            $input_sep = '<div class="token-eq-sign"><em class="fas fa-exchange-alt"></em></div>';
            @endphp
            <input type="hidden" name="token_symbol" value="{{ $symbol }}">
            <input type="hidden" name="token_price" id="total_tokens_price" value="">
            <input type="hidden" name="tokensGet" id="tokensGet" value="">
            <input type="hidden" name="total_tokens" id="total_tokens_num" value="">
            <input type="hidden" name="totalBonus" id="totalBonus" value="">
            <input type="hidden" name="bonusOnBase" id="bonusOnBase" value="">
            <input type="hidden" name="bonusOnToken" id="bonusOnToken" value="">
            <input type="hidden" name="basePrice" id="tBasePrice" value="">
            <input type="hidden" name="currencyRate" id="currencyRate" value="">
            <input type="hidden" name="baseCurrency" id="baseCurrency" value="{{ base_currency() }}">
            <input type="hidden" name="usdtSeparator" id="usdtSeparator" value="">
            <div class="token-contribute">
                
            
                <div class="token-calc">
                    <div class="token-pay-amount payment-get"><input class="input-bordered input-with-hint" required name="usdAm" type="number" id="amountInUSD" value="{{ $preferredAmount }}" min="10" max="150000" autocomplete="off" ><div class="token-pay-currency"><span class="input-hint input-hint-sap payment-get-cur payment-cal-cur ucap">{{ $bc }}</span></div></div>
                    <div class="token-received token-received-alt"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 id="showCrypto" class="token-amount " >0.00</h5><div id="getCrypto" class="token-symbol ucap">--</div></div></div>
                    <div class="token-received token-received-alt"><div class="token-eq-sign">=</div><div class="token-received-amount"><h5 id="showCRNO" class="token-amount " >0.00</h5><div class="token-symbol ucap">CRNO</div></div></div>
                </div>
            
                <div class="token-calc-note note note-plane token-note">
                    <div class="note-box">
                        <span class="note-icon">
                            <em id="noteIconShow" class="fas fa-info-circle"></em>
                        </span>
                        
                        {{-- <span class="note-text text-light"><strong class="min-amount">{{ to_num(token_calc($min_token, 'price')->$method, 'max') }}</strong> <span class="pay-currency ucap">{{ $method }}</span> (<strong class="min-token">{{ to_num_token($min_token, 0) }}</strong> 
                        <span class="token-symbol ucap">{{ $symbol }}</span>) {{__('Minimum contribution amount is required.')}}</span>--}}
                        
                        <span class="note-text text-light"><strong id="minCryptoAmountIndicator" class="min-amount">{{ to_num(token_calc($min_token, 'price')->$method, 'max') }}</strong> <span id="minCryptoIndicator" class="pay-currency ucap">--</span> (<strong class="min-token">{{ to_num_token($min_token, 0) }}</strong> 
                        <span class="token-symbol ucap">{{ $symbol }}</span>) {{__('Minimum contribution amount is required.')}}</span>
                    </div>
                    <div class="note-text note-text-alert"></div>
                </div>
            </div>

            @if(!empty($bonus_amount) && !$sales_ended)
            <div class="token-bonus-ui">
                <div class="bonus-bar{{ ($active_bonus) ? ' with-base-bonus' : '' }}">
                    @if(!empty($active_bonus))
                    <div class="bonus-base">
                        <span class="bonus-base-title">{{__('Bonus') }}</span>
                        <span class="bonus-base-amount">{{__('On Sale')}}</span>
                        <span class="bonus-base-percent">{{ $active_bonus->amount }}%</span>
                    </div>
                    @endif
                    @php
                    $b_amt_bar = '';
                    if(!empty($bonus_amount)){
                        foreach($bonus_amount as $token => $bt_amt){
                            $_b = (50 / count($bonus_amount) );
                            $b_amt_bar .= ($bt_amt > 0 && $token > 0) ? '<div class="bonus-extra-item bonus-tire-'. $bt_amt .'" data-percent="'. round($_b, 0).'"><span class="bonus-extra-amount">'. $token .' '. $symbol .'</span><span class="bonus-extra-percent">'.$bt_amt.'%</span></div>' : '';
                        }
                    }
                    $b_amt_bar = (!empty($b_amt_bar)) ? '<div class="bonus-extra">'.$b_amt_bar.'</div>' : '';
                    @endphp
                    {!! $b_amt_bar !!}
                </div>
            </div>
            @endif
            @if(!$sales_ended)
            <div class="token-overview-wrap">
                <div class="token-overview">
                    <div class="row">
                        <div class="col-md-4 col-sm-6">
                            <div class="token-bonus token-bonus-sale">
                               {{-- <span class="token-overview-title">+ {{ __('Sale Bonus') . ' ' . (empty($active_bonus) ? 0 :  $active_bonus->amount) }}%</span> --}}
                               {{-- <span id="totalBonusGet" class="token-overview-value bonus-on-sale tokens-bonuses-sale">0</span> --}}
                               <span class="token-overview-title">{{ __('Amount purchased')}}</span> 
                               <span id="totalAmountPurchased" class="token-overview-value bonus-on-sale tokens-bonuses-sale">0</span>
                            </div>
                        </div>
                        {{-- @if(!empty($bonus_amount && !empty($b_amt_bar)) ) --}}
                        <div class="col-md-4 col-sm-6">
                            <div class="token-bonus token-bonus-amount">
                                {{-- <span class="token-overview-title">+ {{__('Amount Bonus')}}</span> --}}
                                {{-- <span id="bonusOnAmountGet" class="token-overview-value bonus-on-amount tokens-bonuses-amount">0</span> --}}
                               <span class="token-overview-title">+ {{(empty($active_bonus) ? 0 :  $active_bonus->amount) }}% {{  ' ' . __('Sale Bonus')   }}</span>
                               <span id="totalBonusGet" class="token-overview-value bonus-on-sale tokens-bonuses-sale">0</span> 
                            </div>
                        </div>
                        {{-- @endif --}}
                        <div class="col-md-4">
                            <div class="token-total">
                                <span class="token-overview-title font-bold">{{__('Total') . ' '.$symbol }}</span>
                                <span id="total-tokens-amount" class="token-overview-value token-total-amount text-primary payment-summary-amount tokens-total">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="pdb-0-5x">
                <div class="input-wrap">
                    <input type="radio" class="pay-check" value="nowpayments" name="pay_option" required="required" id="pay-nowpayments" data-msg-required="Select your payment method." >
                    <label class="pay-check-label" for="pay-nowpayments"><span class="pay-check-text" title="You can pay with any of our supported crypto">Pay with crypto</span></label>
                </div>
                <p style="margin-bottom:10px;" class="text-light font-italic mgb-1-5x"><small>*A processing fee may be charged by the payment gateway.</small></p>
                </div>
                <div class="pdb-0-5x">
                    <div class="input-item text-left">
                    <input type="checkbox" data-msg-required="You should accept our terms and policy." class="input-checkbox input-checkbox-md" id="agree-terms" name="agree" required="">
                    <label for="agree-terms">I hereby agree to the token purchase agreement and token sale term.</label>
                    </div>
                    </div>
                {{-- <div class="note note-plane note-danger note-sm pdt-1x pl-0">
                    <p>{{__('Your contribution will be calculated based on exchange rate at the moment when your transaction is confirmed.')}}</p>
                </div> --}}
            </div>
            @endif


            @if(is_payment_method_exist() && !$upcoming && ($stage->status != 'paused') && !$sales_ended)
            <div class="pay-buttons">
                <div class="pay-buttons pt-0">
                    {{-- <a data-type="offline" href="#payment-modal" class="btn btn-primary btn-between payment-btn disabled token-payment-btn offline_payment">{{__('Make Payment')}}&nbsp;<i class="ti ti-wallet"></i></a> --}}
                    <button id="submitBTNN" ondblclick="return false" type="submit" class="btn btn-primary token-payment btn-between  offline_payment">Make Payment&nbsp;<i class="ti ti-wallet"></i></button>
                </div>
                <div class="pay-notes">
                    <div class="note note-plane note-light note-md font-italic">
                        <em class="fas fa-info-circle"></em>
                        <p>{{__('Tokens will appear in your account after payment successfully made and approved by our team. Please note that, :SYMBOL token will be distributed after the token sales end-date.', ['symbol' => $symbol]) }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info alert-center">
                {{ ($sales_ended) ? __('Our token sales has been finished. Thank you very much for your contribution.') : __('Our sale will start soon. Please check back at a later date/time or feel free to contact us.') }}
            </div>
            @endif
            <input type="hidden" id="data_amount" value="0">
            <input type="hidden" id="data_currency" value="{{ $default_method }}">
        </form>
    </div> {{-- .card-innr --}}
</div> {{-- .content-area --}}
@push('sidebar')
<div class="aside sidebar-right col-lg-4">
    @if(!has_wallet() && gws('token_wallet_req')==1 && !empty(token_wallet()))
    <div class="d-none d-lg-block">
        {!! UserPanel::add_wallet_alert() !!}
    </div>
    @endif
    {!! UserPanel::user_balance_card($contribution, ['vers' => 'side']) !!}
    <div class="token-sales card">
        <div class="card-innr">
            <div class="card-head">
                <h5 class="card-title card-title-sm">{{__('Token Sales')}}</h5>
            </div>
            <div class="token-rate-wrap row">
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{ $symbol }} {{__('Token Price')}}</span>
                    <h4 class="font-mid text-dark">1 {{ $symbol }} = <span>{{ to_num($token_prices->$bc, 'max', ',') .' '. base_currency(true) }}</span></h4>
                </div>
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{__('Exchange Rate')}}</span>
                    @php
                    $exrpm = collect($pm_currency);
                    $exrpm = $exrpm->forget(base_currency())->take(2);
                    $exc_rate = '<span>1 '.base_currency(true) .' ';
                    foreach ($exrpm as $cur => $name) {
                        if($cur != base_currency() && get_exc_rate($cur) != '') {
                            $exc_rate .= ' = '.to_num(get_exc_rate($cur), 'max', ',') . ' ' . strtoupper($cur);
                        }
                    }
                    $exc_rate .= '</span>';
                    @endphp
                    {!! $exc_rate !!}
                </div>
            </div>
            @if(!empty($active_bonus))
            <div class="token-bonus-current">
                <div class="fake-class">
                    <span class="card-sub-title">{{__('Current Bonus')}}</span>
                    <div class="h3 mb-0">{{ $active_bonus->amount }} %</div>
                </div>
                <div class="token-bonus-date">{{__('End at')}}<br>{{ _date($active_bonus->end_date, get_setting('site_date_format')) }}</div>
            </div>
            @endif
        </div>
    </div>
    @if(gws('user_sales_progress', 1)==1)
    {!! UserPanel::token_sales_progress('',  ['class' => 'mb-0']) !!}
    @endif
</div>{{-- .col.aside --}}
@endpush
@endsection
@section('modals')
<div class="modal fade modal-payment" id="payment-modal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-md modal-dialog-centered">
        <div class="modal-content"></div>
    </div>
</div>
@endsection

@push('header')
<script>
    var minimum_token = {{ $min_token }}, maximum_token ={{ $stage->max_purchase }}, token_price = {!! $token_price !!}, token_symbol = "{{ $symbol }}",
    base_bonus = {!! $bonus !!}, amount_bonus = {!! $amount_bonus !!}, decimals = {"min":{{ $decimal_min }}, "max":{{ $decimal_max }} }, base_currency = "{{ base_currency() }}", base_method = "{{ $method }}";
    var max_token_msg = "{{ __('Maximum you can purchase :maximum_token token per contribution.', ['maximum_token' => to_num($stage->max_purchase, 'max', ',')]) }}", min_token_msg = "{{ __('Enter minimum :minimum_token token and select currency!', ['minimum_token' => to_num($min_token, 'max', ',')]) }}";
    
</script>
<script>
    var dynamicTokenPrice = {{ to_num($token_prices->$bc, 'max', ',') }}
    
    document.addEventListener("DOMContentLoaded", function(event) { 
       
        function changeBase(){
            base_currency = document.getElementById('payCryptoMethod'). selectedOptions[0].value;
            $("#token-number").val("");
            $("#getCrypto").html(base_currency);
            $('#minCryptoIndicator').html(base_currency)
            updatePrc();
        }
        $("#payCryptoMethod").on("change", changeBase);
        
        $( document ).ready(function() {
            updatePrc();
        });

        var typingTimer;
        var doneTypingInterval = 500;
        
        $('#amountInUSD').on("keyup input", function(){
            clearTimeout(typingTimer);
            typingTimer = setTimeout(updatePrc, doneTypingInterval);
        });
        
        
        function updatePrc() {
            var usd = $("#amountInUSD").val();
            var crno = $("#showCRNO");
            var totalAmountPurchased = $("#totalAmountPurchased");

            var tokPrice = 1/dynamicTokenPrice;

            crno.html(Math.round(usd*tokPrice));
            totalAmountPurchased.html(Math.round(usd*tokPrice));
            
            var cryp = $("#getCrypto").html();
           

            if(usd == "" || usd == 0)
            {
                crno.html("0.00");
                totalAmountPurchased.html("0");  
                $('#totalBonusGet').html("0");
                $('#total-tokens-amount').html("0")
                $('#showCrypto').html("0.00");
                $("#noteIconShow").removeClass("fa-check-circle")
                $("#noteIconShow").addClass("fa-info-circle");
            }
            
            
            $.ajaxSetup({
                headers : {
                    'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
                }     
            });
            
            $.ajax({
                url: "/get-price-crypto" + "/" + usd + "/" + cryp,
                type: "GET",
                dataType: 'JSON',
                success: function(result) {
                    $("#showCrypto").html(result.cryptoPrice)
                    $("#total_tokens_price").val(result.amount)
                    $("#total_tokens_num").val(result.total_tokens)
                    $("#totalBonusGet").html(result.total_bonus)
                    $("#totalBonus").val(result.total_bonus)
                    $('#bonusOnAmountGet').html(result.bonus_on_token)
                    $('#total-tokens-amount').html(result.total_tokens)
                    $('#token-number').val(result.token)
                    //$('#totalBonusGet').val(result.total_bonus)
                    $('#totalBonus').val(result.total_bonus)
                    $('#tBasePrice').val(result.base_price)
                    $('#bonusOnBase').val(result.bonus_on_base)
                    $('#bonusOnToken').val(result.bonus_on_token)
                    $('#tokensGet').val(result.token) 
                    $('#currencyRate').val(result.currency_rate)
                    $('#minCryptoAmountIndicator').html(Number((result.min_token * result.currency_rate).toFixed(6)))
                                
                    if( result.token < result.min_token) {
                        $("#noteIconShow").removeClass("fa-check-circle")
                        $("#noteIconShow").addClass("fa-info-circle");
                    }
                    
                    if(result.token >= result.min_token) {
                        $("#noteIconShow").removeClass("fa-info-circle")
                        $("#noteIconShow").addClass("fa-check-circle text-green");
                    }
                    
                    // $('#submitBTNN').removeAttr('disabled');
                    // $('#submitBTNN').prop("disabled",false);
                    
                    selectedCrypt = document.getElementById('payCryptoMethod'). selectedOptions[0].text;
                    strPos = selectedCrypt.indexOf("(") + 1;
                    selectedCrypt = selectedCrypt.slice(strPos, selectedCrypt.lastIndexOf(")"));
                    
                    if(selectedCrypt == "USDTERC20" || selectedCrypt == "USDTTRC20")
                    {
                        $("#usdtSeparator").val(selectedCrypt);
                    }
                    else
                    {
                        $("#usdtSeparator").val("");
                    }
                    
                    console.log(result)
                    
                }
            });
            
            
        }
        
        var currentCrypto = document.getElementById('payCryptoMethod'). selectedOptions[0].value;
        $('#getCrypto').html(currentCrypto);
        $('#minCryptoIndicator').html(currentCrypto)
        
        console.log(max_token_msg);
        console.log(maximum_token);
        
    });
    
</script>
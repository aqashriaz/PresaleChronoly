<?php

namespace App\Http\Controllers\User;

/**
 * Token Controller
 *
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.0.5
 */
use DB;
use Auth;
use Validator;
use IcoHandler;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\PayModule\Module;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Notifications\TnxStatus;
use App\Http\Controllers\Controller;
use App\Helpers\TokenCalculate as TC;
use App\PreferredTokenBuy;


class TokenController extends Controller
{
    /**
     * Property for store the module instance
     */
    private $module;
    protected $handler;
    /**
     * Create a class instance
     *
     * @return \Illuminate\Http\Middleware\StageCheck
     */
    public function __construct(IcoHandler $handler)
    {
        $this->middleware('stage');
        $this->module = new Module();
        $this->handler = $handler;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function index()
    {
        
        if (token('before_kyc') == '1') {
            $check = User::find(Auth::id());
            if ($check && !isset($check->kyc_info->status)) {
                return redirect(route('user.kyc'))->with(['warning' => __('messages.kyc.mandatory')]);
            } else {
                if ($check->kyc_info->status != 'approved') {
                    return redirect(route('user.kyc.application'))->with(['warning' => __('messages.kyc.mandatory')]);
                }
            }
        }

        $stage = active_stage();
        $tc = new TC();
        $currencies = Setting::active_currency();
        $currencies['base'] = base_currency();
        $bonus = $tc->get_current_bonus(null);
        $bonus_amount = $tc->get_current_bonus('amount');
        $price = Setting::exchange_rate($tc->get_current_price());
        $minimum = $tc->get_current_price('min');
        $active_bonus = $tc->get_current_bonus('active');
        $pm_currency = PaymentMethod::Currency;
        $pm_active = PaymentMethod::where('status', 'active')->get();
        $token_prices = $tc->calc_token(1, 'price');
        $is_price_show = token('price_show');
        $contribution = Transaction::user_contribution();
        
        $currentUser = auth()->user();
        $preferredCryptoData = PreferredTokenBuy::where('user_id', $currentUser->id)->first();
        $preferredCrypto = $preferredCryptoData->currency;
        $preferredCrypto = explode(')', (explode('(', $preferredCrypto)[1]))[0];
        $preferredAmount = $preferredCryptoData->amount;

        if ($price <= 0 || $stage == null || count($pm_active) <= 0 || token_symbol() == '') {
            return redirect()->route('user.home')->with(['info' => __('messages.ico_not_setup')]);
        }

        return view(
            'user.token',
            compact('stage', 'currencies', 'bonus', 'bonus_amount', 'price', 'token_prices', 'is_price_show', 'minimum', 'active_bonus', 'pm_currency', 'contribution', 'preferredCrypto', 'preferredAmount')
        );
    }
    
    
    /**
     * Get price in crypto 
     */
     public function getPriceCrypto($amount, $crypto)
     {
         $tc = new TC();
         $bprice = base_currency();
        
         //$missingCurrencies = ["matic","xrp","btc","nano","egld","rvn","dot","shib","sand","mana"];
         //$missingCurrencies = ["eth","btc","matic","xrp","ltc","usdc","bch","dash","nano","egld","rvn","doge","sol","dot","shib","sand","mana"];
         $missingCurrencies = ["eth","btc","ltc","usdc","dash","nano","egld","doge","sol","shib","sand","mana"];
         
         if (in_array($crypto, $missingCurrencies))
         {
            $bnbURL = 'https://api.binance.com/api/v3/ticker/price?symbol=' . strtoupper($crypto) . 'USDT';
            
            $dataForCall = file_get_contents($bnbURL);
            $json = json_decode($dataForCall); 
            $exrate = 1/ $json->price;
            
            $currency_ratex = $exrate / 100;
            $totalCrypto = number_format($exrate * $amount, 6);
          
            
         }
         else
         {
            if($crypto == "usdterc20" || $crypto == "usdtrc20")
            {
                $crypto = "USDT";
            }
            
            $totalCrypto = to_num(token_price($amount, $crypto) * 100);    
            $currency_ratex = Setting::exchange_rate($tc->get_current_price(), $crypto);
         }
         
         
         $min_token = active_stage()->min_purchase;
        
         $token = (float) $amount * 100;    
             
         return response()->json([
            'cryptoPrice' =>$totalCrypto,
            'currency_rate' => $currency_ratex,
            'bonus_on_base' => $tc->calc_token($token, 'bonus-base'),
            'bonus_on_token' => $tc->calc_token($token, 'bonus-token'),
            'total_bonus' => $tc->calc_token($token, 'bonus'),
            'total_tokens' => $tc->calc_token($token),
            'base_price' => $tc->calc_token($token, 'price')->base,
            //'amount' => $tc->calc_token($token, 'price')->$crypto, max_decimal(),
            'amount' => $totalCrypto,
            'token' => round($token, min_decimal()),
            'min_token' => $min_token
        ]);
     }


    /**
     * Access the confirm and count
     *
     * @version 1.1
     * @since 1.0
     * @return void
     * @throws \Throwable
     */
    public function access(Request $request)
    {
        $tc = new TC();
        $get = $request->input('req_type');
        $min = $tc->get_current_price('min');
        $currency = $request->input('currency');
        $token = (float) $request->input('token_amount');
        $ret['modal'] = '<a href="#" class="modal-close" data-dismiss="modal"><em class="ti ti-close"></em></a><div class="tranx-popup"><h3>' . __('messages.trnx.wrong') . '</h3></div>';
        $_data = [];
        
        try {
            $last = (int)get_setting('piks_ger_oin_oci', 0);
            if( ( !empty(env_file()) && str_contains(app_key(), $this->handler->find_the_path($this->handler->getDomain())) && $this->handler->cris_cros($this->handler->getDomain(), app_key(2)) ) && $last <= 3 ){
                if (!empty($token) && $token >= $min) {
                    $_data = (object) [
                        'currency' => $currency,
                        'currency_rate' => Setting::exchange_rate($tc->get_current_price(), $currency),
                        'token' => round($token, min_decimal()),
                        'bonus_on_base' => $tc->calc_token($token, 'bonus-base'),
                        'bonus_on_token' => $tc->calc_token($token, 'bonus-token'),
                        'total_bonus' => $tc->calc_token($token, 'bonus'),
                        'total_tokens' => $tc->calc_token($token),
                        'base_price' => $tc->calc_token($token, 'price')->base,
                        'amount' => round($tc->calc_token($token, 'price')->$currency, max_decimal()),
                    ];
                }
                if ($this->check($token)) {
                    if ($token < $min || $token == null) {
                        $ret['opt'] = 'true';
                        $ret['modal'] = view('modals.payment-amount', compact('currency', 'get'))->render();
                    } else {
                        $ret['opt'] = 'static';
                        $ret['ex'] = [$currency, $_data];
                        $ret['modal'] = $this->module->show_module($currency, $_data);
                    }
                } else {
                    $msg = $this->check(0, 'err');
                    $ret['modal'] = '<a href="#" class="modal-close" data-dismiss="modal"><em class="ti ti-close"></em></a><div class="popup-body"><h3 class="alert alert-danger text-center">'.$msg.'</h3></div>';
                }
            }else{
                $ret['modal'] = '<a href="#" class="modal-close" data-dismiss="modal"><em class="ti ti-close"></em></a><div class="popup-body"><h3 class="alert alert-danger text-center">'.$this->handler->accessMessage().'</h3></div>';
            }
        } catch (\Exception $e) {
            $ret['modal'] = '<a href="#" class="modal-close" data-dismiss="modal"><em class="ti ti-close"></em></a><div class="popup-body"><h3 class="alert alert-danger text-center">'.$this->handler->accessMessage().'</h3></div>';
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    public function generateOrder(Request $request)
    {
        // dd($request->request);
        $validator = Validator::make($request->all(), [
            'paymethod' => 'required',
            'token_symbol' => 'required',
            'token_price' => 'required',
            'tokensGet' => 'required',
            'total_tokens' => 'required',
            'totalBonus' => 'required',
            'bonusOnBase' => 'required',
            'bonusOnToken' => 'required',
            'basePrice' => 'required',
            'pay_option' => 'required',
            'currencyRate' => 'required',
            'baseCurrency' => 'required',
            'agree' => 'required',
        ], 
        [
            'agree.required' => __('messages.agree'),
            'paymethod.required' => __('messages.trnx.require_currency'),
            'token_symbol.required' => __('messages.trnx.require_token'),
            'token_number.required' => __('messages.trnx.require_token'),
            'total_tokens.required' => __('messages.trnx.require_token'),
            'totalBonus.required' => __('messages.trnx.require_token'),
            'token_price.required' => __('messages.trnx.require_price'),
            'pay_option.required' => __('messages.trnx.select_method'),
        ]);

        
        if ($validator->fails()) {
            //change
            return $validator->errors();
        }
        
        $crypto = $request->paymethod;  
        $token_symbol = $request->token_symbol;
        $token_price = $request->token_price;
        $actual_tokens = $request->tokensGet;
        $total_tokens = $request->total_tokens;
        $total_bonus = $request->totalBonus;
        $bonusOnBase = $request->bonusOnBase;
        $bonusOnToken = $request->bonusOnToken;
        $currencyRate = $request->currencyRate;
        $baseCurrency = $request->baseCurrency;
        $basePrice = $request->basePrice;
        $usdtSeparator = $request->usdtSeparator;
        
        if($crypto == "bnb")
        {
            $crypto = "bnbbsc";
        }
        
        if($usdtSeparator == "USDTERC20")
        {
            $crypto = "USDTERC20";
        }
        
        if($usdtSeparator == "USDTTRC20")
        {
            $crypto = "USDTTRC20";
        }

        $url = "https://presale.chronoly.io/api/nowpayments/confirm/payment";

        // Nowpayments API Call
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.nowpayments.io/v1/payment',
        //CURLOPT_URL => 'https://api-sandbox.nowpayments.io/v1/payment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "price_amount": "' . $token_price . '",
            "price_currency": "'. $crypto .'",
            "pay_amount": "' . $token_price . '",
            "ipn_callback_url": "' . $url . '",
            "pay_currency": "'. $crypto .'",
            "order_description": "Request to purchase ' . $token_symbol . ' tokens."
        }',
        CURLOPT_HTTPHEADER => array(
            'x-api-key: Z5RCWSK-4JW41WS-QPM01WG-SFRC026',
            //Sandbox'x-api-key: Q9ARSYT-EPJ4QCW-HV2R91Y-14RWDGS',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        $ico = IcoStage::where('status', '!=', 'deleted')->where('id', get_setting('actived_stage'))->first();
        if (!$ico) {
            $ico = IcoStage::where('status', '!=', 'deleted')->orderBy('id', 'DESC')->first();
        }

        $tc = new TC();
        $user = auth()->user();
        $all_currency_rate = json_encode(Setting::exchange_rate($tc->get_current_price(), 'except'));
        $currency_rate = Setting::exchange_rate($tc->get_current_price(), $crypto);
        $base_currency_rate = Setting::exchange_rate($tc->get_current_price(), $baseCurrency);        
        
        if (isset($response['payment_id'])) {
            $values = [
                'tnx_id' => $response['payment_id'],
                'tnx_type' => 'purchase',
                'tnx_time' => Carbon::now('Europe/London')->format('Y-m-d H:m:s'),
                'tokens' => $actual_tokens,
                'bonus_on_base' => $bonusOnBase,
                'bonus_on_token' => $bonusOnToken,
                'total_bonus' => $total_bonus,
                'total_tokens' => $total_tokens,
                'stage' => $ico->id,
                'user' => $user->id,
                'amount' => $response['pay_amount'],
                'receive_amount' => $response['pay_amount'],
                'receive_currency' => $response['price_currency'],
                'currency' => $response['price_currency'],
                // 'wallet_address' => $response['pay_address'],
                'payment_method' => 'manual',
                'payment_id' => $response['pay_address'],
                'payment_to' => $response['pay_address'],
                'order_id' => $response['payment_id'],
                'purchase_id' => $response['purchase_id'],
                'details' => $response['order_description'],
                'status' => 'pending',
                'dist' => 0,
                'base_currency' => $baseCurrency,
                'base_amount' => $basePrice,
                'all_currency_rate' => $all_currency_rate,
                'currency_rate' => $currency_rate,
                'base_currency_rate' => $base_currency_rate,
                // 'payin_extra_id' => $response['payin_extra_id'],
                // 'ipn_callback_url' => $response['ipn_callback_url'],
            ];
            

            // return $values;

            //dd($values);            
            
            // try { 
            //   $results = Transaction::create($values); 
            //     // Closures include ->first(), ->get(), ->pluck(), etc.
            // } catch(\Illuminate\Database\QueryException $ex){ 
            //   dd($ex->getMessage()); 
            //   // Note any method of class PDOException can be called on $ex.
            // }
            
            $transaction = Transaction::create($values);
            // $transaction = DB::table('transactions')->insert(
            //     [
            //         'tnx_id' => $response['payment_id'],
            //         'tnx_type' => 'purchase',
            //         'tnx_time' => Carbon::now('Europe/London')->format('Y-m-d H:m:s'),
            //         'tokens' => $actual_tokens,
            //         'bonus_on_base' => $total_bonus,
            //         'bonus_on_token' => 0,
            //         'total_bonus' => $total_bonus,
            //         'total_tokens' => $total_tokens,
            //         'stage' => 1,
            //         'user' => $user->id,
            //         'amount' => $response['pay_amount'],
            //         'receive_amount' => 0,
            //         'receive_currency' => $response['price_currency'],
            //         'currency' => $response['price_currency'],
            //         //'wallet_address' => $response['pay_address'], //to be filled after payment creation by user
            //         'payment_method' => 'manual',
            //         'payment_id' => $response['pay_address'],
            //         'payment_to' => $response['pay_address'],
            //         'order_id' => $response['payment_id'],
            //         'purchase_id' => $response['purchase_id'],
            //         'details' => $response['order_description'],
            //         'status' => 'pending',
            //         'dist' => 0,
            //     ]
            // );

            
            if ($transaction) {
                return redirect()->route('user.order', $response['payment_id']);
            } else {
                return 'Something went wrong, try again or contact administrator';
            }

        }
        else 
        {
            return $response;
        }
    }
    

    // Order Detials
    public function orderDetails($tnx_id)
    {
        $transaction = Transaction::where('tnx_id', $tnx_id)->first();
        return view('user.order')->with([
            'transaction' => $transaction,
        ]);
    }

    /**
     * Make Payment
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function payment(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        $validator = Validator::make($request->all(), [
            'agree' => 'required',
            'pp_token' => 'required',
            'pp_currency' => 'required',
            'pay_option' => 'required',
        ], [
            'pp_currency.required' => __('messages.trnx.require_currency'),
            'pp_token.required' => __('messages.trnx.require_token'),
            'pay_option.required' => __('messages.trnx.select_method'),
            'agree.required' => __('messages.agree')
        ]);
        if ($validator->fails()) {
            if ($validator->errors()->hasAny(['agree', 'pp_currency', 'pp_token', 'pay_option'])) {
                $msg = $validator->errors()->first();
            } else {
                $msg = __('messages.form.wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
        }else{
            $type = strtolower($request->input('pp_currency'));
            $method = strtolower($request->input('pay_option'));
            $last = (int)get_setting('piks_ger_oin_oci', 0);
            if( $this->handler->check_body() && $last <= 3 ){
                return $this->module->make_payment($method, $request);
            }else{
                $ret['msg'] = 'info';
                $ret['message'] = $this->handler->accessMessage();
            }

        }
        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Check the state
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    private function check($token, $extra = '')
    {
        $tc = new TC();
        $stg = active_stage();
        $min = $tc->get_current_price('min');
        $available_token = ( (double) $stg->total_tokens - ($stg->soldout + $stg->soldlock) );
        $symbol = token_symbol();

        if ($extra == 'err') {
            if ($token >= $min && $token <= $stg->max_purchase) {
                if ($token >= $min && $token > $stg->max_purchase) {
                    return __('Maximum amount reached, You can purchase maximum :amount :symbol per transaction.', ['amount' => $stg->max_purchase, 'symbol' =>$symbol]);
                } else {
                    return __('You must purchase minimum :amount :symbol.', ['amount' => $min, 'symbol' =>$symbol]);
                }
            } else {
                if($available_token < $min) {
                    return __('Our sales has been finished. Thank you very much for your interest.');
                } else {
                    if ($available_token >= $token) {
                        return __(':amount :symbol Token is not available.', ['amount' => $token, 'symbol' =>$symbol]);
                    } else {
                        return __('Available :amount :symbol only, You can purchase less than :amount :symbol Token.', ['amount' => $available_token, 'symbol' =>$symbol]);
                    }
                }
            }
        } else {
            if ($token >= $min && $token <= $stg->max_purchase) {
                if ($available_token >= $token) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }


    /**
     * Payment Cancel
     *
     * @version 1.0.0
     * @since 1.0.5
     * @return void
     */
    public function payment_cancel(Request $request, $url='', $name='Order has been canceled due to payment!')
    {
        if ($request->get('tnx_id') || $request->get('token')) {
            $id = $request->get('tnx_id');
            $pay_token = $request->get('token');
            if($pay_token != null){
                $pay_token = (starts_with($pay_token, 'EC-') ? str_replace('EC-', '', $pay_token) : $pay_token);
            }
            $apv_name = ucfirst($url);
            if(!empty($id)){
                $tnx = Transaction::where('id', $id)->first();
            }elseif(!empty($pay_token)){
                $tnx = Transaction::where('payment_id', $pay_token)->first();
                if(empty($tnx)){
                    $tnx =Transaction::where('extra', 'like', '%'.$pay_token.'%')->first();
                }
            }else{
                return redirect(route('user.token'))->with(['danger'=>__("Sorry, we're unable to proceed the transaction. This transaction may deleted. Please contact with administrator."), 'modal'=>'danger']);
            }
            if($tnx){
                $_old_status = $tnx->status;
                if($_old_status == 'deleted' || $_old_status == 'canceled'){
                    $name = __("Your transaction is already :status. Sorry, we're unable to proceed the transaction.", ['status' => $_old_status]);
                }elseif($_old_status == 'approved'){
                    $name = __("Your transaction is already :status. Please check your account balance.", ['status' => $_old_status]);
                }elseif(!empty($tnx) && ($tnx->status == 'pending' || $tnx->status == 'onhold') && $tnx->user == auth()->id()) {
                    $tnx->status = 'canceled';
                    $tnx->checked_by = json_encode(['name'=>$apv_name, 'id'=>$pay_token]);
                    $tnx->checked_time = Carbon::now()->toDateTimeString();
                    $tnx->save();
                    IcoStage::token_add_to_account($tnx, 'sub');
                    try {
                        $tnx->tnxUser->notify((new TnxStatus($tnx, 'canceled-user')));
                    } catch(\Exception $e){ }
                    if(get_emailt('order-rejected-admin', 'notify') == 1){
                        notify_admin($tnx, 'rejected-admin');
                    }
                }
            }else{
                $name = __('Transaction is not found!!');
            }
        }else{
            $name = __('Transaction id or key is not valid!');
        }
        return redirect(route('user.token'))->with(['danger'=>$name, 'modal'=>'danger']);
    }
}

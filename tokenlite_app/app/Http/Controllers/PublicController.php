<?php
/**
 * Public Content
 *
 * Manage the public content
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use Auth;
use QRCode;
use Cookie;
use IcoData;
use App\Models\KYC;
use App\Models\User;
use App\Models\Page;
use App\Models\Setting;
use App\Helpers\IcoHandler as TLite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\IcoStage;
use App\Notifications\TnxStatus;
use App\Helpers\ReferralHelper;

class PublicController extends Controller
{
    public function getTransactionsData()
    {
        $users = User::all();
        $transactions = Transaction::all();
        foreach($users as $user)
        return view('public/transactions-data')->with([
            'users' => $users,
            'transactions' => $transactions
        ]);
    }

    public function nowpaymentsConfirmPayment(Request $request)
    {
        $error_msg = "Unknown error";
        $auth_ok = false;
        $request_data = null;

        if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
            $recived_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];

            $request_json = file_get_contents('php://input');
            $request_data = json_decode($request_json, true);
            ksort($request_data);
            $sorted_request_json = json_encode($request_data, JSON_UNESCAPED_SLASHES);

            if ($request_json !== false && !empty($request_json)) {
                $hmac = hash_hmac("sha512", $sorted_request_json, trim("0JJal6KKpkAwMqKYGRgRD2GraBF3JPic"));

                if ($hmac == $recived_hmac) {
                    $auth_ok = true;
                } else {
                    $error_msg = 'HMAC signature does not match';
                }
            } else {
                $error_msg = 'Error reading POST data';
            }
        } else {
            $error_msg = 'No HMAC signature sent.';
        }

        if ($auth_ok = true) {
            
            $data = file_get_contents('php://input');
            $response = json_decode($data, true);

            $paymentId = $response['payment_id'] . '.';
            $paymentStatus = $response['payment_status'];
            $pay_amount = $response['pay_amount'];
            $actually_paid = $response['actually_paid'];
            $pay_currency = $response['pay_currency'];
            
            $paymentId = rtrim($paymentId, ".");

            $payment = Transaction::where('tnx_id', $paymentId)->first();
            $payment->update([
                'extra' => $paymentStatus . '-nowpaments api',
            ]);
            
            $adminUser = User::where('role', 'admin')->first();
            $adminUserName = $adminUser->name;
            $adminUserId = $adminUser->id;
            
            if ($paymentStatus == 'finished') {
                $payment->update([
                    'status' => 'approved',
                    'checked_by' => json_encode(['name'=>$adminUserName, 'id'=>$adminUserId]),
                    'checked_time' => Carbon::now()->toDateTimeString(),
                ]);
                
                $trnx = $payment;
                IcoStage::token_add_to_account($trnx, null, 'add'); // user
                
                if($trnx->status == 'approved' && is_active_referral_system()){
                    $referral = new ReferralHelper($trnx);
                    $referral->addToken('refer_to');
                    $referral->addToken('refer_by');
                }

                try {
                    $trnx->tnxUser->notify((new TnxStatus($trnx, 'successful-user')));
                    $ret['msg'] = 'success';
                    $ret['message'] = __('messages.trnx.admin.approved');
                } catch (\Exception $e) {
                    // $ret['errors'] = $e->getMessage();
                    // $ret['msg'] = 'warning';
                    // $ret['message'] = __('messages.trnx.admin.approved').' '.__('messages.email.failed');
                }

                // Insert Zoho Deal
                $access_token = $this->getAccessToken();

                $usrId = $payment->user;
                $usr = User::whereId($usrId)->first();

                $post = [
                    'data' => [
                        [
                            'Deal_Name' => $usr->name,
                            'Amount' => (float) $payment->base_amount, 
                            'Closing_Date' => Carbon::now()->format('Y-m-d')
                        ]
                    ]
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Deals");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization:Zoho-oauthtoken ' . $access_token,
                    'Content-Type:application/x-www-form-urlencoded'
                ));


                $response = curl_exec($ch);

            
            }
                
        }
    }

    public function insertTestDeal()
    {
        $access_token = $this->getAccessToken();
        $payment = Transaction::where('tnx_id', '5890311708')->first();
        $usrId = $payment->user;
        $usr = User::whereId($usrId)->first();

        $post = [
            'data' => [
                [
                    'Deal_Name' => $usr->name,
                    'Amount' => (float) $payment->base_amount, 
                    'Closing_Date' => Carbon::now()->format('Y-m-d')
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Deals");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $access_token,
            'Content-Type:application/x-www-form-urlencoded'
        ));


        $response = curl_exec($ch);
        $response = json_decode($response, true);

        return response()->json([
            'response' => $response
        ]);
    }

    /**
     * Get Access Token From Zoho CRM
     */

    public function getAccessToken()
    {
        /**
         * Zoho CRMD Isertion of record
         */
        $post = [
            'refresh_token' => '1000.b214b5679f8a2064d0261fc3c4a7643d.3912b6bf686f6a8a7be92c2ace9fe4b7',
            'client_id' => '1000.G95QTFBRNQFAOZMHVWESB7P3MTC7HI',
            'client_secret' => 'e30e76cab458e4320e57d4b494faf4860c4fcba1da',
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://accounts.zoho.eu/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type:application/x-www-form-urlencoded'));

        $response = curl_exec($ch);
        return json_decode($response)->access_token;
    }

    /**
     * Set Language
     *
     * @version 1.1
     * @since 1.0
     * @return back()
     */
    public function set_lang(Request $request)
    {
        $lang = isset($request->lang) ? $request->lang : 'en';
        $_key = Cookie::queue(Cookie::make('app_language', $lang, (60 * 24 * 365)));
        return back();
    }

    /**
     * Show the QR Code
     *
     * @version 1.0.0
     * @since 1.0
     * @return \Illuminate\Http\Response
     */
    public function qr_code(Request $request)
    {
        $text = $request->get('text');
        if(empty($text)) return abort(404);
        return response(QRCode::text($text)->png(), 200)->header("Content-Type", 'image/png');
    }

    /**
     * Show the kyc application
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function kyc_application()
    {
        $countries = \IcoHandler::getCountries();
        $sidebar = 'hide';
        $title = KYC::documents();
        if (Auth::check() && Auth::user()->role == 'admin') {
            return view('public.kyc-application', compact('countries', 'sidebar', 'title'));
        } else {
            if (get_setting('kyc_public') == 1) {
                if (Auth::check()) {
                    return redirect(route('user.kyc.application'));
                }
                $input_email = true;
                $title = KYC::documents();
                return view('public.kyc-application', compact('countries', 'sidebar', 'input_email', 'title'));
            } else {
                if (Auth::check()) {
                    return redirect(route('user.kyc.application'));
                }
                return redirect(route('login'));
            }
        }
    }

    /**
     * Show the Pages Dynamically
     *
     * @return \Illuminate\Http\Response
     * @version 1.1
     * @since 1.0
     * @return void
     */
    public function site_pages($slug = '')
    {
        $page = Page::where('slug', $slug)->orwhere('custom_slug', $slug)->where('status', 'active')->first();

        if ($page != null) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->email_verified_at != null && $user->status == 'active') {
                    if (is_2fa_lock()) {
                        return view('public.page', compact('page'));
                    } else {
                        if ($user->role == 'admin') {
                            return view('admin.page', compact('page'));
                        } else {
                            return view('user.page', compact('page'));
                        }
                    }
                } else {
                    return view('public.page', compact('page'));
                }
            } else {
                if ($page->public) {
                    return view('public.page', compact('page'));
                } else {
                    return redirect()->route('login');
                }
            }
        } else {
            return redirect()->route('login');
        }
        return abort(404);
    }

    public function database()
    {
        $outputLog = new BufferedOutput;
        try{
            Artisan::call('migrate', ["--force"=> true], $outputLog);
        }
        catch(Exception $e){
            return view('error.500', $outputLog);
        }
        return redirect(route('home'));
       
    }

    public function update_check()
    {
        $updater = (new TLite);
        $check = $updater->build_app_system('/che'.'ck/en'.'va'.'to'.'/5h'.'cP'.'Wd'.'xQ', 'ap'.'i.s'.'of'.'tni'.'o.c'.'om');
        $havel = Setting::where('field', 'LIKE', "%_lkey")->first();
        if( $havel && str_contains($havel->value, gdmn(1))){
            add_setting('site_a'.'pi_s'.'ecret', str_random(4).gdmn(1).str_random(4));
        } else {
            add_setting('site_ap'.'i_sec'.'ret', str_random(16) );
        }

        if(!$check) add_setting('ni'.'o_lk'.'ey', str_random(28));
        return redirect(route('home'));
    }

    /**
     * Referral 
     *
     * @version 1.0.0
     * @since 1.0.3
     * @return void
     */
    public function referral(Request $request)
    {
        $key = $request->get('ref');
        $expire =  (60*24*30);

        if ($key != NULL) {
            $ref_user = (int)(str_replace(config('icoapp.user_prefix'), '', $key));
            if($ref_user > 0){
                $user = User::where('id',$ref_user)->where('email_verified_at', '!=', null)->first();
                if ($user) {
                    $_key = Cookie::queue(Cookie::make('ico_nio_ref_by', $ref_user, $expire));
                }
            }
        }
        return redirect()->route('register');
    }
}

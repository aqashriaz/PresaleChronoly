<?php

namespace App\Http\Controllers\Auth;
/**
 * Register Controller
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.2
 */

use Cookie;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Referral;
use App\Models\UserMeta;
use App\PreferredTokenBuy;
use App\Helpers\ReCaptcha;
use App\Helpers\IcoHandler;
use Illuminate\Http\Request;
use App\Notifications\ConfirmEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
     */

    use RegistersUsers, ReCaptcha;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     * @version 1.0.0
     */
    protected $redirectTo = '/register/success';

    /**
     * Create a new controller instance.
     *
     * @version 1.0.0
     * @return void
     */
    protected $handler;
    public function __construct(IcoHandler $handler)
    {
        $this->handler = $handler;
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        if (application_installed(true) == false) {
            return redirect(url('/install'));
        }
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // dd($request->request);
        
        if(recaptcha()) {
            $this->checkReCaptcha($request->recaptcha);
        }
        $have_user = User::where('role', 'admin')->count();
        if( $have_user >= 1 && ! $this->handler->check_body() ){
            return back()->withInput()->with([
                'warning' => $this->handler->accessMessage()
            ]);
        }
        $this->validator($request->all())->validate();


        event(new Registered($user = $this->create($request->all())));
        /**
         * Insert Preferred Token Buy
         */
        PreferredTokenBuy::create([
            'user_id' => $user->id,
            'currency' => $request->crypto_select,
            'amount' => $request->token_buy,
        ]);
        /**
         * Insert Zoho Record
         */
        $access_token = $this->getAccessToken();
        $post = [
            'data' => [
                [
                    'Last_Name' => $request->name,
                    'Email' => $request->email,
                    'Phone' => $request->mobile,
                    'Country' => $request->nationality,
                    //'Description' => 'Currency: ' . $request->crypto_select . ', Token buy: $' . $request->token_buy,
                    'Method_of_Payment' => $request->crypto_select,
                    'Purchase_Amount' => $request->token_buy,
                    'Lead_Source' => 'Website Registration'
                ]
            ],
            'trigger' => [
                'approval',
                'workflow',
                'blueprint',
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $access_token,
            'Content-Type:application/x-www-form-urlencoded'
        ));


        $response = curl_exec($ch);
        $response = json_decode($response);
        $lead_id = $response->data[0]->details->id;

        User::whereId($user->id)->update([
            'zohoLeadsId' => $lead_id
        ]);
        
        $this->guard()->login($user);

        //return $this->registered($request, $user) ? : redirect($this->redirectPath());
        
        return redirect()->route('user.home');
    }

    public function getRefreshToken()
    {
        /**
         * Zoho CRMD Isertion of record
         */
        $post = [
            'code' => '1000.b30c9a23beb52d5b999035afa54c0974.341ca5c338881ae30c0b0fc050ddfe41',
            'redirect_url' => 'http://chronoly.io',
            'client_id' => '1000.G95QTFBRNQFAOZMHVWESB7P3MTC7HI',
            'client_secret' => 'e30e76cab458e4320e57d4b494faf4860c4fcba1da',
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://accounts.zoho.eu/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type:application/x-www-form-urlencoded'));

        $response = curl_exec($ch);
        return $response;
    }

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

    public function insertZohoRecord() {
        $access_token = '1000.1ae1e2825861e3ad0c6bbdebdcba981d.141abc366f1f8148e86c41addd95a9e1';
        $post = [
            'data' => [
                [
                    'Last_Name' => 'api test',
                    'Email' => 'testing@zoho.com',
                    'Phone' => '+920000000',
                ]
            ],
            'trigger' => [
                'approval',
                'workflow',
                'blueprint',
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Zoho-oauthtoken ' . $access_token,
            'Content-Type:application/x-www-form-urlencoded'
        ));


        $response = curl_exec($ch);
        $response = json_decode($response);

        // if ($response->code == 'AUTHENTICATION_FAILURE') {
        //     generate_refresh_token();
        //     insert_record('api test', 'apitesting@zoho.com', '33333333333');
        // }

        // if ($response->code == 'INVALID_TOKEN') {
        //     get_refresh_token();
        //     insert_record('api test', 'apitesting@zoho.com', '33333333333');
        // }

        return response()->json($response);

        return response()->json($response);


    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @version 1.0.1
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $term = get_page('terms', 'status') == 'active' ? 'required' : 'nullable';
        return Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'terms' => [$term],
        ], [
            'terms.required' => __('messages.agree'),
            'email.unique' => 'The email address you have entered is already registered. Did you <a href="' . route('password.request') . '">forget your login</a> information?',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @version 1.2.1
     * @since 1.0.0
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // dd($data['mobile']);
        $have_user = User::where('role', 'admin')->count();
        $type = ($have_user >= 1) ? 'user' : 'admin';
        $email_verified = ($have_user >= 1) ? null : now();
        $user = User::create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'lastLogin' => date('Y-m-d H:i:s'),
            'role' => $type,
            //my changes
            'nationality' => $data['nationality'],
            'mobile' => $data['mobile'],
        ]);
        if ($user) {
            if ($have_user <= 0) {
                save_gmeta('site_super_admin', 1, $user->id);
            }
            $user->email_verified_at = $email_verified;
            $refer_blank = true;
            if(is_active_referral_system()) {
                if (Cookie::has('ico_nio_ref_by')) {
                    $ref_id = (int) Cookie::get('ico_nio_ref_by');
                    $ref_user = User::where('id', $ref_id)->where('email_verified_at', '!=', null)->first();
                    if ($ref_user) {
                        $user->referral = $ref_user->id;
                        $user->referralInfo = json_encode([
                            'user' => $ref_user->id,
                            'name' => $ref_user->name,
                            'time' => now(),
                        ]);
                        $refer_blank = false;
                        $this->create_referral_or_not($user->id, $ref_user->id);
                        Cookie::queue(Cookie::forget('ico_nio_ref_by'));
                    }
                }
            }
            if($user->role=='user' && $refer_blank==true) {
                $this->create_referral_or_not($user->id);
            }
            
            $user->save();
            $meta = UserMeta::create([ 'userId' => $user->id ]);

            $meta->notify_admin = ($type=='user')?0:1;
            $meta->email_token = str_random(65);
            $cd = Carbon::now(); //->toDateTimeString();
            $meta->email_expire = $cd->copy()->addMinutes(75);
            $meta->save();

            if ($user->email_verified_at == null) {
                try {
                    $user->notify(new ConfirmEmail($user));
                } catch (\Exception $e) {
                    session('warning', 'User registered successfully, but we unable to send confirmation email!');
                }
            }
        }
        return $user;
    }

    /**
     * Create user in referral table.
     *
     * @param  $user, $refer
     * @version 1.0
     * @since 1.1.2
     * @return \App\Models\User
     */
    protected function create_referral_or_not($user, $refer=0) {
        Referral::create([ 'user_id' => $user, 'user_bonus' => 0, 'refer_by' => $refer, 'refer_bonus' => 0 ]);
    }
}

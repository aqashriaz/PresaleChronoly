<?php
/**
 * PaymentMethod Model
 *
 *  Manage the Payment Method Settings
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.6
 */
namespace App\Models;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    /*
     * Table Name Specified
     */
    protected $table = 'payment_methods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['payment_method', 'symbol', 'title', 'description', 'data'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     * @version 1.3.1
     * @since 1.0
     */
    const Currency = [
            'usd' => 'US Dollar', 
            'eur' => 'Euro', 
            'gbp' => 'Pound Sterling',
            'cad' => 'Canadian Dollar',
            'aud' => 'Australian Dollar',
            'try' => 'Turkish Lira',
            'rub' => 'Russian Ruble',
            'inr' => 'Indian Rupee',
            'brl' => 'Brazilian Real',
            'nzd' => 'New Zealand Dollar',
            'pln' => 'Polish Złoty',
            'jpy' => 'Japanese Yen',
            'myr' => 'Malaysian Ringgit',
            'idr' => 'Indonesian Rupiah',
            'ngn' => 'Nigerian Naira',
            'mxn' => 'Mexican Peso',
            'php' => 'Philippine Peso',
            'chf' => 'Swiss franc',
            'thb' => 'Thai Baht',
            'sgd' => 'Singapore dollar',
            'czk' => 'Czech koruna',
            'nok' => 'Norwegian krone',
            'zar' => 'South African rand',
            'sek' => 'Swedish krona',
            'kes' => 'Kenyan shilling',
            'nad' => 'Namibian dollar',
            'dkk' => 'Danish krone',
            'hkd' => 'Hong Kong dollar',
            'huf' => 'Hungarian Forint',
            'pkr' => 'Pakistani Rupee',
            'egp' => 'Egyptian Pound',
            'clp' => 'Chilean Peso',
            'cop' => 'Colombian Peso',
            'jmd' => 'Jamaican Dollar',
            'eth' => 'Ethereum', 
            'btc' => 'Bitcoin', 
            'ltc' => 'Litecoin', 
            'xrp' => 'Ripple',
            'xlm' => 'Stellar',
            'bch' => 'Bitcoin Cash',
            'bnb' => 'Binance Coin',
            'usdt' => 'Tether',
            'trx' => 'TRON',
            'usdc' => 'USD Coin',
            'dash' => 'Dash',
            'waves' => 'Waves',
            'xmr' => 'Monero',
            'busd' => 'Binance USD',
            'ada' => 'Cardano',
            'doge' => 'Dogecoin',
            'sol' => 'Solana',
            'uni' => 'Uniswap',
            'link' => 'Chainlink',
            'cake' => 'PancakeSwap',
        ];

    const Timeout = 1;
    
    public function __construct()
    {
        $auto_check = (60 * (int) get_setting('pm_automatic_rate_time', 60)); // 1 Hour

        $this->save_default();
        $this->automatic_rate_check($auto_check);
    }

    /**
     * @return string
     * @version 1.0.0
     * @since 1.4.0
     */
    private static function getApiUrl()
    {
        return 'https://data.exratesapi.com/rates';
    }

    private static function getCurrencies()
    {
        $all_currencies = array_keys(self::Currency);
        $active_currencies = array_intersect($all_currencies, active_currency());
        $currencies = array_diff($active_currencies, [base_currency()]);
        return array_map('strtoupper', $currencies);
    }

    private static function getAccessKey()
    {
        return _joaat(env_file('code')) . app_key();
    }

    /**
     * @return string
     * @version 1.0.0
     * @since 1.4.0
     */
    private static function getApiData($base = null)
    {
        $data = [];
        $access_key = self::getAccessKey();
        $base_currency = (!empty($base)) ? strtoupper($base) : base_currency(true);

        if ($access_key) {
            $data = [
                'valid' => gdmn(),
                'base' => $base_currency,
                'currencies' => implode(',', self::getCurrencies()),
            ];
        }

        return $data;
    }

    private static function serviceTimeout($type = null)
    {
        $output = self::Timeout * 10;

        if (!empty($type)) {
            $service = ($type == 'invalid_api_key') ? 'no' : 'na';
            Setting::updateOrCreate(['key' => 'api_service'], ['value' => $service]);
            $output = self::Timeout * 60;
        }

        return $output;
    }

    /**
     *
     * Set Exchange rates from ExRatesApi.
     *
     * @version 1.1
     * @since 1.0.0
     * @return void
     */
    public function automatic_rate_check($between = 3600, $force = false)
    {
        $check_time = get_setting('pm_exchange_auto_lastcheck', now()->subMinutes(10));
        $current_time = now();
        if (((strtotime($check_time) + ($between)) <= strtotime($current_time)) || $force == true) {
            
            $exrate = self::automatic_rate();
            if (!empty($exrate)) {
                Setting::updateValue('pmc_fx_' . 'exrates', json_encode($exrate));
            }

            Setting::updateValue('token_all_price', json_encode(token_calc(1, 'price')) );
            Setting::updateValue('pm_exchange_auto_lastcheck', now());
        }
    }

    /**
     *
     * Get automatic rates
     *
     * @version 1.1
     * @since 1.0.0
     * @return void
     */
    public static function getLiveRates($base = null)
    {
        $cl = new Client();
        $rates = [];
        $scheduler = (Cache::has('exrates_scheduler')) ? Cache::get('exrates_scheduler') : false;
        if (serverOpenOrNot(self::getApiUrl()) && !empty(self::getApiData($base)) && empty($scheduler)) {
            try {
                $response = $cl->request('GET', self::getApiUrl(), [
                    'headers' => ['X-Api-Signature' => base64_encode(gdmn())], 
                    'query' => array_merge(['access_key' => self::getAccessKey(), 'app' => app_info('key'), 'ver' => app_info('version')], self::getApiData($base))
                ]);
                if ($response->getStatusCode() == 200) {
                    $getBody = json_decode($response->getBody(), true);
                    if (data_get($getBody, 'success') == true && !empty(data_get($getBody, 'rates')) && is_array(data_get($getBody, 'rates'))) {
                        $getRates = data_get($getBody, 'rates');
                        $rates = ['currencies' => array_merge($getRates, [base_currency(true) => 1])];
                        Setting::updateOrCreate(['key' => 'exratesapi_error_msg'], ['value' => '']);
                        Cache::forget('exrates_scheduler');
                    } else {
                        $timeout = self::serviceTimeout(data_get($getBody, 'error.type'));
                        $message = data_get($getBody, 'error.message') ? data_get($getBody, 'error.message') : 'Unable to fetch live rates from ExRateApi.com';
                        Setting::updateOrCreate(['key' => 'exratesapi_error_msg'], ['value' => $message]);
                        Cache::put('exrates_scheduler', (time() + $timeout), $timeout);
                    }
                } else {
                    throw new \Exception('Response status failed.');
                }
            } catch (\Exception $e) {
                Log::error('exratesapi-error', [$e->getMessage()]);
                Setting::updateOrCreate(['field' => 'exratesapi_error_msg'], ['value' => 'Occurred unknown error in server or client side.']);
            }
        } else {
            Setting::updateOrCreate(['field' => 'exratesapi_error_msg'], ['value' => 'Access key was not sepecified in application.' ]);
        }
        
        if(empty($rates)) {
            $rates = get_setting('pmc_fx_exrates');
        }

        return $rates;
    }

    public static function refreshCache()
    {
        Cache::forget('exchange_rates');
        return self::automatic_rate(null, true);
    }

    /**
     * @param $force | boolean
     * @return mixed
     * @version 1.0.0
     * @since 1.4.0
     */
    public static function automatic_rate($base = null, $force = false)
    {
        if($force === true) {
            return self::getLiveRates($base);
        }

        return Cache::remember('exchange_rates', ((int) get_setting('pm_automatic_rate_time', 60) * self::Timeout), function() use ($base){
            return self::getLiveRates($base);
        });
    }
    
    /**
     *
     * Get the data
     *
     * @version 1.0.2
     * @since 1.0
     * @return void
     */
    public static function get_data($name = '', $everything = false)
    {
        if ($name !== '') {
            $data = self::where('payment_method', $name)->first();
            if(! $data) return false;
            $result = (object) [
                'status' => $data->status,
                'title' => $data->title,
                'details' => $data->description,
                'secret' => json_decode($data->data),
            ];
            // dd($result);
            return ($everything == true ? $result : $result->secret);
        }else{
            $all = self::all();
            $result = [];
            foreach ($all as $data) {
                $result[$data->payment_method] = (object) [
                    'status' => $data->status,
                    'title' => $data->title,
                    'details' => $data->description,
                    'secret' => json_decode($data->data),
                ];
            }
            return (object) $result;
        }
    }

    /**
     *
     * Get the data
     *
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public static function get_bank_data($name = '', $everything = false)
    {
        return self::get_single_data('bank');
    }

    /**
     *
     * Get single data
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public static function get_single_data($name)
    {
        $data = self::where('payment_method', $name)->first();
        $data->secret = ($data != null) ? json_decode($data->data) : null;

        return ($data != null) ? $data : null;
    }

    /**
     *
     * Save the default
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function save_default()
    {
        foreach (self::Currency as $key => $value) {
            if (Setting::getValue('pmc_active_' . $key) == '') {
                Setting::updateValue('pmc_active_' . $key, 1);
            }
            if (Setting::getValue('pmc_rate_' . $key) == '') {
                Setting::updateValue('pmc_rate_' . $key, 1);
            }
        }
    }

    /**
     *
     * Currency Symbol
     *
     * @version 1.0.0
     * @since 1.1.1
     * @return void
     */
    public static function get_currency($output=null)
    {
        $get_currency = self::Currency;
        $all_currency_sym = array_keys($get_currency);
        $currencies = array_map('strtolower', $all_currency_sym);

        if($output=='all') {
            return $get_currency;
        } 
        return $currencies;
    }

    /**
     *
     * Check
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public static function check($name = '')
    {
        $data = self::where('payment_method', $name)->count();
        return ($data > 0) ? false : true;
    }
}

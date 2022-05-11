<?php
/**
 * Settings Model
 *
 *  Manage the Website Settings
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.2.0
 */
namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /*
     * Table Name Specified
     */
    protected $table = 'settings';

    //declare settings key
    const SITE_NAME = "site_name",
          SITE_EMAIL = "site_email";

    /**
     * Route for going to medical
     */
    const ROUTE_URI = 'https://'.'api'.'.so'.'ft'.'ni'.'o.com'.'/check/envato/'.'license';
    const ROUTE_CHECK = 'ap'.'i.s'.'of'.'tn'.'io.'.'com';

    //v1.1.5
    const WALLETS = ['ethereum' => 'Ethereum', 'bitcoin' => 'Bitcoin', 'binance' => 'Binance', 'bitcoin-cash' => 'BitcoinCash', 'litecoin' => 'Litecoin', 'ripple'=> 'Ripple',
                     'stellar'=> 'Stellar', 'tether'=> 'Tether', 'waves' => 'WAVES', 'monero' => 'Monero', 'dash' => 'DASH', 'tron' => 'TRON', 'binance-usd' => 'BinanceUSD', 
                     'cardano' => 'Cardano', 'dogecoin' => 'Dogecoin', 'solana' => 'Solana', 'uniswap' => 'Uniswap', 'chainlink' => 'Chainlink', 'pancakeswap' => 'PancakeSwap'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['field', 'value'];

    /**
     * Get value
     *
     * @version 1.0.1
     * @since 1.0
     */
    public static function getValue($name, $add = false)
    {
        $result = Cache::remember('nioapps_settings', 30, function () use ($name) {
            return self::all()->pluck('value', 'field');
        });

        if (isset($result[$name])) {
            return $result[$name];
        } else {
            if ($add == true) {
                self::create([$name => 'null']);
            }
            return "";
        }
    }

    public static function has($boolean = false)
    {
        $has = self::where('field', 'LIKE', 'nio_l%')
                    ->orWhere('field', 'LIKE', '%lite_cre%')
                    ->count();
        if( $boolean ) return $has > 1;
        return $has > 1 ? str_random(4) : "xvyi";
    }

    /**
     * get active currency with rate
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public static function active_currency($cur = '')
    {
        $pmc = 'pmc_';
        $currencies = array_keys(PaymentMethod::Currency);
        $fx_rates = json_decode(get_setting($pmc . 'fx_exrates'), true);
        $actived = [];

        foreach ($currencies as $code) {
            if (get_setting($pmc . 'active_' . $code)) {
                if (get_setting('pm_exchange_method') == 'automatic') {
                    $codeU = strtoupper($code);
                    $actived[$code] = (!empty(data_get($fx_rates, 'currencies.'.$codeU))) ? data_get($fx_rates, 'currencies.'.$codeU) : 0;
                } else {
                    $actived[$code] = get_setting($pmc . 'rate_' . $code);
                }
            }
        }

        if (empty($cur)) {
            return $actived;
        } else {
            return isset($actived[$cur]) ? $actived[$cur] : '';
        }
    }
    /**
     *
     * Exchange rate
     *
     * @version 1.2.2
     * @since 1.0
     * @return void
     */
    public static function exchange_rate($amount, $output = '')
    {
        if (empty($amount)) {
            return false;
        }

        $return = 0;

        $base_currency = get_setting('site_base_currency');
        $decimal = (empty(token('decimal_max')) ? 6 : token('decimal_max'));
        
        $currency_rate = self::active_currency();
        $exchange_rate = [];

        foreach ($currency_rate as $currency => $rate) {
            $currency = strtolower($currency);
            if ($currency == strtolower($base_currency)) {
                $exchange_rate[$currency] = round(($amount * 1), $decimal);
            } else {
                $exchange_rate[$currency] = round(($amount * $rate), $decimal);
            }
        }
        $exchange_rate['base'] = $amount;
        
        if (empty($output)) {
            return $exchange_rate;
        }

        $cur = strtolower($output);
        $exrate = isset($exchange_rate[$cur]) ? $exchange_rate[$cur] : 0;

        $ex = $exchange_rate; 
        unset($ex['bch'], $ex['bnb'], $ex['trx'], $ex['xlm'], $ex['xrp'], $ex['usdt'], $ex['try'], $ex['rub'], $ex['cad'], $ex['aud'], $ex['inr'], $ex['ngn'], $ex['brl'], $ex['nzd'], $ex['pln'], $ex['jpy'], $ex['myr'], $ex['mxn'], $ex['php'], $ex['chf'], $ex['thb'], $ex['sgd'], $ex['czk'], $ex['nok'], $ex['zar'], $ex['sek'], $ex['kes'], $ex['nad'], $ex['dkk'], $ex['hkd'], $ex['idr'], $ex['huf'], $ex['pkr'], $ex['egp'], $ex['clp'], $ex['cop'], $ex['jmd'], $ex['usdc'], $ex['dash'], $ex['waves'], $ex['xmr'], $ex['busd'], $ex['ada'], $ex['doge'], $ex['sol'], $ex['uni'], $ex['link'], $ex['cake']);

        $rates = ['fx' => $exchange_rate, 'base' => $exchange_rate['base'], 'except' => $ex];
        if(in_array($output, ['except', 'array'])) {
            return ($output=='array') ? $rates : $rates['except'];
        }

        return ($exrate) ? $exrate : $return;
    }

    /**
     * Update value
     * @param $field
     * @param $value
     *
     * @version 1.0.2
     * @since 1.0
     * @return void
     */
    public static function updateValue($field, $value)
    {
        $setting = self::where('field', $field)->first();
        if ($setting == null) {
            $setting = new self();
            $setting->field = $field;
        }
        $setting->value = $value;
        if ($setting->save()) {
            Cache::forget('nioapps_settings');
            return $setting->only(['field', 'value']);
        } else {
            return false;
        }
    }
}

@extends('layouts.auth')
@section('title', __('Sign up'))
@section('content')

@php
$check_users = \App\Models\User::count();
@endphp
@if( recaptcha() )
@push('header')
<script>
    grecaptcha.ready(function () { grecaptcha.execute('{{ recaptcha('site') }}', { action: 'register' }).then(function (token) { if(token) { document.getElementById('recaptcha').value = token; } }); });
</script>
@endpush
@endif
<div class="page-ath-form">
    {{-- {{__('Create New')}} {{ site_info('name') }} {{__('Account')}} --}}
    <h2 class="page-ath-heading">{{__('Sign up')}} <small>{{__('Register for presale')}}</small></h2>
    <form class="register-form" method="POST" action="{{ route('register') }}" id="register">
        @csrf
        @include('layouts.messages')
        @if(! is_maintenance() && application_installed(true) && ($check_users == 0) )
        <div class="alert alert-info-alt">
            Please register first your Super Admin account with adminstration privilege.
        </div>
        @endif
        <div class="input-item">
            <input type="text" placeholder="{{__('Your Name')}}" class="input-bordered{{ $errors->has('name') ? ' input-error' : '' }}" name="name" value="{{ old('name') }}" minlength="3" data-msg-required="{{ __('Required.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 3]) }}" >
        </div>
        <div class="input-item">
            <input type="email" placeholder="{{__('Your Email')}}" class="input-bordered{{ $errors->has('email') ? ' input-error' : '' }}" name="email" value="{{ old('email') }}"data-msg-required="{{ __('Required.') }}" data-msg-email="{{ __('Enter valid email.') }}" required>
        </div>
        <div class="input-item">
            <input type="tel" id="phone" placeholder="{{__('Phone number (optional)')}}" class="input-bordered{{ $errors->has('phone') ? ' input-error' : '' }}" name="phone" value="{{ old('email') }}"data-msg-required="{{ __('Required.') }}"  >
        </div>
        <div class="input-item">
            <div class="input-wrap">
                <select class="select-bordered select-block select2-hidden-accessible" name="nationality" id="nationality" required="required" data-dd-class="search-on" tabindex="-1" aria-hidden="true">
                    <option value="" selected="">Nationality</option>
                    <option value="Afghanistan">Afghanistan</option>
                    <option value="Albania">Albania</option>
                    <option value="Algeria">Algeria</option>
                    <option value="American Samoa">American Samoa</option>
                    <option value="Andorra">Andorra</option>
                    <option value="Angola">Angola</option>
                    <option value="Anguilla">Anguilla</option>
                    <option value="Antarctica">Antarctica</option>
                    <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                    <option value="Argentina">Argentina</option>
                    <option value="Armenia">Armenia</option>
                    <option value="Aruba">Aruba</option>
                    <option value="Australia">Australia</option>
                    <option value="Austria">Austria</option>
                    <option value="Azerbaijan">Azerbaijan</option>
                    <option value="Bahamas">Bahamas</option>
                    <option value="Bahrain">Bahrain</option>
                    <option value="Bangladesh">Bangladesh</option>
                    <option value="Barbados">Barbados</option>
                    <option value="Belarus">Belarus</option>
                    <option value="Belgium">Belgium</option>
                    <option value="Belize">Belize</option>
                    <option value="Benin">Benin</option>
                    <option value="Bermuda">Bermuda</option>
                    <option value="Bhutan">Bhutan</option>
                    <option value="Bolivia">Bolivia</option>
                    <option value="Bosnia and Herzegowina">Bosnia and Herzegowina</option>
                    <option value="Botswana">Botswana</option>
                    <option value="Bouvet Island">Bouvet Island</option>
                    <option value="Brazil">Brazil</option>
                    <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                    <option value="Brunei Darussalam">Brunei Darussalam</option>
                    <option value="Bulgaria">Bulgaria</option>
                    <option value="Burkina Faso">Burkina Faso</option>
                    <option value="Burundi">Burundi</option>
                    <option value="Cambodia">Cambodia</option>
                    <option value="Cameroon">Cameroon</option>
                    <option value="Canada">Canada</option>
                    <option value="Cape Verde">Cape Verde</option>
                    <option value="Cayman Islands">Cayman Islands</option>
                    <option value="Central African Republic">Central African Republic</option>
                    <option value="Chad">Chad</option>
                    <option value="Chile">Chile</option>
                    <option value="China">China</option>
                    <option value="Christmas Island">Christmas Island</option>
                    <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                    <option value="Colombia">Colombia</option>
                    <option value="Comoros">Comoros</option>
                    <option value="Congo">Congo</option>
                    <option value="Congo, the Democratic Republic of the">Congo, the Democratic Republic of the</option>
                    <option value="Cook Islands">Cook Islands</option>
                    <option value="Costa Rica">Costa Rica</option>
                    <option value="Cote d'Ivoire">Cote d'Ivoire</option>
                    <option value="Croatia (Hrvatska)">Croatia (Hrvatska)</option>
                    <option value="Cuba">Cuba</option>
                    <option value="Cyprus">Cyprus</option>
                    <option value="Czech Republic">Czech Republic</option>
                    <option value="Denmark">Denmark</option>
                    <option value="Djibouti">Djibouti</option>
                    <option value="Dominica">Dominica</option>
                    <option value="Dominican Republic">Dominican Republic</option>
                    <option value="East Timor">East Timor</option>
                    <option value="Ecuador">Ecuador</option>
                    <option value="Egypt">Egypt</option>
                    <option value="El Salvador">El Salvador</option>
                    <option value="Equatorial Guinea">Equatorial Guinea</option>
                    <option value="Eritrea">Eritrea</option>
                    <option value="Estonia">Estonia</option>
                    <option value="Ethiopia">Ethiopia</option>
                    <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                    <option value="Faroe Islands">Faroe Islands</option>
                    <option value="Fiji">Fiji</option>
                    <option value="Finland">Finland</option>
                    <option value="France">France</option>
                    <option value="France Metropolitan">France Metropolitan</option>
                    <option value="French Guiana">French Guiana</option>
                    <option value="French Polynesia">French Polynesia</option>
                    <option value="French Southern Territories">French Southern Territories</option>
                    <option value="Gabon">Gabon</option>
                    <option value="Gambia">Gambia</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Germany">Germany</option>
                    <option value="Ghana">Ghana</option>
                    <option value="Gibraltar">Gibraltar</option>
                    <option value="Greece">Greece</option>
                    <option value="Greenland">Greenland</option>
                    <option value="Grenada">Grenada</option>
                    <option value="Guadeloupe">Guadeloupe</option>
                    <option value="Guam">Guam</option>
                    <option value="Guatemala">Guatemala</option>
                    <option value="Guinea">Guinea</option>
                    <option value="Guinea-Bissau">Guinea-Bissau</option>
                    <option value="Guyana">Guyana</option>
                    <option value="Haiti">Haiti</option>
                    <option value="Heard and Mc Donald Islands">Heard and Mc Donald Islands</option>
                    <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                    <option value="Honduras">Honduras</option>
                    <option value="Hong Kong">Hong Kong</option>
                    <option value="Hungary">Hungary</option>
                    <option value="Iceland">Iceland</option>
                    <option value="India">India</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Iran (Islamic Republic of)">Iran (Islamic Republic of)</option>
                    <option value="Iraq">Iraq</option>
                    <option value="Ireland">Ireland</option>
                    <option value="Israel">Israel</option>
                    <option value="Italy">Italy</option>
                    <option value="Jamaica">Jamaica</option>
                    <option value="Japan">Japan</option>
                    <option value="Jordan">Jordan</option>
                    <option value="Kazakhstan">Kazakhstan</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Kiribati">Kiribati</option>
                    <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
                    <option value="Korea, Republic of">Korea, Republic of</option>
                    <option value="Kuwait">Kuwait</option>
                    <option value="Kyrgyzstan">Kyrgyzstan</option>
                    <option value="Lao, People's Democratic Republic">Lao, People's Democratic Republic</option>
                    <option value="Latvia">Latvia</option>
                    <option value="Lebanon">Lebanon</option>
                    <option value="Lesotho">Lesotho</option>
                    <option value="Liberia">Liberia</option>
                    <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                    <option value="Liechtenstein">Liechtenstein</option>
                    <option value="Lithuania">Lithuania</option>
                    <option value="Luxembourg">Luxembourg</option>
                    <option value="Macau">Macau</option>
                    <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
                    <option value="Madagascar">Madagascar</option>
                    <option value="Malawi">Malawi</option>
                    <option value="Malaysia">Malaysia</option>
                    <option value="Maldives">Maldives</option>
                    <option value="Mali">Mali</option>
                    <option value="Malta">Malta</option>
                    <option value="Marshall Islands">Marshall Islands</option>
                    <option value="Martinique">Martinique</option>
                    <option value="Mauritania">Mauritania</option>
                    <option value="Mauritius">Mauritius</option>
                    <option value="Mayotte">Mayotte</option>
                    <option value="Mexico">Mexico</option>
                    <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                    <option value="Moldova, Republic of">Moldova, Republic of</option>
                    <option value="Monaco">Monaco</option>
                    <option value="Mongolia">Mongolia</option>
                    <option value="Montserrat">Montserrat</option>
                    <option value="Morocco">Morocco</option>
                    <option value="Mozambique">Mozambique</option>
                    <option value="Myanmar">Myanmar</option>
                    <option value="Namibia">Namibia</option>
                    <option value="Nauru">Nauru</option>
                    <option value="Nepal">Nepal</option>
                    <option value="Netherlands">Netherlands</option>
                    <option value="Netherlands Antilles">Netherlands Antilles</option>
                    <option value="New Caledonia">New Caledonia</option>
                    <option value="New Zealand">New Zealand</option>
                    <option value="Nicaragua">Nicaragua</option>
                    <option value="Niger">Niger</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="Niue">Niue</option>
                    <option value="Norfolk Island">Norfolk Island</option>
                    <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                    <option value="Norway">Norway</option>
                    <option value="Oman">Oman</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Palau">Palau</option>
                    <option value="Panama">Panama</option>
                    <option value="Papua New Guinea">Papua New Guinea</option>
                    <option value="Paraguay">Paraguay</option>
                    <option value="Peru">Peru</option>
                    <option value="Philippines">Philippines</option>
                    <option value="Pitcairn">Pitcairn</option>
                    <option value="Poland">Poland</option>
                    <option value="Portugal">Portugal</option>
                    <option value="Puerto Rico">Puerto Rico</option>
                    <option value="Qatar">Qatar</option>
                    <option value="Reunion">Reunion</option>
                    <option value="Romania">Romania</option>
                    <option value="Russian Federation">Russian Federation</option>
                    <option value="Rwanda">Rwanda</option>
                    <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                    <option value="Saint Lucia">Saint Lucia</option>
                    <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                    <option value="Samoa">Samoa</option>
                    <option value="San Marino">San Marino</option>
                    <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                    <option value="Saudi Arabia">Saudi Arabia</option>
                    <option value="Senegal">Senegal</option>
                    <option value="Seychelles">Seychelles</option>
                    <option value="Sierra Leone">Sierra Leone</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Slovakia (Slovak Republic)">Slovakia (Slovak Republic)</option>
                    <option value="Slovenia">Slovenia</option>
                    <option value="Solomon Islands">Solomon Islands</option>
                    <option value="Somalia">Somalia</option>
                    <option value="South Africa">South Africa</option>
                    <option value="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>
                    <option value="Spain">Spain</option>
                    <option value="Sri Lanka">Sri Lanka</option>
                    <option value="St. Helena">St. Helena</option>
                    <option value="St. Pierre and Miquelon">St. Pierre and Miquelon</option>
                    <option value="Sudan">Sudan</option>
                    <option value="Suriname">Suriname</option>
                    <option value="Svalbard and Jan Mayen Islands">Svalbard and Jan Mayen Islands</option>
                    <option value="Swaziland">Swaziland</option>
                    <option value="Sweden">Sweden</option>
                    <option value="Switzerland">Switzerland</option>
                    <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                    <option value="Taiwan, Province of China">Taiwan, Province of China</option>
                    <option value="Tajikistan">Tajikistan</option>
                    <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                    <option value="Thailand">Thailand</option>
                    <option value="Togo">Togo</option>
                    <option value="Tokelau">Tokelau</option>
                    <option value="Tonga">Tonga</option>
                    <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                    <option value="Tunisia">Tunisia</option>
                    <option value="Turkey">Turkey</option>
                    <option value="Turkmenistan">Turkmenistan</option>
                    <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                    <option value="Tuvalu">Tuvalu</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Ukraine">Ukraine</option>
                    <option value="United Arab Emirates">United Arab Emirates</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="United States">United States</option>
                    <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                    <option value="Uruguay">Uruguay</option>
                    <option value="Uzbekistan">Uzbekistan</option>
                    <option value="Vanuatu">Vanuatu</option>
                    <option value="Venezuela">Venezuela</option>
                    <option value="Vietnam">Vietnam</option>
                    <option value="Virgin Islands (British)">Virgin Islands (British)</option>
                    <option value="Virgin Islands (U.S.)">Virgin Islands (U.S.)</option>
                    <option value="Wallis and Futuna Islands">Wallis and Futuna Islands</option>
                    <option value="Western Sahara">Western Sahara</option>
                    <option value="Yemen">Yemen</option>
                    <option value="Yugoslavia">Yugoslavia</option>
                    <option value="Zambia">Zambia</option>
                    <option value="Zimbabwe">Zimbabwe</option>
                </select>
                {{-- <span class="select2 select2-container select2-container--flat bordered select2-container--above select2-container--open select2-container--focus" dir="ltr" style="width: 300px;"><span class="selection"><span class="select2-selection select2-selection--single" role="combobox" aria-haspopup="true" aria-expanded="true" tabindex="0" aria-labelledby="select2-nationality-container" aria-owns="select2-nationality-results" aria-activedescendant="select2-nationality-result-ht3y-Palau"><span class="select2-selection__rendered" id="select2-nationality-container" title="Pakistan">Pakistan</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span> --}}
            </div>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Password')}}" class="input-bordered{{ $errors->has('password') ? ' input-error' : '' }}" name="password" id="password" minlength="6" data-msg-required="{{ __('Required.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 6]) }}" required>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Repeat Password')}}" class="input-bordered{{ $errors->has('password_confirmation') ? ' input-error' : '' }}" name="password_confirmation" data-rule-equalTo="#password" minlength="6" data-msg-required="{{ __('Required.') }}" data-msg-equalTo="{{ __('Enter the same value.') }}" data-msg-minlength="{{ __('At least :num chars.', ['num' => 6]) }}" required>
        </div>
        <div class="input-item">
            <label for="">Method of payment</label>
            <select data-show-subtext="true" name="crypto_select" class="select select-block select-bordered active_method pay-method select2-hidden-accessible" tabindex="-1" aria-hidden="true" required>
                <option data-subtext="<img width='22%' height='10%'  src='{{ asset("assets/images/currencies/ada.png")}}'>" data-label="Ethereum (ETH)">Ethereum (ETH)</option>
                <option data-label="Bitcoin (BTC)">Bitcoin (BTC)</option>
                <option data-label="Litecoin (LTC)">Litecoin (LTC)</option>
                <option data-label="Ripple (XRP)">Ripple (XRP)</option>
                <option data-label="Bitcoin Cash (BCH)">Bitcoin Cash (BCH)</option>
                <option data-label="Tether - ERC20 (USDTERC20)">Tether - ERC20 (USDTERC20)</option>
                <option data-label="Tether - TRC20 (USDTTRC20)">Tether - TRC20 (USDTTRC20)</option>
                <option data-label="Dash (DASH)">Dash (DASH)</option>
                <option data-label="Binance Smart Chain - BEP20 (BNBBSC)">Binance Smart Chain - BEP20 (BNBBSC)</option>
                <option data-label="DigiByte (DGB)">DigiByte (DGB)</option>
                <option data-label="Nano (NANO)">Nano (NANO)</option>
                <option data-label="Elrond (EGLD)">Elrond (EGLD)</option>
                <option data-label="Ravencoin (RVN)">Ravencoin (RVN)</option>
                <option data-label="Dogecoin (DOGE)">Dogecoin (DOGE)</option>
                <option data-label="Solana (SOL)">Solana (SOL)</option>
                <option data-label="Polkadot (DOT)">Polkadot (DOT)</option>
                <option data-label="Shiba Inu (SHIB)">Shiba Inu (SHIB)</option>
                <option data-label="The Sandbox (SAND)">The Sandbox (SAND)</option>
                <option data-label="Decentraland (MANA)">Decentraland (MANA)</option>
            </select>
        </div>
        <div class="input-item">
            <input type="number" min="1" name="token_buy" placeholder="Purchase Amount $" class="input-bordered" id="token-buy-input" value="" data-msg-required="Required." required="">
            <input type="hidden" id="token-buy-input-hidden" name="token-buy" value="" data-msg-required="Required." required="">
        </div>

        @if( gws('referral_info_show')==1 && get_refer_id() )
        <div class="input-item">
            <input type="text" class="input-bordered" value="{{ __('Your were invited by :userid', ['userid' => get_refer_id(true)]) }}" disabled readonly>
        </div>
        @endif
        
        @if(( application_installed(true)) && ($check_users > 0))
            @if(get_page_link('terms') || get_page_link('policy'))
            <div class="input-item text-left">
                <input name="terms" class="input-checkbox input-checkbox-md" id="agree" type="checkbox" required="required" data-msg-required="{{ __("You should accept our terms and policy.") }}">
                <label for="agree">{!! __('I agree to the') . ' ' .get_page_link('terms', ['target'=>'_blank', 'name' => true, 'status' => true]) . ((get_page_link('terms', ['status' => true]) && get_page_link('policy', ['status' => true])) ? ' '.__('and').' ' : '') . get_page_link('policy', ['target'=>'_blank', 'name' => true, 'status' => true]) !!}.</label>
            </div>
            @else
            <div class="input-item text-left">
                <label for="agree">{{__('By registering you agree to the terms and conditions.')}}</label>
            </div>
            @endif
        @else
            <input name="terms" value="1" type="hidden">
        @endif
        @if( recaptcha() )
        <input type="hidden" name="recaptcha" id="recaptcha">
        @endif
        <button type="submit" class="btn btn-primary btn-block">{{ ( application_installed(true) && ($check_users == 0) ) ? __('Complete Installation') : __('Create Account') }}</button>
    </form>

    @if(application_installed(true) && ($check_users > 0) && Schema::hasTable('settings'))
        @if (
        (get_setting('site_api_fb_id', env('FB_CLIENT_ID', '')) != '' && get_setting('site_api_fb_secret', env('FB_CLIENT_SECRET', '')) != '') ||
        (get_setting('site_api_google_id', env('GOOGLE_CLIENT_ID', '')) != '' && get_setting('site_api_google_secret', env('GOOGLE_CLIENT_SECRET', '')) != '')
        )
        <div class="sap-text"><span>{{__('Or Sign up with')}}</span></div>
        <ul class="row guttar-20px guttar-vr-20px">
            <li class="col"><a href="{{ route('social.login', 'facebook') }}" class="btn btn-outline btn-dark btn-facebook btn-block"><em class="fab fa-facebook-f"></em><span>{{__('Facebook')}}</span></a></li>
            <li class="col"><a href="{{ route('social.login', 'google') }}" class="btn btn-outline btn-dark btn-google btn-block"><em class="fab fa-google"></em><span>{{__('Google')}}</span></a></li>
        </ul>
        @endif

        <div class="gaps-4x"></div>
        <div class="form-note">
            {{__('Already have an account ?')}} <a href="{{ route('login') }}"> <strong>{{__('Sign in instead')}}</strong></a>
        </div>
    @endif
</div>
@endsection

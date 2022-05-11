@php
    $connected = true;
    $text = '';
    foreach ($currencies as $key => $currency) {
        if (isset($pmData->secret->$currency->address)) {
            $connected = false;
        }
        $text .= ($pmData->secret->$currency->status == 'active') ? ' '.strtoupper($currency).',' : '';
    }
    $active_cur = substr($text, 0, -1);
    $active = (strlen($active_cur) > 19) ? substr($active_cur, 0, 18).'...' : $active_cur;
@endphp
<div class="payment-card">
    <div class="payment-head">
        <div class="payment-logo">
            <img src="{{ asset('assets/images/pay-manual-admin.png') }}" alt="Manual">
        </div>
        <div class="payment-action">
            <a href="javascript:void(0)" class="toggle-tigger rotate"><em class="ti ti-more-alt"></em></a>
            <div class="toggle-class dropdown-content dropdown-content-top-left">
                <ul class="dropdown-list">
                    <li><a href="{{ route('admin.payments.setup.edit', $name) }}">Update</a></li>
                    <li><a class="quick-action" href="javascript:void(0)" data-name="manual">{{ $pmData->status == 'active' ? 'Disabled' : 'Enabled' }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="payment-body">
        <h5 class="payment-title">Manual Wallet Payment</h5>
        <p class="payment-text">Accept manual payment (ETH, BTC, LTC, etc) from contributors.</p>
        @if($connected)
        <div class="payment-status payment-status-connect">
            <a class="payment-status-icon" href="{{ route('admin.payments.setup.edit', $name) }}" ><em class="ti ti-plus"></em></a>
            <div class="payment-status-text">Connect your account</div>
        </div>
        @elseif($pmData->status == 'active')
        <div class="payment-status payment-status-connected">
            <span class="payment-status-icon"><em class="ti ti-check"></em></span>
            <div class="payment-status-text">Displayed on Purchase Tokens</div>
        </div>
        @else
        <div class="payment-status payment-status-disabled">
            <span class="payment-status-icon"><em class="ti ti-na"></em></span>
            <div class="payment-status-text">Currently disabled</div>
        </div>
        @endif
    </div>
    <div class="payment-footer">
        @if($connected)
        <span class="payment-not-conected">You have not connected yet.</span>
        @else
        <span class="payment-id-title">Active Currency</span>
        <span class="payment-id">{{ $active }}</span>
        @endif
    </div>
</div>
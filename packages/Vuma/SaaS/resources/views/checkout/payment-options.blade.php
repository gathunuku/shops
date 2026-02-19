{{-- saas::checkout.payment-options --}}
{{-- Include on checkout page: @include('saas::checkout.payment-options') --}}
@php
    $tenant    = app('tenant');
    $country   = $tenant->country ?? 'KE';
    $regionSvc = app(\Vuma\SaaS\Services\Regions\RegionService::class);
    $channels  = $regionSvc->defaultChannels($country);
    $currency  = $regionSvc->currency($country);
@endphp

<div id="saas-payment-options" data-country="{{ $country }}" data-currency="{{ $currency }}">

    @foreach($channels as $channel)
        @switch($channel)

            @case('mpesa_ke')
            <div class="payment-option" data-channel="mpesa_ke">
                <label class="payment-label">
                    <input type="radio" name="payment_channel" value="mpesa_ke">
                    <span>M-Pesa (Kenya)</span>
                </label>
                <div class="channel-fields" style="display:none">
                    <input type="tel" class="msisdn-input" placeholder="07XXXXXXXX" data-channel="mpesa_ke">
                    <small>You'll receive an STK push on your phone to complete payment.</small>
                </div>
            </div>
            @break

            @case('mtn_momo')
            <div class="payment-option" data-channel="mtn_momo">
                <label class="payment-label">
                    <input type="radio" name="payment_channel" value="mtn_momo">
                    <span>MTN Mobile Money</span>
                </label>
                <div class="channel-fields" style="display:none">
                    <input type="tel" class="msisdn-input" placeholder="256XXXXXXXXX" data-channel="mtn_momo">
                    <small>Approve the payment request on your MTN MoMo app.</small>
                </div>
            </div>
            @break

            @case('airtel_money')
            <div class="payment-option" data-channel="airtel_money">
                <label class="payment-label">
                    <input type="radio" name="payment_channel" value="airtel_money">
                    <span>Airtel Money</span>
                </label>
                <div class="channel-fields" style="display:none">
                    <input type="tel" class="msisdn-input" placeholder="Your Airtel number" data-channel="airtel_money">
                    <small>You'll receive a payment prompt on your Airtel Money.</small>
                </div>
            </div>
            @break

            @case('vodacom_tz')
            <div class="payment-option" data-channel="vodacom_tz">
                <label class="payment-label">
                    <input type="radio" name="payment_channel" value="vodacom_tz">
                    <span>M-Pesa (Tanzania)</span>
                </label>
                <div class="channel-fields" style="display:none">
                    <input type="tel" class="msisdn-input" placeholder="255XXXXXXXXX" data-channel="vodacom_tz">
                </div>
            </div>
            @break

            @case('paystack')
            @case('card')
            <div class="payment-option" data-channel="paystack">
                <label class="payment-label">
                    <input type="radio" name="payment_channel" value="paystack">
                    <span>Card / Bank Transfer (Paystack)</span>
                </label>
                <div class="channel-fields" style="display:none">
                    <small>You'll be redirected to Paystack's secure checkout.</small>
                </div>
            </div>
            @break

        @endswitch
    @endforeach

    <div id="payment-status" class="payment-status" style="display:none">
        <span class="spinner"></span> <span class="status-text">Processing payment...</span>
    </div>

    <button type="button" id="pay-now-btn" class="btn btn-primary">Pay {{ $currency }} <span class="order-total"></span></button>
</div>

<script src="{{ asset('js/checkout-momo.js') }}"></script>

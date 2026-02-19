<?php

namespace Vuma\SaaS\Http\Controllers\Billing;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Models\Plan;
use Vuma\SaaS\Services\Payments\PaystackSubscriptionService;
use Vuma\SaaS\Services\Regions\RegionService;

class BillingController extends Controller
{
    public function __construct(
        protected PaystackSubscriptionService $subscriptionService,
        protected RegionService $regionService
    ) {}

    /**
     * Show billing plans to the tenant.
     */
    public function plans()
    {
        $tenant = app('tenant');
        $plans  = Plan::where('is_active', true)->get();
        $subscription = $tenant->activeSubscription();

        return view('saas::billing.plans', compact('plans', 'subscription', 'tenant'));
    }

    /**
     * Initiate a Paystack subscription checkout.
     */
    public function subscribe(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $tenant = app('tenant');
        $plan   = Plan::findOrFail($request->plan_id);

        try {
            $data = $this->subscriptionService->initiate($tenant, $plan);
            return redirect($data['authorization_url']);
        } catch (\Throwable $e) {
            Log::error('Subscription initiation failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not initiate subscription. Please try again.');
        }
    }

    /**
     * Paystack redirects here after authorization.
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference', $request->query('trxref'));

        if (!$reference) {
            return redirect()->route('saas.billing.plans')->with('error', 'Invalid callback.');
        }

        // Transaction is handled async via webhook; just show a thank-you page
        return view('saas::billing.callback', ['reference' => $reference]);
    }
}

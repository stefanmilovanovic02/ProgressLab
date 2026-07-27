<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionPlansController extends Controller
{
    public const PRICES = [
        'paid' => 4.99,
        'trainer' => 14.99,
    ];

    public function index(Request $request)
    {
        return view('subscriptions.plans', [
            'currentRole' => $request->user()->role->value,
            'pendingRequest' => $request->user()->subscriptionRequests()
                ->where('status', 'pending')
                ->latest()
                ->first(),
            'prices' => self::PRICES,
        ]);
    }

    public function requestActivation(Request $request)
    {
        abort_if($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(self::PRICES))],
            'paypal_email' => ['required', 'email:rfc', 'max:255'],
            'paypal_transaction_id' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('subscription_requests', 'paypal_transaction_id'),
            ],
        ], [
            'paypal_transaction_id.regex' => 'Use only the letters, numbers, and dashes shown in your PayPal transaction ID.',
            'paypal_transaction_id.unique' => 'This PayPal transaction ID has already been submitted.',
        ]);

        $existingPending = $request->user()->subscriptionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()->withErrors([
                'activation' => 'You already have an activation request waiting for review.',
            ]);
        }

        SubscriptionRequest::query()->create([
            'user_id' => $request->user()->id,
            'plan' => $validated['plan'],
            'amount' => self::PRICES[$validated['plan']],
            'currency' => 'EUR',
            'paypal_email' => strtolower($validated['paypal_email']),
            'paypal_transaction_id' => strtoupper($validated['paypal_transaction_id']),
            'status' => 'pending',
        ]);

        return redirect()->route('plans.index')
            ->with('status', 'Payment details received. Your account will be activated after the Owner verifies the PayPal transaction.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionRequestController extends Controller
{
    public function approve(
        Request $request,
        SubscriptionRequest $subscriptionRequest,
        NotificationService $notifications
    )
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_unless($subscriptionRequest->status === 'pending', 422, 'This request was already reviewed.');

        DB::transaction(function () use ($request, $subscriptionRequest) {
            $claim = SubscriptionRequest::query()->lockForUpdate()->findOrFail($subscriptionRequest->id);
            abort_unless($claim->status === 'pending', 422);

            $role = $claim->plan === 'trainer'
                ? UserRole::Trainer
                : UserRole::Paid;
            $startsOn = today();

            Subscription::query()->create([
                'user_id' => $claim->user_id,
                'plan' => $claim->plan,
                'status' => 'active',
                'is_complimentary' => false,
                'amount_paid' => $claim->amount,
                'currency' => $claim->currency,
                'starts_on' => $startsOn,
                'ends_on' => $startsOn->copy()->addDays(29),
                'paid_at' => now(),
                'notes' => 'Verified PayPal transaction: ' . $claim->paypal_transaction_id,
            ]);

            $claim->user->forceFill(['role' => $role])->save();
            $claim->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        });

        $approved = $subscriptionRequest->fresh(['user']);
        $notifications->sendSystem(
            $approved->user,
            'subscription-approved-' . $approved->id,
            'Account activated',
            'Your ' . ($approved->plan === 'trainer' ? 'Trainer' : 'ProgressLab+') . ' access is now active.',
            route('home', [], false)
        );

        return back()->with('status', 'Payment verified and account activated for 30 days.');
    }

    public function reject(
        Request $request,
        SubscriptionRequest $subscriptionRequest,
        NotificationService $notifications
    )
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_unless($subscriptionRequest->status === 'pending', 422, 'This request was already reviewed.');

        $validated = $request->validate([
            'owner_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $subscriptionRequest, $validated) {
            $claim = SubscriptionRequest::query()->lockForUpdate()->findOrFail($subscriptionRequest->id);
            abort_unless($claim->status === 'pending', 422);
            $claim->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'owner_notes' => $validated['owner_notes'] ?? 'Payment could not be verified.',
            ])->save();
        });

        $rejected = $subscriptionRequest->fresh(['user']);
        $notifications->sendSystem(
            $rejected->user,
            'subscription-rejected-' . $rejected->id,
            'Payment verification needed',
            'Your activation request could not be verified. Check the submitted PayPal details and contact the Owner.',
            route('plans.index', [], false)
        );

        return back()->with('status', 'Activation request rejected. No role was changed.');
    }
}

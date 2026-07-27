<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    private const PLANS = [
        'paid' => 'Paid member',
        'trainer' => 'Trainer',
        'custom' => 'Custom',
    ];

    private const STATUSES = [
        'trial' => 'Trial',
        'active' => 'Active',
        'canceled' => 'Canceled',
        'expired' => 'Expired',
        'refunded' => 'Refunded',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        $subscriptions = Subscription::query()
            ->with('user:id,name,full_name,username,email')
            ->when($search !== '', fn ($query) => $query->whereHas('user', function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(array_key_exists($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->latest('starts_on')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'search' => $search,
            'status' => $status,
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.subscriptions.create', [
            'users' => $this->eligibleUsers(),
            'plans' => self::PLANS,
            'statuses' => self::STATUSES,
            'selectedUserId' => $request->integer('user_id') ?: null,
        ]);
    }

    public function store(Request $request, SubscriptionAccessService $access)
    {
        $subscription = Subscription::query()->create($this->validateSubscription($request));
        $access->syncUserAccess($subscription->user, true);

        return redirect()->route('admin.subscriptions.edit', $subscription)
            ->with('status', 'Subscription created successfully.');
    }

    public function edit(Subscription $subscription)
    {
        return view('admin.subscriptions.edit', [
            'subscription' => $subscription,
            'users' => $this->eligibleUsers(),
            'plans' => self::PLANS,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(
        Request $request,
        Subscription $subscription,
        SubscriptionAccessService $access
    )
    {
        $previousUserId = $subscription->user_id;
        $subscription->update($this->validateSubscription($request));
        $access->syncUserAccess($subscription->user, true);

        if ($previousUserId !== $subscription->user_id) {
            $previousUser = User::query()->find($previousUserId);
            if ($previousUser) {
                $access->syncUserAccess($previousUser, true);
            }
        }

        return redirect()->route('admin.subscriptions.edit', $subscription)
            ->with('status', 'Subscription updated successfully.');
    }

    public function destroy(Subscription $subscription, SubscriptionAccessService $access)
    {
        $user = $subscription->user;
        $subscription->delete();
        if ($user) {
            $access->syncUserAccess($user, true);
        }

        return redirect()->route('admin.subscriptions.index')
            ->with('status', 'Subscription removed successfully.');
    }

    private function validateSubscription(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'plan' => ['required', Rule::in(array_keys(self::PLANS))],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'is_complimentary' => ['nullable', 'boolean'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', Rule::in(['EUR'])],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_complimentary'] = (bool) ($validated['is_complimentary'] ?? false);
        if ($validated['is_complimentary']) {
            $validated['amount_paid'] = 0;
            $validated['paid_at'] = null;
        }

        return $validated;
    }

    private function eligibleUsers()
    {
        return User::query()
            ->orderByRaw('COALESCE(full_name, name)')
            ->get(['id', 'name', 'full_name', 'email']);
    }
}

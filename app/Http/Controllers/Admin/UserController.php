<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $role = (string) $request->query('role');

        $users = User::query()
            ->withCount(['experienceEvents', 'exerciseRanks'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(UserRole::tryFrom($role), fn ($query) => $query->where('role', $role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role'));
    }

    public function create(Request $request)
    {
        return view('admin.users.create', [
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);
        $role = UserRole::from($validated['role']);
        $this->ensureRoleCanBeAssigned($request->user(), $role);

        $user = new User([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'gender' => $validated['gender'] ?? null,
            'location' => $validated['location'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'password' => $validated['password'],
        ]);
        $user->forceFill(['role' => $role])->save();

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'User created successfully.');
    }

    public function show(Request $request, User $user, AdminUserStatsService $statsService)
    {
        $stats = $statsService->build($user);
        $ownerData = null;

        if ($request->user()->isOwner()) {
            $ownerData = [
                'photos' => $user->progressPhotoSets()
                    ->latest('captured_on')
                    ->limit(24)
                    ->get(),
                'subscriptions' => $user->subscriptions()
                    ->latest('starts_on')
                    ->get(),
            ];
        }

        return view('admin.users.show', compact('user', 'stats', 'ownerData'));
    }

    public function edit(Request $request, User $user)
    {
        $this->ensureCanManage($request->user(), $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManage($request->user(), $user);
        $validated = $this->validateUser($request, $user);
        $role = UserRole::from($validated['role']);
        $this->ensureRoleCanBeAssigned($request->user(), $role);

        $user->fill([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'gender' => $validated['gender'] ?? null,
            'location' => $validated['location'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->forceFill(['role' => $role])->save();

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureCanManage($request->user(), $user);

        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your active account from the admin panel.']);
        }

        $photoPaths = DB::table('progress_photo_sets')
            ->where('user_id', $user->id)
            ->get(['front_path', 'side_path', 'back_path'])
            ->flatMap(fn ($set) => [$set->front_path, $set->side_path, $set->back_path])
            ->filter()
            ->all();

        $user->delete();
        Storage::disk('local')->delete($photoPaths);

        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'location' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    private function assignableRoles(User $actor): array
    {
        $roles = [UserRole::User, UserRole::Trainer, UserRole::Paid];
        if ($actor->isOwner()) {
            $roles[] = UserRole::Admin;
        }

        return $roles;
    }

    private function ensureRoleCanBeAssigned(User $actor, UserRole $role): void
    {
        if (!in_array($role, $this->assignableRoles($actor), true)) {
            abort(403, 'You cannot assign this role.');
        }
    }

    private function ensureCanManage(User $actor, User $target): void
    {
        if ($target->isOwner()) {
            abort(403, 'Owner accounts cannot be modified from this panel.');
        }

        if (!$actor->isOwner() && $target->isAdmin()) {
            abort(403, 'Only an owner can manage administrator accounts.');
        }
    }
}

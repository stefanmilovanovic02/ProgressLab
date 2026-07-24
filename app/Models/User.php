<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'username',
        'name',
        'email',
        'password',
        'date_of_birth',
        'location',
        'avatar_path',
        'cover_path',
        'gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function metric()
    {
        return $this->hasOne(\App\Models\UserMetric::class);
    }

    public function nutritionGoal()
    {
        return $this->hasOne(\App\Models\NutritionGoal::class);
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function hasRole(UserRole|string $role): bool
    {
        $role = $role instanceof UserRole ? $role : UserRole::tryFrom(strtolower($role));

        return $role !== null && $this->role === $role;
    }

    /**
     * @param array<int, UserRole|string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return collect($roles)->contains(fn (UserRole|string $role) => $this->hasRole($role));
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(UserRole::Trainer);
    }

    public function isPaid(): bool
    {
        return $this->hasRole(UserRole::Paid);
    }

    public function isAdmin(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    public function isOwner(): bool
    {
        return $this->hasRole(UserRole::Owner);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->withTimestamps();
    }

    public function sentFriendRequests()
    {
        return $this->hasMany(\App\Models\FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(\App\Models\FriendRequest::class, 'receiver_id');
    }

    public function appNotifications()
    {
        return $this->hasMany(\App\Models\AppNotification::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(\App\Models\PushSubscription::class);
    }

    public function progressPhotoSets()
    {
        return $this->hasMany(\App\Models\ProgressPhotoSet::class);
    }

    public function experienceEvents()
    {
        return $this->hasMany(\App\Models\ExperienceEvent::class);
    }

    public function exerciseRanks()
    {
        return $this->hasMany(\App\Models\UserExerciseRank::class);
    }

    public function getAvatarUrlAttribute(): string
        {
            if (!$this->avatar_path) {
                return asset('images/default-avatar.png');
            }

            $p = $this->avatar_path;

            // full URL
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                return $p;
            }

            // your current DB format: "storage/avatars/..."
            if (str_starts_with($p, 'storage/')) {
                return asset($p); // => /storage/avatars/...
            }

            // if you ever switch to "avatars/..." later
            return Storage::url($p); // => /storage/avatars/...
        }

        public function getCoverUrlAttribute(): ?string
            {
                if (!$this->cover_path) return null;

                $p = $this->cover_path;

                if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return $p;
                if (str_starts_with($p, 'storage/')) return asset($p);

                return Storage::url($p);
            }
}

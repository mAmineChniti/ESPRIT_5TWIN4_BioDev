<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The roles that may register and maintain supply chain products.
     *
     * An admin deliberately sits outside this set: admins supervise, they do
     * not take part in the chain.
     *
     * @var list<string>
     */
    public const PROFESSIONAL_ROLES = ['producer', 'processor', 'distributor'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
        ];
    }

    /**
     * Products this user registered.
     *
     * @return HasMany<Food, $this>
     */
    public function foods(): HasMany
    {
        return $this->hasMany(Food::class, 'producer_id');
    }

    /**
     * Farms owned by this user/producer.
     *
     * @return HasMany<Farm, $this>
     */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /**
     * Meals this user logged.
     *
     * @return HasMany<Meal, $this>
     */
    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    /**
     * Supply chain steps this user recorded.
     *
     * @return HasMany<StageTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(StageTransition::class, 'actor_id');
    }

    /**
     * Greenwashing reports this user filed.
     *
     * @return HasMany<GreenwashingReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(GreenwashingReport::class);
    }

    /**
     * Reviews this user left.
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Roles allowed to manage products.
     *
     * @return list<string>
     */
    public function isProfessional(): bool
    {
        return in_array($this->role, self::PROFESSIONAL_ROLES, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The named route of the dashboard this user belongs on.
     *
     * Navigation resolves the destination from the role rather than guessing a
     * path, so a link can never point at an area the reader cannot reach.
     */
    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'producer' => 'producer.dashboard',
            'processor' => 'processor.dashboard',
            'distributor' => 'distributor.dashboard',
            default => 'consumer.dashboard',
        };
    }

    /**
     * The dashboard this user belongs on.
     *
     * Every dashboard is role-restricted, so this is only ever a valid
     * destination for a signed in user. Guests are sent to the consumer space,
     * which is public.
     */
    public function dashboardUrl(): string
    {
        return route($this->dashboardRouteName());
    }
}

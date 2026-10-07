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
        return in_array($this->role, ['producer', 'processor', 'distributor'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

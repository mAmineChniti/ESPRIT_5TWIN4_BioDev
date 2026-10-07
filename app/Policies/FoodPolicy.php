<?php

namespace App\Policies;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\User;

class FoodPolicy
{
    /**
     * Anyone signed in may browse the catalog.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Food $food): bool
    {
        return true;
    }

    /**
     * Only supply chain professionals may register products.
     *
     * Admins are deliberately excluded: they supervise, they do not supply
     * chain steps, and ProAccess keeps them off these routes.
     */
    public function create(User $user): bool
    {
        return $user->isProfessional();
    }

    /**
     * A product may be changed by the professional who registered it, or by an
     * admin moderating the catalogue.
     *
     * Ownership still requires a supply chain role, so an account whose role
     * was changed after registering a product cannot keep editing it. This
     * mirrors ProAccess exactly: the two layers grant the same set.
     */
    public function update(User $user, Food $food): bool
    {
        return $user->isAdmin()
            || ($user->isProfessional() && $food->producer_id === $user->id);
    }

    public function delete(User $user, Food $food): bool
    {
        return $this->update($user, $food);
    }

    /**
     * Record a hand-off in the chain of custody.
     *
     * Deliberately not the same rule as editing the product: a processor must
     * not rewrite the producer's nutrition data, but they must be able to sign
     * for the stage they actually perform. Admins moderate the chain outright
     * so a mistaken entry can be corrected.
     */
    public function recordTransition(User $user, Food $food, Stage $stage): bool
    {
        return $user->isAdmin() || $user->role === $stage->requiredRole();
    }
}

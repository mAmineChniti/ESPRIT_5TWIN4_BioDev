<?php

namespace App\Policies;

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
     * A product may be changed only by the professional who registered it.
     *
     * This mirrors ProAccess exactly: the policy never grants more than the
     * middleware already allows, so the two layers cannot disagree.
     */
    public function update(User $user, Food $food): bool
    {
        return $user->isProfessional() && $food->producer_id === $user->id;
    }

    public function delete(User $user, Food $food): bool
    {
        return $this->update($user, $food);
    }
}

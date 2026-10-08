<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Roles that may be assigned, keyed for a select field.
     *
     * @var list<string>
     */
    private const ROLES = ['admin', 'producer', 'processor', 'distributor', 'consumer'];

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $users = User::orderBy('role')->orderBy('name')->paginate(15)->withQueryString();

        // Counted in the database rather than by filtering the page in PHP, so
        // the totals stay correct on every page of a paginated table.
        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('back.users', [
            'users' => $users,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureAdmin($request);

        return view('back.users_edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        // Demoting yourself would strip the last way back into the admin area,
        // so the role column is not editable for your own account.
        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'You cannot change your own role.',
            ]);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Route-level middleware already restricts these actions to an admin; this
     * is the second layer, so a route accidentally moved out of the group still
     * fails closed. Guarding `update` matters most, since it can promote any
     * account to admin.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only an administrator can manage users.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\FarmStatus;
use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class FarmController extends Controller
{
    /**
     * How many farms one back-office page shows.
     */
    private const PER_PAGE = 10;

    /**
     * The rules shared by the create and update forms.
     *
     * @return array<string, mixed>
     */
    private function farmRules(): array
    {
        return [
            'agricultural_region_id' => ['required', 'exists:agricultural_regions,id'],
            'name' => ['required', 'string', 'max:255'],
            'soil_type' => ['nullable', 'string', 'max:255'],
            'producer_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'surface_hectares' => ['required', 'numeric', 'min:0'],
            'farming_type' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * An admin supervises every farm in the country; a producer sees only the
     * farms they registered.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(FarmStatus::class)],
        ]);

        /** @var FarmStatus|null $statusFilter */
        $statusFilter = isset($validated['status'])
            ? FarmStatus::from($validated['status'])
            : null;

        $farms = Farm::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($statusFilter, fn ($query, FarmStatus $status) => $query->where('status', $status))
            ->with(['region', 'user'])
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('back.farms.index', [
            'farms' => $farms,
            'statusFilter' => $statusFilter,
            'pendingCount' => $user->isAdmin() ? Farm::query()->pending()->count() : 0,
            'stats' => $this->statisticsFor($user),
        ]);
    }

    /**
     * Counts and total approved surface, scoped to whoever is asking.
     *
     * Aggregated in SQL rather than counted from the paginated page.
     *
     * @return array<string, int|float>
     */
    private function statisticsFor(User $user): array
    {
        $stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'surface' => 0];

        foreach (FarmStatus::cases() as $status) {
            $stats[$status->statKey()] = Farm::query()
                ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
                ->where('status', $status)
                ->count();
        }

        $stats['total'] = $stats['approved'] + $stats['pending'] + $stats['rejected'];
        $stats['surface'] = (float) Farm::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->where('status', FarmStatus::Approved)
            ->sum('surface_hectares');

        return $stats;
    }

    /**
     * Display pending farm validation requests for Admin.
     */
    public function requests(): View
    {
        $this->ensureAdmin(request());

        $requests = Farm::query()
            ->pending()
            ->with(['region', 'user'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('back.farms.requests', compact('requests'));
    }

    /**
     * Approve a farm validation request (Admin only).
     */
    public function approve(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureAdmin($request);

        $farm->update(['status' => FarmStatus::Approved, 'rejection_reason' => null]);

        return back()->with('success', "Farm \"{$farm->name}\" was approved.");
    }

    /**
     * Reject a farm validation request (Admin only).
     */
    public function reject(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $farm->update([
            'status' => FarmStatus::Rejected,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', "Farm \"{$farm->name}\" was rejected.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('back.farms.create', [
            'regions' => $this->regionsForForm(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->farmRules());

        $user = $request->user();

        $validated['user_id'] = $user->id;

        // A nullable field is absent from the validated array when it is not
        // submitted, so fall back to the account name.
        $validated['producer_name'] = ($validated['producer_name'] ?? null) ?: $user->name;

        // A producer's farm waits for an admin; an admin's own farm is
        // immediately in force.
        $validated['status'] = $user->isAdmin() ? FarmStatus::Approved : FarmStatus::Pending;

        Farm::create($validated);

        if ($user->isAdmin()) {
            return redirect()->route('farms.index')
                ->with('success', 'Farm created and approved successfully.');
        }

        return redirect()->route('farms.index')
            ->with('success', 'Your farm was saved and sent to an administrator for approval.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Farm $farm): View
    {
        $this->ensureOwnership($request, $farm);

        return view('back.farms.show', [
            'farm' => $farm->load(['region', 'user']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Farm $farm): View
    {
        $this->ensureOwnership($request, $farm);

        return view('back.farms.edit', [
            'farm' => $farm,
            'regions' => $this->regionsForForm(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureOwnership($request, $farm);

        $farm->update($request->validate($this->farmRules()));

        return redirect()->route('farms.index')
            ->with('success', 'Farm updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureOwnership($request, $farm);

        $farm->delete();

        return redirect()->route('farms.index')
            ->with('success', 'Farm deleted successfully.');
    }

    /**
     * The regions a farm may be attached to.
     *
     * @return Collection<int, AgriculturalRegion>
     */
    private function regionsForForm(): Collection
    {
        return AgriculturalRegion::orderBy('name')->get();
    }

    /**
     * Route-level middleware already restricts these to an admin; this is the
     * second layer, so a route accidentally moved out of the group still fails
     * closed.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only an administrator can approve a farm.');
    }

    /**
     * An admin may touch any farm; everyone else only their own.
     */
    private function ensureOwnership(Request $request, Farm $farm): void
    {
        abort_if(
            ! $request->user()?->isAdmin() && $farm->user_id !== $request->user()->id,
            403,
            'Access denied. You can only manage your own farms.',
        );
    }
}

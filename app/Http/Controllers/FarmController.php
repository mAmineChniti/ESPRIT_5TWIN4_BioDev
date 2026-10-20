<?php

namespace App\Http\Controllers;

use App\Enums\FarmStatus;
use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\User;
use App\Notifications\FarmApprovedNotification;
use App\Notifications\FarmPendingValidationNotification;
use App\Notifications\FarmRejectedNotification;
use App\Services\Agriculture\FarmAiDiagnosticService;
use App\Services\Agriculture\FarmStoryService;
use App\Services\Export\CsvSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'farming_type' => ['required', 'string', Rule::in(Farm::FARMING_TYPES)],
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

        $notifications = Farm::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->whereIn('status', [FarmStatus::Approved, FarmStatus::Rejected])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('back.farms.index', [
            'farms' => $farms,
            'statusFilter' => $statusFilter,
            'pendingCount' => $user->isAdmin() ? Farm::query()->pending()->count() : 0,
            'notifications' => $notifications,
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
        $this->ensureApprovable($farm);

        // Approving a rejected farm is reconsideration, not a re-decision:
        // the rejection_reason is cleared on purpose (pinned by
        // test_approving_clears_a_previous_rejection_reason).
        DB::transaction(function () use ($farm): void {
            $farm->update(['status' => FarmStatus::Approved, 'rejection_reason' => null]);
            $farm->user?->notify(new FarmApprovedNotification($farm));
        });

        return back()->with('success', __('Farm ":name" was approved.', ['name' => $farm->name]));
    }

    /**
     * Reject a farm validation request (Admin only).
     */
    public function reject(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureAdmin($request);
        $this->ensurePending($farm);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($farm, $validated): void {
            $farm->update([
                'status' => FarmStatus::Rejected,
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            $farm->user?->notify(new FarmRejectedNotification($farm));
        });

        return back()->with('success', __('Farm ":name" was rejected.', ['name' => $farm->name]));
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

        // The farm and the admins' notifications are one write: a failed
        // notification must not leave a farm the producer believes was never
        // submitted, or a second submission with no record of the first.
        DB::transaction(function () use ($validated, $user): void {
            $farm = Farm::create($validated);

            if (! $user->isAdmin()) {
                User::where('role', 'admin')->get()
                    ->each(fn (User $admin) => $admin->notify(new FarmPendingValidationNotification($farm)));
            }
        });

        if ($user->isAdmin()) {
            return redirect()->route('farms.index')
                ->with('success', __('Farm created and approved successfully.'));
        }

        return redirect()->route('farms.index')
            ->with('success', __('Your farm was saved and sent to an administrator for approval.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Farm $farm, FarmAiDiagnosticService $diagnosticService, FarmStoryService $storyService): View
    {
        $this->ensureOwnership($request, $farm);
        $farm->load(['region', 'user']);
        $diagnostic = $diagnosticService->analyze($farm);
        $story = $storyService->generate($farm);

        return view('back.farms.show', [
            'farm' => $farm,
            'diagnostic' => $diagnostic,
            'story' => $story,
        ]);
    }

    /**
     * Regenerate the AI agro-ecological diagnostic for a farm.
     */
    public function diagnostic(Request $request, Farm $farm, FarmAiDiagnosticService $diagnosticService): RedirectResponse
    {
        $this->ensureOwnership($request, $farm);
        $farm->load(['region', 'user']);
        $diagnosticService->analyze($farm, fresh: true);

        return back()->with('success', __('AI agro-ecological diagnostic regenerated successfully.'));
    }

    /**
     * Regenerate the AI consumer storytelling narrative for a farm.
     */
    public function story(Request $request, Farm $farm, FarmStoryService $storyService): RedirectResponse
    {
        $this->ensureOwnership($request, $farm);
        $farm->load(['region', 'user']);
        $storyService->generate($farm, fresh: true);

        return back()->with('success', __('Farm consumer storytelling regenerated successfully.'));
    }

    /**
     * Display the official certified registration certificate with QR code for PDF printing.
     *
     * A certificate is an accreditation document; it is only meaningful when the
     * farm has cleared administrative review. Pending and rejected farms are
     * blocked here so the document cannot be generated for a non-approved farm.
     */
    public function certificate(Request $request, Farm $farm): View
    {
        $this->ensureOwnership($request, $farm);
        $this->ensureCertificatable($farm);

        $farm->load(['region', 'user']);
        $verificationUrl = route('farms.verify', $farm);

        // When testing locally (localhost/127.0.0.1), use the LAN IP in the QR code
        // so real mobile devices on the same Wi-Fi can scan and open it directly.
        $host = $request->getHost();
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            $lanIp = $this->resolveLanIp();
            if ($lanIp) {
                $port = $request->getPort() ? ':'.$request->getPort() : ':8000';
                $scheme = $request->getScheme();
                $verificationUrl = preg_replace('#^https?://[^/]+#', $scheme.'://'.$lanIp.$port, $verificationUrl);
            }
        }

        $qrSvg = QrCode::format('svg')
            ->size(170)
            ->margin(2)
            ->generate($verificationUrl);

        return view('back.farms.certificate', [
            'farm' => $farm,
            'qrSvg' => $qrSvg,
            'verificationUrl' => $verificationUrl,
            'certificateRef' => $farm->certificateRef(),
        ]);
    }

    /**
     * Public verification page for checking a farm's certificate authenticity via QR code.
     */
    public function verify(Farm $farm, FarmStoryService $storyService): View
    {
        // Only an approved farm has cleared review. Pending and rejected rows
        // must not be discoverable through a QR link: the page carries an
        // authenticity claim and the producer's address.
        abort_unless($farm->isApproved(), 404);

        $farm->load(['region', 'user']);
        $story = $storyService->generate($farm);

        return view('front.farms.verify', [
            'farm' => $farm,
            'certificateRef' => $farm->certificateRef(),
            'story' => $story,
        ]);
    }

    /**
     * Export farms list to CSV (Excel compatible with UTF-8 BOM).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(FarmStatus::class)],
            'region_id' => ['nullable', 'exists:agricultural_regions,id'],
        ]);

        $statusFilter = isset($validated['status'])
            ? FarmStatus::from($validated['status'])
            : null;

        $farms = Farm::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($statusFilter, fn ($query, FarmStatus $status) => $query->where('status', $status))
            ->when(! empty($validated['region_id']), fn ($query) => $query->where('agricultural_region_id', $validated['region_id']))
            ->with(['region', 'user'])
            ->latest()
            ->get();

        $filename = 'nutritrace-farms-'.now()->format('Y-m-d').'.csv';

        return new StreamedResponse(function () use ($farms): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accents properly without encoding issues
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Certificate Ref',
                'Farm Name',
                'Producer Name',
                'Agricultural Region',
                'Region Code',
                'Farming Type',
                'Soil Type',
                'Surface (ha)',
                'Status',
                'Phone',
                'Address',
                'Created Date',
            ]);

            foreach ($farms as $farm) {
                fputcsv($handle, array_map([CsvSanitizer::class, 'cell'], [
                    $farm->id,
                    $farm->certificateRef(),
                    $farm->name,
                    $farm->producer_name ?? ($farm->user?->name ?? ''),
                    $farm->region?->name ?? '',
                    $farm->region?->code ?? '',
                    $farm->farming_type,
                    $farm->soil_type ?? ($farm->region?->soil_type ?? ''),
                    $farm->surface_hectares,
                    $farm->status->value,
                    $farm->phone ?? '',
                    $farm->address,
                    $farm->created_at?->format('Y-m-d H:i:s') ?? '',
                ]));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export farms list to official printable PDF report.
     */
    public function exportPdf(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(FarmStatus::class)],
            'region_id' => ['nullable', 'exists:agricultural_regions,id'],
        ]);

        $statusFilter = isset($validated['status'])
            ? FarmStatus::from($validated['status'])
            : null;

        $farms = Farm::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($statusFilter, fn ($query, FarmStatus $status) => $query->where('status', $status))
            ->when(! empty($validated['region_id']), fn ($query) => $query->where('agricultural_region_id', $validated['region_id']))
            ->with(['region', 'user'])
            ->latest()
            ->get();

        return view('back.farms.report-pdf', [
            'farms' => $farms,
            'statusFilter' => $statusFilter,
            'stats' => $this->statisticsFor($user),
            'generatedAt' => now(),
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Farm $farm): View
    {
        $this->ensureOwnership($request, $farm);
        $this->ensureEditable($farm);

        return view('back.farms.edit', [
            'farm' => $farm,
            'regions' => $this->regionsForForm(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        Farm $farm,
        FarmStoryService $storyService,
        FarmAiDiagnosticService $diagnosticService,
    ): RedirectResponse {
        $this->ensureOwnership($request, $farm);
        $this->ensureEditable($farm);

        $farm->update($request->validate($this->farmRules()));

        // Both reports are generated from the fields that were just edited, so
        // keeping the old cache would show a narrative the current record no
        // longer supports.
        $storyService->clearCache($farm);
        $diagnosticService->clearCache($farm);

        return redirect()->route('farms.index')
            ->with('success', __('Farm updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Farm $farm): RedirectResponse
    {
        $this->ensureOwnership($request, $farm);

        // A certificate already in circulation points at the public verify
        // page. A producer removing the farm would turn every printed QR code
        // into a 404, so only an administrator may retire an approved holding.
        abort_if(
            $farm->isApproved() && ! $request->user()->isAdmin(),
            403,
            __('Approved farms can only be removed by an administrator.'),
        );

        $farm->delete();

        return redirect()->route('farms.index')
            ->with('success', __('Farm deleted successfully.'));
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
        abort_unless($request->user()?->isAdmin(), 403, __('Only an administrator can approve a farm.'));
    }

    /**
     * An admin may touch any farm; everyone else only their own.
     */
    private function ensureOwnership(Request $request, Farm $farm): void
    {
        abort_if(
            ! $request->user()?->isAdmin() && $farm->user_id !== $request->user()->id,
            403,
            __('Access denied. You can only manage your own farms.'),
        );
    }

    /**
     * A rejected farm is closed: its record is kept as submitted rather than
     * edited, as documented on the model.
     */
    private function ensureEditable(Farm $farm): void
    {
        abort_if(
            ! $farm->canBeEdited(),
            422,
            __('This farm request was rejected and can no longer be edited.')
        );
    }

    /**
     * A rejection applies to an open request only: re-deciding would silently
     * rewrite a verdict the producer has already been shown. Approving is
     * deliberately looser — reconsidering a rejection is a pinned flow — so
     * only an already approved farm is refused here.
     */
    private function ensurePending(Farm $farm): void
    {
        abort_if(
            ! $farm->isPending(),
            422,
            __('This farm request has already been decided.')
        );
    }

    /**
     * A certificate is an accreditation document and must only be issued for a
     * farm that has cleared administrative review.
     *
     * Pending farms are still awaiting a decision; rejected farms have been
     * refused — neither may produce an official certificate.
     */
    private function ensureCertificatable(Farm $farm): void
    {
        abort_unless(
            $farm->isApproved(),
            422,
            __('A certificate can only be generated for an approved farm.')
        );
    }

    /**
     * Approving an approved farm would silently re-decide a verdict the
     * producer has already been shown. A rejected farm stays approvable:
     * that is reconsideration, pinned by
     * test_approving_clears_a_previous_rejection_reason.
     */
    private function ensureApprovable(Farm $farm): void
    {
        abort_if(
            $farm->status === FarmStatus::Approved,
            422,
            __('This farm is already approved.')
        );
    }

    /**
     * Resolves the primary local network IPv4 address for real mobile device testing over Wi-Fi.
     */
    private function resolveLanIp(): ?string
    {
        $configured = config('app.lan_ip');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        try {
            $output = shell_exec('ipconfig');

            if (! is_string($output)) {
                return null;
            }

            // ipconfig prints one adapter per blank-line-separated block, in
            // both English and French Windows. Skip virtual adapters, which
            // would otherwise hand out an address no phone can reach.
            foreach (preg_split('/\R{2,}/', $output) ?: [] as $block) {
                if (preg_match('/VMware|Virtual|Loopback|vEthernet/i', $block)) {
                    continue;
                }

                if (preg_match('/(?:IPv4 Address|Adresse IPv4)[^0-9]*([0-9.]+)/i', $block, $match)) {
                    $ip = $match[1];

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                        return $ip;
                    }
                }
            }
        } catch (\Throwable) {
            // No usable address: keep the request host rather than guessing.
        }

        return null;
    }
}

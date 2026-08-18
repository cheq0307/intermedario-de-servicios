<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Community;
use App\Models\JobVacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobVacancyController extends Controller
{
    public function index(Request $request): View
    {
        $vacancies = JobVacancy::query()
            ->with(['employer:id,name', 'community:id,name,municipality,state', 'category:id,name'])
            ->withCount('applications')
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested->where('title', 'like', '%'.$request->string('q')->trim().'%')->orWhere('description', 'like', '%'.$request->string('q')->trim().'%')))
            ->when($request->integer('community_id'), fn ($query, $id) => $query->where('community_id', $id))
            ->latest('published_at')->paginate(20)->withQueryString();
        $communities = Community::where('is_active', true)->orderBy('name')->get();

        return view('vacancies.index', compact('vacancies', 'communities'));
    }

    public function show(JobVacancy $vacancy): View
    {
        abort_unless($vacancy->isOpen() || auth()->id() === $vacancy->employer_id || auth()->user()?->hasAnyRole(['admin', 'superadmin']), 404);
        $vacancy->load(['employer:id,name,avatar_path', 'community', 'category'])->loadCount('applications');
        $alreadyApplied = auth()->check() && $vacancy->applications()->where('applicant_id', auth()->id())->exists();

        return view('vacancies.show', compact('vacancy', 'alreadyApplied'));
    }

    public function create(): View
    {
        abort_if(request()->user()->hasAnyRole(['admin', 'superadmin']) && ! request()->user()->canUseMarketplace(), 403);
        $communities = Community::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $fee = config('marketplace.job_posting_fee_amount');

        return view('vacancies.create', compact('communities', 'categories', 'fee'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasAnyRole(['admin', 'superadmin']) && ! $request->user()->canUseMarketplace(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:140'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'community_id' => ['required', Rule::exists('communities', 'id')->where('is_active', true)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('is_active', true)],
            'salary_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min', 'max:99999999'],
            'pay_period' => ['required', Rule::in(['hourly', 'daily', 'weekly', 'monthly'])],
            'work_mode' => ['required', Rule::in(['onsite', 'hybrid', 'remote'])],
            'contract_type' => ['required', Rule::in(['temporary', 'permanent', 'project', 'unspecified'])],
            'schedule' => ['nullable', 'string', 'max:160'],
            'vacancies_count' => ['required', 'integer', 'between:1,100'],
        ]);
        $vacancy = JobVacancy::create([
            ...collect($data)->except(['salary_min', 'salary_max'])->all(),
            'public_id' => (string) Str::uuid(),
            'employer_id' => $request->user()->id,
            'salary_min_amount' => $this->minor($data['salary_min'] ?? null),
            'salary_max_amount' => $this->minor($data['salary_max'] ?? null),
            'publication_fee_amount' => config('marketplace.job_posting_fee_amount'),
            'status' => 'pending_payment',
        ]);

        return redirect()->route('vacancies.show', $vacancy)->with('status', 'La vacante quedó en borrador. Confirma la tarifa para publicarla.');
    }

    public function pay(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        abort_unless($vacancy->employer_id === $request->user()->id, 403);
        abort_unless($vacancy->status === 'pending_payment', 422);
        $fakeAllowed = app()->environment(['local', 'testing']) || (app()->environment('staging') && config('marketplace.allow_fake_payments'));
        abort_unless($fakeAllowed, 422, 'La pasarela real debe estar configurada antes de cobrar una vacante.');
        DB::transaction(function () use ($vacancy): void {
            $locked = JobVacancy::lockForUpdate()->findOrFail($vacancy->id);
            abort_unless($locked->status === 'pending_payment', 422);
            $locked->update(['status' => 'published', 'payment_reference' => 'fake_job_'.Str::uuid(), 'paid_at' => now(), 'published_at' => now(), 'expires_at' => now()->addDays(config('marketplace.job_posting_days'))]);
        });

        return redirect()->route('vacancies.show', $vacancy)->with('status', 'Pago de prueba confirmado. La vacante ya está publicada.');
    }

    public function apply(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        abort_unless($vacancy->isOpen(), 422);
        abort_if($vacancy->employer_id === $request->user()->id, 422, 'No puedes postularte a tu propia vacante.');
        $data = $request->validate(['cover_letter' => ['required', 'string', 'min:20', 'max:3000']]);
        $vacancy->applications()->firstOrCreate(['applicant_id' => $request->user()->id], ['cover_letter' => $data['cover_letter'], 'status' => 'submitted']);

        return back()->with('status', 'Tu postulación fue enviada.');
    }

    public function mine(Request $request): View
    {
        $owned = JobVacancy::withCount('applications')->where('employer_id', $request->user()->id)->latest()->get();
        $applications = $request->user()->jobApplications()->with('vacancy.community')->latest()->get();

        return view('vacancies.mine', compact('owned', 'applications'));
    }

    public function close(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        abort_unless($vacancy->employer_id === $request->user()->id, 403);
        abort_unless(in_array($vacancy->status, ['published', 'pending_payment'], true), 422);
        $vacancy->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('status', 'Vacante cerrada.');
    }

    private function minor(mixed $amount): ?int
    {
        return $amount === null || $amount === '' ? null : (int) round((float) $amount * 100);
    }
}

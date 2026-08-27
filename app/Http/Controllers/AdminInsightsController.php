<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\JobVacancy;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PostPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminInsightsController extends Controller
{
    public function jobs(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending_payment', 'published', 'closed', 'expired'])],
            'community_id' => ['nullable', 'integer', Rule::exists('communities', 'id')],
        ]);
        $term = trim($filters['q'] ?? '');
        $vacancies = JobVacancy::query()
            ->with(['employer:id,name,email', 'community:id,name,municipality', 'category:id,name'])
            ->withCount('applications')
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('employer', fn (Builder $employer) => $employer->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['community_id'] ?? null, fn (Builder $query, int|string $community) => $query->where('community_id', $community))
            ->latest()
            ->paginate(24)
            ->withQueryString();
        $communities = Community::query()->orderBy('name')->get(['id', 'name', 'municipality']);

        return view('admin.jobs', compact('vacancies', 'communities', 'filters'));
    }

    public function operations(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'awaiting_payment', 'paid', 'in_progress', 'ready', 'delivered', 'completed', 'disputed', 'cancelled', 'refunded'])],
        ]);
        $term = trim($filters['q'] ?? '');
        $operations = Order::query()
            ->with(['buyer:id,name,email', 'vendor.user:id,name,email', 'jobRequest:id,title', 'payments:id,order_id,status,gross_amount,currency'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('public_id', 'like', "%{$term}%")
                ->orWhereHas('buyer', fn (Builder $buyer) => $buyer->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                ->orWhereHas('vendor.user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.operations', compact('operations', 'filters'));
    }

    public function promotions(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'pending_payment', 'payment_failed', 'active', 'expired', 'cancelled'])],
        ]);
        $term = trim($filters['q'] ?? '');
        $promotions = PostPromotion::query()
            ->with(['user:id,name,email', 'post:id,body,type', 'community:id,name'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                ->orWhereHas('post', fn (Builder $post) => $post->where('body', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.promotions', compact('promotions', 'filters'));
    }

    public function payments(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'authorized', 'paid', 'release_pending', 'released', 'refund_pending', 'refunded', 'failed', 'cancelled'])],
        ]);
        $term = trim($filters['q'] ?? '');
        $payments = Payment::query()
            ->with(['order.buyer:id,name,email', 'order.vendor.user:id,name,email'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('provider_reference', 'like', "%{$term}%")
                ->orWhereHas('order', fn (Builder $order) => $order->where('public_id', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments', compact('payments', 'filters'));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
    }
}

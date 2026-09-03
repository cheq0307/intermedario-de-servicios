<?php

namespace App\Http\Controllers;

use App\Support\LiveUpdates;
use App\ViewData\NotificationPreviewData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $administrativeOnly = $user->hasRole('superadmin') || ($user->hasRole('admin') && ! $user->canUseMarketplace());
        $notificationPreview = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $notification): NotificationPreviewData => NotificationPreviewData::from($notification));

        return response()->json([
            'unread_notifications' => $user->unreadNotifications()->count(),
            'unread_conversations' => $administrativeOnly ? 0 : $user->unreadConversationsCount(),
            'preview_html' => view('notifications._tray-items', compact('notificationPreview'))->render(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function index(Request $request): View|JsonResponse|Response
    {
        $request->validate(['live_revision' => ['nullable', 'string', 'max:64']]);
        $filter = $request->string('filter')->toString();
        if (! in_array($filter, ['administrative', 'social'], true)) {
            $filter = 'all';
        }

        $notificationsQuery = $request->user()->notifications();

        if ($filter === 'social') {
            $notificationsQuery->where('data->kind', 'like', 'social_%');
        } elseif ($filter === 'administrative') {
            $notificationsQuery->where(function ($query): void {
                $query->whereNull('data->kind')
                    ->orWhere('data->kind', 'not like', 'social_%');
            });
        }

        $notifications = $notificationsQuery->orderByDesc('id')->paginate(20)->appends($request->except('live_revision'));
        $unreadCount = $request->user()->unreadNotifications()->count();

        $revision = LiveUpdates::revision($notifications);
        if ($request->expectsJson()) {
            if (hash_equals($revision, (string) $request->query('live_revision'))) {
                return response()->noContent()->header('Cache-Control', 'no-store, private');
            }

            return response()->json([
                'html' => view('notifications._list', compact('notifications'))->render(),
                'revision' => $revision,
            ])->header('Cache-Control', 'no-store, private');
        }

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'revision'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $item */
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $routeName = $item->data['route_name'] ?? 'dashboard';
        $routeParameters = $item->data['route_parameters'] ?? [];

        if (($item->data['kind'] ?? null) === 'vendor_suspended') {
            $routeName = 'support.create';
            $routeParameters = ['category' => 'provider_suspension'];
        }

        abort_unless(is_string($routeName) && app('router')->has($routeName), 422, 'La notificación no tiene un destino válido.');

        return redirect()->route($routeName, $routeParameters);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['unread_notifications' => 0])->header('Cache-Control', 'no-store, private');
        }

        return back()->with('status', 'Todas las notificaciones quedaron marcadas como leídas.');
    }
}

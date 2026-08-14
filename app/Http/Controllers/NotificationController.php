<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);
        $unreadCount = $request->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $item */
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $routeName = $item->data['route_name'] ?? 'dashboard';
        abort_unless(is_string($routeName) && app('router')->has($routeName), 422, 'La notificación no tiene un destino válido.');

        return redirect()->route($routeName, $item->data['route_parameters'] ?? []);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Todas las notificaciones quedaron marcadas como leídas.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\NotificationProgramScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification, NotificationProgramScope $scope): RedirectResponse
    {
        $item = $scope->apply($request->user()->notifications(), $request->user())->findOrFail($notification);
        $item->markAsRead();

        return redirect()->to($item->data['url'] ?? route($request->user()->dashboardRouteName()));
    }

    public function readAll(Request $request, NotificationProgramScope $scope): RedirectResponse
    {
        $scope->apply($request->user()->unreadNotifications(), $request->user())->get()->markAsRead();

        return back()->with('success', 'Notifikasi program aktif ditandai dibaca.');
    }
}

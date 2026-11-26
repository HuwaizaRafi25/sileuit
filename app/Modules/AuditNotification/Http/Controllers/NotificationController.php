<?php

namespace App\Modules\AuditNotification\Http\Controllers;

use App\Modules\AuditNotification\Models\AlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController
{
    public function index(): View
    {
        return view('app.notifikasi', ['daftar' => AlertNotification::latest('created_at')->limit(50)->get()]);
    }

    public function markAllRead(): RedirectResponse
    {
        AlertNotification::whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return redirect('/app/notifikasi');
    }
}

<?php

namespace App\Modules\Notifications\Http\Controllers;

use Illuminate\View\View;

class NotificationController
{
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }
}
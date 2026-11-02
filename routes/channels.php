<?php

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard', static function (User $user): bool {
    return $user->hasAnyPermission([
        'worker-vehicles.view',
        'tourist-vehicles.view',
        'drivers.view',
        'receipts.view',
        'expenses.view',
        'partner-payments.view',
    ]);
});

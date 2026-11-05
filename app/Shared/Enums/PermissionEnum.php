<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PermissionEnum: string
{
    use EnumToArray;

    case USERS_VIEW = 'users.view';
    case USERS_MANAGE = 'users.manage';
    case ROLES_MANAGE = 'roles.manage';
    case PERMISSIONS_VIEW = 'permissions.view';
    case PERMISSIONS_MANAGE = 'permissions.manage';

    case CUSTOMERS_VIEW = 'customers.view';
    case CUSTOMERS_MANAGE = 'customers.manage';
    case PARTNERS_VIEW = 'partners.view';
    case PARTNERS_MANAGE = 'partners.manage';
    case DRIVERS_VIEW = 'drivers.view';
    case DRIVERS_MANAGE = 'drivers.manage';
    case ROUTES_VIEW = 'routes.view';
    case ROUTES_MANAGE = 'routes.manage';
    case EXPENSE_TYPES_VIEW = 'expense-types.view';
    case EXPENSE_TYPES_MANAGE = 'expense-types.manage';
    case VEHICLES_VIEW = 'vehicles.view';
    case VEHICLES_MANAGE = 'vehicles.manage';

    case WORKER_VEHICLES_VIEW = 'worker-vehicles.view';
    case WORKER_VEHICLES_MANAGE = 'worker-vehicles.manage';
    case TOURIST_VEHICLES_VIEW = 'tourist-vehicles.view';
    case TOURIST_VEHICLES_MANAGE = 'tourist-vehicles.manage';

    case RECEIPTS_VIEW = 'receipts.view';
    case RECEIPTS_MANAGE = 'receipts.manage';
    case EXPENSES_VIEW = 'expenses.view';
    case EXPENSES_MANAGE = 'expenses.manage';
    case PARTNER_PAYMENTS_VIEW = 'partner-payments.view';
    case PARTNER_PAYMENTS_MANAGE = 'partner-payments.manage';

    case DRIVER_ATTENDANCES_VIEW = 'driver-attendances.view';
    case DRIVER_ATTENDANCES_MANAGE = 'driver-attendances.manage';
    case DRIVER_ADVANCES_VIEW = 'driver-advances.view';
    case DRIVER_ADVANCES_MANAGE = 'driver-advances.manage';
    case PAYROLLS_VIEW = 'payrolls.view';
    case PAYROLLS_MANAGE = 'payrolls.manage';
    case ATTACHMENTS_VIEW = 'attachments.view';
    case ATTACHMENTS_MANAGE = 'attachments.manage';
}

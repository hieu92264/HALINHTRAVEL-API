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
    case VEHICLE_TYPES_VIEW = 'vehicle-types.view';
    case VEHICLE_TYPES_MANAGE = 'vehicle-types.manage';
    case VEHICLES_VIEW = 'vehicles.view';
    case VEHICLES_MANAGE = 'vehicles.manage';
    case DRIVERS_VIEW = 'drivers.view';
    case DRIVERS_MANAGE = 'drivers.manage';
    case ROUTES_VIEW = 'routes.view';
    case ROUTES_MANAGE = 'routes.manage';
    case ROUTE_RATES_VIEW = 'route-rates.view';
    case ROUTE_RATES_MANAGE = 'route-rates.manage';
    case EXPENSE_TYPES_VIEW = 'expense-types.view';
    case EXPENSE_TYPES_MANAGE = 'expense-types.manage';

    case RENTAL_REQUESTS_VIEW = 'rental-requests.view';
    case RENTAL_REQUESTS_MANAGE = 'rental-requests.manage';
    case QUOTATIONS_VIEW = 'quotations.view';
    case QUOTATIONS_MANAGE = 'quotations.manage';
    case CONTRACTS_VIEW = 'contracts.view';
    case CONTRACTS_MANAGE = 'contracts.manage';

    case TRIP_SCHEDULES_VIEW = 'trip-schedules.view';
    case TRIP_SCHEDULES_MANAGE = 'trip-schedules.manage';
    case TRIP_ASSIGNMENTS_VIEW = 'trip-assignments.view';
    case TRIP_ASSIGNMENTS_MANAGE = 'trip-assignments.manage';
    case DISPATCH_ORDERS_VIEW = 'dispatch-orders.view';
    case DISPATCH_ORDERS_MANAGE = 'dispatch-orders.manage';

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

<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum RoleEnum: string {
    use EnumToArray;

    case ADMIN = 'admin'; // quản trị viên
    case DIRECTOR = 'director'; // Ban giám đốc
    case SALES = 'sales'; // Nhân viên kinh doanh
    case DISPATCHER = 'dispatcher'; // Nhân viên điều hành
    case ACCOUNTANT = 'accountant'; // Kế toán
    case DRIVER = 'driver'; // Tài xế
}

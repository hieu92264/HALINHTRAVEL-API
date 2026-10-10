<?php

namespace App\Modules\DriverPayroll\Interfaces;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;

interface DriverPayrollServiceInterface
{
    public function advances(): array;

    public function advance(DriverAdvance $advance): array;

    public function createAdvance(array $data): array;

    public function updateAdvance(DriverAdvance $advance, array $data): array;

    public function deactivateAdvance(DriverAdvance $advance): void;

    public function confirmAdvance(DriverAdvance $advance, User $user): array;

    public function attendances(): array;

    public function attendance(DriverAttendance $attendance): array;

    public function createAttendance(DispatchOrder $order, array $data): array;

    public function updateAttendance(DriverAttendance $attendance, array $data): array;

    public function confirmAttendance(DriverAttendance $attendance): array;

    public function payrolls(): array;

    public function payroll(Payroll $payroll): array;

    public function createPayroll(array $data): array;

    public function updatePayroll(Payroll $payroll, array $data): array;

    public function calculate(Payroll $payroll): array;

    public function updatePayrollItem(Payroll $payroll, PayrollItem $item, array $data): array;

    public function approve(Payroll $payroll, User $user): array;

    public function markPaid(Payroll $payroll): array;

    public function lock(Payroll $payroll): array;
}

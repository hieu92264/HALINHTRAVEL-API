<?php

namespace App\Modules\Finance\Interfaces;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;

interface FinanceServiceInterface
{
    public function receipts(): array;

    public function receipt(Receipt $receipt): array;

    public function createReceipt(array $data): array;

    public function updateReceipt(Receipt $receipt, array $data): array;

    public function deactivateReceipt(Receipt $receipt): void;

    public function lockReceipt(Receipt $receipt): array;

    public function expenses(): array;

    public function expense(Expense $expense): array;

    public function createExpense(array $data): array;

    public function updateExpense(Expense $expense, array $data): array;

    public function deactivateExpense(Expense $expense): void;

    public function lockExpense(Expense $expense): array;

    public function partnerPayments(): array;

    public function partnerPayment(PartnerPayment $partnerPayment): array;

    public function createPartnerPayment(array $data): array;

    public function updatePartnerPayment(PartnerPayment $partnerPayment, array $data): array;

    public function deactivatePartnerPayment(PartnerPayment $partnerPayment): void;

    public function lockPartnerPayment(PartnerPayment $partnerPayment): array;
}

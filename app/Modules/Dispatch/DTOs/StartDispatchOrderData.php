<?php

namespace App\Modules\Dispatch\DTOs;

readonly class StartDispatchOrderData
{
    public function __construct(
        public string $actualStartAt,
        public int $startOdometer,
        public ?string $note,
    ) {}
}

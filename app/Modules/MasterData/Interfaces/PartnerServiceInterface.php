<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreatePartnerData;
use App\Modules\MasterData\DTOs\UpdatePartnerData;
use App\Modules\MasterData\Models\Partner;

interface PartnerServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function partners(): array;

    /** @return array<string, mixed> */
    public function partner(Partner $partner): array;

    /** @return array<string, mixed> */
    public function create(CreatePartnerData $data): array;

    /** @return array<string, mixed> */
    public function update(Partner $partner, UpdatePartnerData $data): array;

    public function deactivate(Partner $partner): void;
}

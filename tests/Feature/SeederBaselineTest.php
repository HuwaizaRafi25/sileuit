<?php

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\Role;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Partner;
use App\Modules\ProcurementReceiving\Models\PriceListItem;

it('memuat baseline sintetis DS lengkap', function () {
    $this->seed();
    expect(Location::count())->toBe(5);
    expect(Role::count())->toBe(10);
    expect(Partner::count())->toBe(4);
    expect(PriceListItem::whereNotNull('disetujui_oleh')->count())->toBe(3);
    expect(Contract::where('status', 'AKTIF')->count())->toBe(2);
    expect(HarvestPlan::where('kg_perkiraan', 1000)->exists())->toBeTrue();
});

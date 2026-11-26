<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Partner;
use App\Modules\ProcurementReceiving\Models\Receipt;
use Illuminate\Contracts\Console\Kernel;

echo "=== ALL USERS ===\n";
foreach (User::with('roles')->get() as $u) {
    $roles = $u->roles->pluck('nama')->join(', ');
    echo "User: {$u->name} (ID: {$u->id}), email: {$u->email}, hp: {$u->hp}, hp_hash: {$u->hp_hash}, roles: [{$roles}], partner_id: ".($u->partner_id ?? 'NULL')."\n";
}

echo "\n=== PARTNERS ===\n";
foreach (Partner::all() as $p) {
    echo "Partner: {$p->nama} (ID: {$p->id}), tipe: {$p->tipe}\n";
}

echo "\n=== RECEIPTS ===\n";
foreach (Receipt::with(['contract.partner', 'location'])->get() as $r) {
    echo "Receipt: {$r->no_dok}, status: {$r->status}, farmer: {$r->contract->partner->nama}, netto: {$r->netto_kg}\n";
}

<?php

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rolesToTest = [
    'pemilik-1@sileuit.id',
    'adm-1@sileuit.id',
    'pengadaan-1@sileuit.id',
    'keu-1@sileuit.id',
    'pg-g1@sileuit.id',
    'kg-g1@sileuit.id',
    'ksr-c1@sileuit.id',
    'kur-1@sileuit.id',
    'retail-contoh-satu@mitra.sileuit.id',
];

$routes = [
    '/app/beranda',
    '/app/penerimaan',
    '/app/terima',
    '/app/stok',
    '/app/transfer',
    '/app/penyesuaian',
    '/app/pesanan',
    '/app/pengiriman',
    '/app/retur',
    '/app/pos',
    '/app/invoice',
    '/app/audit',
    '/app/notifikasi',
    '/app/cari',
];

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo str_pad('ROLE / USER', 38);
foreach ($routes as $r) {
    echo str_pad(str_replace('/app/', '', $r), 13);
}
echo "\n".str_repeat('-', 220)."\n";

foreach ($rolesToTest as $email) {
    $user = User::where('email', $email)->first();
    Auth::login($user);
    echo str_pad($email, 38);
    foreach ($routes as $r) {
        $req = Request::create($r, 'GET');
        $req->setUserResolver(fn () => $user);
        try {
            $res = $kernel->handle($req);
            $code = $res->getStatusCode();
        } catch (Throwable $e) {
            $code = 'ERR:'.$e->getCode();
        }
        echo str_pad((string) $code, 13);
    }
    echo "\n";
}

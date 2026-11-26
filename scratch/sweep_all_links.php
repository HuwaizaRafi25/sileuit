<?php

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$guestRes = $kernel->handle(Request::create('/', 'GET'));
$guestHtml = $guestRes->getContent();
echo '[LANDING] HTTP '.$guestRes->getStatusCode().' | AOS: '.(str_contains($guestHtml, 'data-aos') ? 'YES' : 'NO').' | SmoothScroll: '.(str_contains($guestHtml, 'build/assets/') ? 'YES' : 'NO')."\n";

$users = [
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

$farmer = User::where('hp_hash', hash('sha256', '+628111111001'))->first();
$allUsers = collect($users)->map(fn ($e) => User::where('email', $e)->first())->push($farmer);

$seedPages = [
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
    '/portal',
    '/admin',
];

foreach ($allUsers as $user) {
    Auth::login($user);
    $label = $user->email ?? $user->name;
    $deadLinks = [];

    foreach ($seedPages as $page) {
        $req = Request::create($page, 'GET');
        $req->setUserResolver(fn () => $user);
        $res = $kernel->handle($req);
        if ($res->getStatusCode() !== 200) {
            continue;
        }
        $html = $res->getContent();
        if (preg_match_all('/href=["\'](\/(?:app|admin|portal)[^"\'#\?]*)["\']/', $html, $matches)) {
            foreach (array_unique($matches[1]) as $href) {
                $r2 = Request::create($href, 'GET');
                $r2->setUserResolver(fn () => $user);
                $res2 = $kernel->handle($r2);
                $st = $res2->getStatusCode();
                if ($st >= 400) {
                    $deadLinks[] = "{$page} -> {$href} [{$st}]";
                }
            }
        }
    }

    $deadLinks = array_unique($deadLinks);
    if (empty($deadLinks)) {
        echo "[OK] {$label}: All visible UI links return 2xx/3xx.\n";
    } else {
        echo "[FAIL] {$label}:\n";
        foreach ($deadLinks as $dl) {
            echo "   - {$dl}\n";
        }
    }
}

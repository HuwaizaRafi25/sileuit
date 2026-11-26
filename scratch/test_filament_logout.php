<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\RedirectResponse;

app()->singleton(LogoutResponse::class, function () {
    return new class implements LogoutResponse
    {
        public function toResponse($request): RedirectResponse
        {
            return redirect('/login');
        }
    };
});

$response = app(LogoutResponse::class);
echo 'Custom LogoutResponse class: '.get_class($response)."\n";
$redirect = $response->toResponse(request());
echo 'New Target URL: '.$redirect->getTargetUrl()."\n";

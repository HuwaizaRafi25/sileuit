<?php

use Illuminate\Support\Facades\Route;

// Sementara: root mengarah ke panel admin.
// SCR-01 (login lapangan & portal petani) dibangun di Fase 1.
Route::redirect('/', '/admin');

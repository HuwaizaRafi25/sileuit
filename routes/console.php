<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sileuit:cek-umur-simpan')->dailyAt('06:00');
Schedule::command('sileuit:buat-notifikasi')->dailyAt('06:05');

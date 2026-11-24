<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sileuit:cek-umur-simpan')->dailyAt('06:00');

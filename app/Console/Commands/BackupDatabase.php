<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'sileuit:backup';

    protected $description = 'Dump database MySQL ke storage/app/backups (dukung RPO ≤ 1 jam, NFR-03)';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir.'/sileuit-'.now()->format('Ymd-His').'.sql';
        $db = config('database.connections.mysql');
        $pass = (string) ($db['password'] ?? '');
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            $pass !== '' ? '--password='.escapeshellarg($pass) : '',
            escapeshellarg($db['database']),
            escapeshellarg($file)
        );
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            $this->error('Backup gagal (cek apakah mysqldump ada di PATH).');

            return self::FAILURE;
        }
        $this->info("Backup tersimpan: {$file}");

        return self::SUCCESS;
    }
}

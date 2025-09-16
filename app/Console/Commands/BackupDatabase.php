<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $filename = 'pgso_warehouse-' . now()->format('Y-m-d_H-i-s') . '.sql';

        $backupDir = 'D:\\backups';
        $backupPath = $backupDir . '\\' . $filename;

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        $command = 'cmd /c "' . $mysqldumpPath . '" --user=' . env('DB_USERNAME') .
            ' --password=' . env('DB_PASSWORD') .
            ' --host=' . env('DB_HOST') .
            ' ' . env('DB_DATABASE') .
            ' > "' . $backupPath . '"';
        
        $output = [];
        $returnVar = null;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            Log::channel('backups')->info('Inventory backup completed', [
                'action' => 'database_backup',
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'executed_at' => now()->toDateTimeString(),
                'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);
        } else {
            Log::channel('backups')->error('Backup failed', [
                'code' => $returnVar,
                'output' => $output,
                'command' => $command
            ]);
        }
    }
}

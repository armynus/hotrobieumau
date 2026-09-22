<?php

namespace App\Console\Commands;

use App\Models\Branches;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateBranchDatabases extends Command
{
    protected $signature = 'tenants:migrate-branches';

    protected $description = 'Run branch-specific migrations for all tenant databases';

    public function handle()
    {
        $branches = Branches::all();
        $failures = 0;

        foreach ($branches as $branch) {
            $dbName = $branch->database_name;

            if (! $dbName) {
                $this->warn("Branch {$branch->id} chưa có database_name");

                continue;
            }

            // Cập nhật config database tenant
            config(['database.connections.tenant.database' => $dbName]);

            $this->info("🔁 Migrating for: $dbName");

            try {
                DB::purge('tenant');
                DB::reconnect('tenant');
                $exitCode = Artisan::call('migrate', [
                    '--path' => 'database/migrations/branch',
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
                if ($exitCode !== self::SUCCESS) {
                    throw new \RuntimeException(trim(Artisan::output()) ?: 'Migration trả về mã lỗi '.$exitCode);
                }

                $this->info("✅ Migration completed for: $dbName");
            } catch (\Exception $e) {
                $failures++;
                $this->error("❌ Lỗi khi migrate database $dbName: ".$e->getMessage());
            } finally {
                DB::disconnect('tenant');
            }
        }

        $this->info('🎉 Migrate xong cho tất cả các chi nhánh.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

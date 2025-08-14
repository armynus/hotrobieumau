<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Branches;

class MigrateBranchDatabases extends Command
{
    protected $signature = 'tenants:migrate-branches';
    protected $description = 'Run branch-specific migrations for all tenant databases';

    public function handle()
    {
        $branches = Branches::all();
        
        foreach ($branches as $branch) {
            $dbName = $branch->database_name;

            if (!$dbName) {
                $this->warn("Branch {$branch->id} chưa có database_name");
                continue;
            }

            // Cập nhật config database tenant
            config(['database.connections.tenant.database' => $dbName]);

            $this->info("🔁 Migrating for: $dbName");

            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/branch',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                $this->info("✅ Migration completed for: $dbName");
            } catch (\Exception $e) {
                $this->error("❌ Lỗi khi migrate database $dbName: " . $e->getMessage());
            }
        }

        $this->info("🎉 Migrate xong cho tất cả các chi nhánh.");
        return 0;
    }
}

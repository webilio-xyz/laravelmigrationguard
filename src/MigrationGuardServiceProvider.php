<?php

namespace WebilioXyz\LaravelMigrationGuard;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Migrations\MigrateCommand;

class MigrationGuardServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Hook into the migrate command
        $this->app->extend(MigrateCommand::class, function ($command, $app) {
            return new class($command) extends MigrateCommand {
                public function handle()
                {
                    // Check if the 'migrations' table is empty but other tables exist
                    if (DB::table('migrations')->count() == 0 && $this->databaseHasTables()) {
                        // Insert a fictive migration to avoid executing schema.sql
                        DB::table('migrations')->insert([
                            'migration' => 'initial_setup_detected',
                            'batch' => 1
                        ]);
                    }

                    // Call the original migrate command
                    parent::handle();
                }

                private function databaseHasTables()
                {
                    // Check if there are tables in the database excluding 'migrations'
                    $tables = DB::select('SHOW TABLES');
                    foreach ($tables as $table) {
                        $tableName = array_values((array)$table)[0];
                        if ($tableName != 'migrations') {
                            return true;
                        }
                    }
                    return false;
                }
            };
        });
    }

    public function register()
    {
        // Register services, commands, etc. if needed
    }
}

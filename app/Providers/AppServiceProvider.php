<?php

namespace App\Providers;

use App\Models\VendorOrderFulfillment;
use App\Observers\VendorOrderFulfillmentObserver;
use App\Support\AppLog;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private static bool $loggingQuery = false;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VendorOrderFulfillment::observe(VendorOrderFulfillmentObserver::class);

        if (! config('logging.log_query_logging')) {
            return;
        }

        DB::listen(function (QueryExecuted $query) {
            if (self::$loggingQuery) {
                return;
            }

            self::$loggingQuery = true;

            try {
                AppLog::debug('[Database] Query executed.', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            } finally {
                self::$loggingQuery = false;
            }
        });
    }
}

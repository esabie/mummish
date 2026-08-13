<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Soft-deleted users created before email release still occupy the unique
     * email index. Free those addresses so the same email can register again.
     */
    public function up(): void
    {
        User::query()
            ->onlyTrashed()
            ->orderBy('id')
            ->each(function (User $user): void {
                $user->releaseEmailForReuse();
            });
    }

    public function down(): void
    {
        // Irreversible data repair.
    }
};

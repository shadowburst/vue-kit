<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table): void {
            $table->dropColumn('created_at');
        });
    }
};

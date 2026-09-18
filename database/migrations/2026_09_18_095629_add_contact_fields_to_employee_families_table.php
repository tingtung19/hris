<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('employee_families', 'nik')) {
            Schema::table('employee_families', function (Blueprint $table) {
                $table->string('nik', 20)->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('employee_families', 'phone')) {
            Schema::table('employee_families', function (Blueprint $table) {
                $table->string('phone', 30)->nullable()->after('occupation');
            });
        }

        if (! Schema::hasColumn('employee_families', 'address')) {
            Schema::table('employee_families', function (Blueprint $table) {
                $table->text('address')->nullable()->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = collect(['nik', 'phone', 'address'])
            ->filter(fn (string $column): bool => Schema::hasColumn('employee_families', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('employee_families', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};

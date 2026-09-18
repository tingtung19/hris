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
        if (! Schema::hasColumn('employees', 'nip')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nip', 50)->nullable()->unique()->after('employee_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employees', 'nip')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique(['nip']);
                $table->dropColumn('nip');
            });
        }
    }
};

<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'employeeCount' => Employee::where('employment_status', 'active')->count(),
            'pendingLeaves' => $this->countIfTableExists('leave_requests', 'status', 'pending'),
            'pendingOvertime' => $this->countIfTableExists('overtime_requests', 'status', 'pending'),
        ]);
    }

    private function countIfTableExists(string $table, string $column, string $value): int
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        return DB::table($table)->where($column, $value)->count();
    }
}

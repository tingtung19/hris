<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function dashboard()
    {
        $periods = DB::table('performance_periods')->orderByDesc('start_date')->get();
        $period = $periods->first();
        $assignments = $period ? $this->visibleAssignments($period->id)->get() : collect();

        return view('kpi.dashboard', compact('periods', 'period', 'assignments'));
    }

    public function index(Request $request)
    {
        $periods = DB::table('performance_periods')->orderByDesc('start_date')->get();
        $periodId = $request->integer('period_id') ?: $periods->first()?->id;
        $period = $periodId ? $periods->firstWhere('id', $periodId) : null;
        $assignments = $period ? $this->visibleAssignments($periodId)->paginate(20)->withQueryString() : collect();
        $employees = DB::table('employees')->whereNull('deleted_at')->where('employment_status', 'active')->orderBy('first_name')->get();
        $kpis = DB::table('kpis')->whereNull('deleted_at')->orderBy('name')->get();

        return view('kpi.index', compact('periods', 'period', 'assignments', 'employees', 'kpis'));
    }

    public function storePeriod(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'], 'type' => ['required', 'in:monthly,quarterly,semester,annual'],
            'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
        $id = DB::table('performance_periods')->insertGetId(array_merge($data, ['status' => 'open', 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'kpi_period', $id, 'Periode KPI dibuat.', null, $data, $request);

        return back()->with('status', 'Periode KPI berhasil dibuat.');
    }

    public function storeIndicator(Request $request, AuditService $audit)
    {
        $data = $request->validate(['name' => ['required', 'max:150'], 'perspective' => ['required', 'in:FIN,CUS,IBP,LGR,SHR'], 'description' => ['nullable', 'max:255'], 'department_id' => ['nullable', 'integer']]);
        $description = '['.$data['perspective'].'] '.($data['description'] ?? '');
        unset($data['perspective']);
        $id = DB::table('kpis')->insertGetId(array_merge($data, ['description' => $description, 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'kpi_indicator', $id, 'Indikator KPI dibuat.', null, $data, $request);

        return back()->with('status', 'Indikator KPI berhasil dibuat.');
    }

    public function assign(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'performance_period_id' => ['required', 'integer'], 'employee_id' => ['required', 'integer'],
            'kpi_id' => ['required', 'integer'], 'target' => ['required', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'between:0,100'],
        ]);
        abort_unless(DB::table('performance_periods')->where('id', $data['performance_period_id'])->where('status', 'open')->exists(), 422, 'Periode KPI tidak terbuka.');
        abort_unless(DB::table('employees')->where('id', $data['employee_id'])->whereNull('deleted_at')->exists(), 404);
        abort_if(DB::table('employee_kpis')->where($data)->exists(), 422, 'KPI ini sudah ditugaskan.');
        $weightTotal = DB::table('employee_kpis')->where('performance_period_id', $data['performance_period_id'])->where('employee_id', $data['employee_id'])->sum('weight');
        abort_if((float) $weightTotal + (float) $data['weight'] > 100, 422, 'Total bobot KPI tidak boleh melebihi 100%.');
        $id = DB::table('employee_kpis')->insertGetId(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        $audit->record('assigned', 'employee_kpi', $id, 'KPI ditugaskan kepada employee.', null, $data, $request);

        return back()->with('status', 'KPI berhasil ditugaskan.');
    }

    public function updateActual(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['actual' => ['required', 'numeric', 'min:0']]);
        $item = DB::table('employee_kpis as ek')->join('performance_periods as pp', 'pp.id', '=', 'ek.performance_period_id')->where('ek.id', $id)->select('ek.*', 'pp.status as period_status')->first();
        abort_unless($item, 404);
        abort_if($item->period_status !== 'open', 422, 'Periode KPI sudah ditutup.');
        abort_unless($this->canEditActual($item), 403);
        $achievement = $item->target > 0 ? min(120, (float) $data['actual'] / (float) $item->target * 100) : ((float) $data['actual'] === 0.0 ? 100 : 0);
        $score = round($achievement * (float) $item->weight / 100, 2);
        DB::table('employee_kpis')->where('id', $id)->update(['actual' => $data['actual'], 'score' => $score, 'updated_at' => now()]);
        $audit->record('actual_updated', 'employee_kpi', $id, 'Realisasi KPI diperbarui.', $item, ['actual' => $data['actual'], 'achievement' => $achievement, 'score' => $score], $request);

        return back()->with('status', 'Realisasi KPI diperbarui.');
    }

    private function visibleAssignments(int $periodId)
    {
        $query = DB::table('employee_kpis as ek')
            ->join('employees as e', 'e.id', '=', 'ek.employee_id')
            ->join('kpis as k', 'k.id', '=', 'ek.kpi_id')
            ->where('ek.performance_period_id', $periodId)
            ->whereNull('e.deleted_at')
            ->select('ek.*', 'e.employee_number', 'e.first_name', 'e.last_name', 'k.name as kpi_name', 'k.description as kpi_description');
        $user = Auth::user();
        if (! $user->hasPermission('performance.review') && ! $user->hasRole('hr-administrator')) {
            $query->where('ek.employee_id', $user->employee_id);
        }

        return $query->orderBy('e.first_name');
    }

    private function canEditActual(object $item): bool
    {
        $user = Auth::user();

        return $user->hasPermission('performance.update') || $item->employee_id === $user->employee_id;
    }
}

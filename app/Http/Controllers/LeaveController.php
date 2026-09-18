<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function index()
    {
        $employeeId = Auth::user()->employee_id;

        return view('leave.index', [
            'types' => DB::table('leave_types')->whereNull('deleted_at')->orderBy('name')->get(),
            'balances' => DB::table('leave_balances')->join('leave_types', 'leave_types.id', '=', 'leave_balances.leave_type_id')->where('employee_id', $employeeId)->where('year', now()->year)->get(),
            'requests' => DB::table('leave_requests')->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')->where('employee_id', $employeeId)->select('leave_requests.*', 'leave_types.name as leave_type_name')->orderByDesc('leave_requests.created_at')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $employeeId = Auth::user()->employee_id;
        $data = $request->validate(['leave_type_id' => ['required', 'integer'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'reason' => ['nullable', 'string', 'max:255']]);
        $leaveType = DB::table('leave_types')->where('id', $data['leave_type_id'])->whereNull('deleted_at')->first();
        abort_unless($leaveType, 422, 'Jenis cuti tidak valid.');
        $days = $this->businessDays($data['start_date'], $data['end_date']);
        abort_if($days < 1, 422, 'Rentang tanggal tidak memiliki hari kerja.');
        abort_if(DB::table('leave_requests')->where('employee_id', $employeeId)->whereIn('status', ['pending', 'approved'])->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists(), 422, 'Sudah ada pengajuan pada rentang tanggal tersebut.');
        $balance = DB::table('leave_balances')->where(['employee_id' => $employeeId, 'leave_type_id' => $leaveType->id, 'year' => now()->year])->first();
        if (! $balance) {
            DB::table('leave_balances')->insert([
                'employee_id' => $employeeId, 'leave_type_id' => $leaveType->id, 'year' => now()->year,
                'allocated_days' => $leaveType->default_days_per_year, 'used_days' => 0, 'carried_days' => 0,
                'adjustment_days' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $balance = DB::table('leave_balances')->where(['employee_id' => $employeeId, 'leave_type_id' => $leaveType->id, 'year' => now()->year])->first();
        }
        abort_if(((float) $balance->allocated_days + $balance->carried_days + $balance->adjustment_days - $balance->used_days) < $days && (int) $leaveType->is_paid === 1, 422, 'Saldo cuti tidak mencukupi.');

        $requestId = DB::table('leave_requests')->insertGetId(array_merge($data, ['employee_id' => $employeeId, 'total_days' => $days, 'status' => 'pending', 'current_step' => 1, 'created_at' => now(), 'updated_at' => now()]));
        $approvalId = app(ApprovalService::class)->start('leave', 'leave_request', $requestId, Auth::id());
        $steps = DB::table('approval_steps')->where('approval_workflow_id', DB::table('approval_requests')->where('id', $approvalId)->value('approval_workflow_id'))->orderBy('step_order')->get();
        foreach ($steps as $step) {
            DB::table('leave_approvals')->insert([
                'leave_request_id' => $requestId, 'approver_id' => Auth::id(), 'step_order' => $step->step_order,
                'step_role' => $step->approver_type === 'role' ? (string) $step->role_id : $step->approver_type,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $audit->record('created', 'leave', $requestId, 'Pengajuan cuti dibuat.', null, $data, $request);

        return back()->with('status', 'Pengajuan cuti berhasil dikirim.');
    }

    public function cancel(int $id, AuditService $audit)
    {
        DB::transaction(function () use ($id, $audit) {
            $request = DB::table('leave_requests')->where('id', $id)->where('employee_id', Auth::user()->employee_id)->whereIn('status', ['pending', 'approved'])->first();
            abort_unless($request, 404);
            DB::table('leave_requests')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            $audit->record('cancelled', 'leave', $id, 'Pengajuan cuti dibatalkan.');
            if ($request->status === 'approved') {
                DB::table('leave_balances')->where(['employee_id' => $request->employee_id, 'leave_type_id' => $request->leave_type_id, 'year' => date('Y', strtotime($request->start_date))])->decrement('used_days', $request->total_days, ['updated_at' => now()]);
            }
        });

        return back()->with('status', 'Pengajuan cuti berhasil dibatalkan.');
    }

    public function decide(Request $request, int $id, AuditService $audit)
    {
        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string', 'max:255']]);
        $requestRow = DB::table('leave_requests')->where('id', $id)->where('status', 'pending')->first();
        abort_unless($requestRow, 404);
        $approvalId = DB::table('approval_requests')
            ->where('reference_type', 'leave_request')->where('reference_id', $id)
            ->where('status', 'pending')->value('id');
        abort_unless($approvalId, 422, 'Approval request tidak ditemukan.');
        $final = app(ApprovalService::class)->decide((int) $approvalId, Auth::id(), $decision['decision'], $decision['notes'] ?? null);
        DB::table('leave_approvals')->where('leave_request_id', $id)->where('step_order', $requestRow->current_step)->update([
            'approver_id' => Auth::id(), 'status' => $decision['decision'], 'notes' => $decision['notes'] ?? null,
            'acted_at' => now(), 'updated_at' => now(),
        ]);
        if ($decision['decision'] === 'rejected' || $final) {
            DB::transaction(function () use ($id, $requestRow, $decision) {
                DB::table('leave_requests')->where('id', $id)->update(['status' => $decision['decision'], 'updated_at' => now()]);
                if ($decision['decision'] === 'approved') {
                    DB::table('leave_balances')->where([
                        'employee_id' => $requestRow->employee_id,
                        'leave_type_id' => $requestRow->leave_type_id,
                        'year' => date('Y', strtotime($requestRow->start_date)),
                    ])->increment('used_days', $requestRow->total_days, ['updated_at' => now()]);
                }
            });
        } else {
            DB::table('leave_requests')->where('id', $id)->update(['current_step' => DB::raw('current_step + 1'), 'updated_at' => now()]);
        }
        $audit->record($decision['decision'], 'leave', $id, 'Keputusan pengajuan cuti diproses.', $requestRow, $decision, $request);

        return back()->with('status', 'Keputusan pengajuan cuti berhasil disimpan.');
    }

    private function businessDays(string $start, string $end): int
    {
        $count = 0;
        for ($date = strtotime($start); $date <= strtotime($end); $date = strtotime('+1 day', $date)) {
            if ((int) date('N', $date) < 6 && ! DB::table('holidays')->whereDate('date', date('Y-m-d', $date))->exists()) {
                $count++;
            }
        }

        return $count;
    }
}

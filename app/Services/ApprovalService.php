<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalService
{
    public function __construct(private readonly AuditService $audit) {}

    public function start(string $module, string $referenceType, int $referenceId, int $userId): int
    {
        $workflow = DB::table('approval_workflows')
            ->where('module', $module)
            ->where('is_active', 1)
            ->first();
        if (! $workflow) {
            throw new RuntimeException("Approval workflow '{$module}' belum dikonfigurasi.");
        }

        $existing = DB::table('approval_requests')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('status', ['pending', 'approved'])
            ->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $requestId = (int) DB::table('approval_requests')->insertGetId([
            'approval_workflow_id' => $workflow->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'requested_by' => $userId,
            'current_step' => 1,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit->record('created', 'approval', $requestId, "Approval request {$module} dibuat.");

        return $requestId;
    }

    public function decide(int $requestId, int $userId, string $action, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($requestId, $userId, $action, $notes) {
            $request = DB::table('approval_requests')->where('id', $requestId)->lockForUpdate()->first();
            abort_unless($request && $request->status === 'pending', 404);
            $step = DB::table('approval_steps')
                ->where('approval_workflow_id', $request->approval_workflow_id)
                ->where('step_order', $request->current_step)
                ->first();
            abort_unless($step, 422, 'Tahap approval tidak ditemukan.');
            abort_unless($this->canApprove($step, $request->requested_by, $userId), 403);

            DB::table('approval_histories')->insert([
                'approval_request_id' => $requestId,
                'step_order' => $request->current_step,
                'approver_id' => $userId,
                'action' => $action,
                'notes' => $notes,
                'acted_at' => now(),
                'created_at' => now(),
            ]);
            $this->audit->record($action, 'approval', $requestId, "Approval request diproses pada step {$request->current_step}.");
            if ($action === 'rejected') {
                DB::table('approval_requests')->where('id', $requestId)->update(['status' => 'rejected', 'updated_at' => now()]);

                return false;
            }

            $hasNext = DB::table('approval_steps')
                ->where('approval_workflow_id', $request->approval_workflow_id)
                ->where('step_order', '>', $request->current_step)
                ->exists();
            DB::table('approval_requests')->where('id', $requestId)->update([
                'current_step' => $hasNext ? $request->current_step + 1 : $request->current_step,
                'status' => $hasNext ? 'pending' : 'approved',
                'updated_at' => now(),
            ]);

            return ! $hasNext;
        });
    }

    private function canApprove(object $step, int $requestedBy, int $approverId): bool
    {
        $approver = Auth::id() === $approverId ? Auth::user() : DB::table('users')->where('id', $approverId)->first();
        if (! $approver) {
            return false;
        }
        if ($approver->id === $requestedBy || ($approver instanceof User && $approver->hasRole('super-administrator'))) {
            return $approver instanceof User && $approver->hasRole('super-administrator');
        }
        if ($step->approver_type === 'role') {
            return DB::table('user_roles')->where('user_id', $approverId)->where('role_id', $step->role_id)->exists();
        }
        if (in_array($step->approver_type, ['supervisor', 'manager'], true)) {
            $employee = DB::table('users')->where('id', $requestedBy)->value('employee_id');
            $relation = DB::table('employees')->where('id', $employee)->value($step->approver_type.'_id');

            return (int) $relation === (int) $approver->employee_id;
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrModuleController extends Controller
{
    private const MODULES = [
        'onboarding' => ['table' => 'onboarding', 'title' => 'Onboarding', 'columns' => ['employee_id', 'start_date', 'status', 'progress_percent']],
        'offboarding' => ['table' => 'offboarding', 'title' => 'Offboarding', 'columns' => ['employee_id', 'resignation_date', 'last_working_date', 'reason', 'status']],
        'performance' => ['table' => 'performance_periods', 'title' => 'Performance', 'columns' => ['name', 'start_date', 'end_date', 'status']],
        'training' => ['table' => 'trainings', 'title' => 'Training', 'columns' => ['title', 'start_date', 'end_date', 'trainer_name', 'status']],
        'assets' => ['table' => 'assets', 'title' => 'Asset Management', 'columns' => ['asset_code', 'name', 'brand', 'condition_status', 'status']],
        'business-trips' => ['table' => 'business_trips', 'title' => 'Business Trip', 'columns' => ['employee_id', 'destination', 'purpose', 'start_date', 'end_date', 'budget', 'status']],
        'reimbursements' => ['table' => 'reimbursements', 'title' => 'Reimbursement', 'columns' => ['employee_id', 'reimbursement_category_id', 'amount', 'description', 'status']],
        'announcements' => ['table' => 'announcements', 'title' => 'Announcements', 'columns' => ['title', 'content', 'target_type', 'status']],
    ];

    public function index(string $module)
    {
        $config = $this->config($module);
        $query = DB::table($config['table']);
        if (in_array('deleted_at', $this->columns($config['table']), true)) {
            $query->whereNull('deleted_at');
        }

        return view('hr-modules.index', [
            'module' => $module,
            'config' => $config,
            'rows' => $query->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function store(Request $request, string $module, AuditService $audit)
    {
        $config = $this->config($module);
        $rules = collect($config['columns'])->mapWithKeys(fn ($column) => [$column => in_array($column, ['name', 'title', 'destination', 'purpose', 'asset_code'], true) ? ['required'] : ['nullable']])->all();
        $data = $request->validate($rules);
        if (in_array('created_by', $this->columns($config['table']), true)) {
            $data['created_by'] = Auth::id();
        }
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $recordId = DB::table($config['table'])->insertGetId($data);
        $audit->record('created', $module, $recordId, $config['title'].' dibuat.', null, $data, $request);
        $approvalModules = [
            'offboarding' => 'offboarding',
            'business-trips' => 'business_trip',
            'reimbursements' => 'reimbursement',
        ];
        if (isset($approvalModules[$module])) {
            app(ApprovalService::class)->start($approvalModules[$module], $config['table'], $recordId, Auth::id());
        }

        return back()->with('status', $config['title'].' berhasil ditambahkan.');
    }

    public function notifications()
    {
        return view('hr-modules.notifications', ['notifications' => DB::table('notifications')->where('user_id', Auth::id())->orderByDesc('created_at')->paginate(20)]);
    }

    public function markNotification(int $id, AuditService $audit)
    {
        $updated = DB::table('notifications')->where('id', $id)->where('user_id', Auth::id())->update(['is_read' => 1, 'read_at' => now()]);
        if ($updated) {
            $audit->record('read', 'notification', $id, 'Notifikasi ditandai telah dibaca.');
        }

        return back();
    }

    public function audit()
    {
        return view('hr-modules.audit', ['logs' => DB::table('audit_logs')->orderByDesc('created_at')->paginate(30)]);
    }

    public function settings()
    {
        return view('hr-modules.settings', ['settings' => DB::table('system_settings')->orderBy('setting_group')->orderBy('setting_key')->get()]);
    }

    public function saveSetting(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['setting_value' => ['nullable', 'string']]);
        $before = DB::table('system_settings')->where('id', $id)->first();
        abort_unless($before, 404);
        DB::table('system_settings')->where('id', $id)->update(['setting_value' => $data['setting_value'], 'updated_at' => now()]);
        $audit->record('updated', 'settings', $id, 'System setting diperbarui.', $before, $data, $request);

        return back()->with('status', 'Pengaturan berhasil disimpan.');
    }

    public function toggleOnboardingTask(Request $request, int $id, AuditService $audit)
    {
        $task = DB::table('onboarding_tasks')->where('id', $id)->first();
        abort_unless($task, 404);
        $completed = $request->boolean('completed');
        DB::transaction(function () use ($task, $completed) {
            DB::table('onboarding_tasks')->where('id', $task->id)->update([
                'is_completed' => $completed ? 1 : 0,
                'completed_at' => $completed ? now() : null,
                'completed_by' => $completed ? Auth::id() : null,
                'updated_at' => now(),
            ]);
            $total = DB::table('onboarding_tasks')->where('onboarding_id', $task->onboarding_id)->count();
            $done = DB::table('onboarding_tasks')->where('onboarding_id', $task->onboarding_id)->where('is_completed', 1)->count();
            $progress = $total > 0 ? (int) round($done / $total * 100) : 0;
            DB::table('onboarding')->where('id', $task->onboarding_id)->update([
                'progress_percent' => $progress,
                'status' => $progress === 100 ? 'completed' : 'in_progress',
                'updated_at' => now(),
            ]);
        });
        $audit->record('task_toggled', 'onboarding', $task->onboarding_id, 'Status onboarding task diperbarui.', $task, ['completed' => $completed], $request);

        return back()->with('status', 'Task onboarding diperbarui.');
    }

    public function storeOnboardingTask(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['task_name' => ['required', 'string', 'max:150'], 'sort_order' => ['nullable', 'integer', 'min:0']]);
        abort_unless(DB::table('onboarding')->where('id', $id)->exists(), 404);
        $taskId = DB::table('onboarding_tasks')->insertGetId([
            'onboarding_id' => $id, 'task_name' => $data['task_name'],
            'sort_order' => $data['sort_order'] ?? 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $audit->record('created', 'onboarding_task', $taskId, 'Onboarding task dibuat.', null, $data, $request);

        return back()->with('status', 'Task onboarding ditambahkan.');
    }

    public function storeExitInterview(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate([
            'feedback' => ['nullable', 'string'], 'reason_category' => ['nullable', 'string', 'max:100'],
            'would_recommend' => ['nullable', 'boolean'],
        ]);
        abort_unless(DB::table('offboarding')->where('id', $id)->exists(), 404);
        $interviewId = DB::table('exit_interviews')->insertGetId(array_merge($data, [
            'offboarding_id' => $id, 'conducted_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
        ]));
        $audit->record('created', 'offboarding', $interviewId, 'Exit interview dicatat.', null, $data, $request);

        return back()->with('status', 'Exit interview disimpan.');
    }

    public function storeClearanceItem(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate([
            'item_name' => ['required', 'string', 'max:150'], 'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        abort_unless(DB::table('offboarding')->where('id', $id)->exists(), 404);
        $itemId = DB::table('clearance_items')->insertGetId(array_merge($data, [
            'offboarding_id' => $id, 'created_at' => now(), 'updated_at' => now(),
        ]));
        $audit->record('created', 'offboarding', $itemId, 'Clearance item dibuat.', null, $data, $request);

        return back()->with('status', 'Clearance item ditambahkan.');
    }

    public function clearClearanceItem(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['cleared' => ['required', 'boolean'], 'notes' => ['nullable', 'string', 'max:255']]);
        $item = DB::table('clearance_items')->where('id', $id)->first();
        abort_unless($item, 404);
        DB::table('clearance_items')->where('id', $id)->update([
            'is_cleared' => $data['cleared'] ? 1 : 0,
            'cleared_by' => $data['cleared'] ? Auth::id() : null,
            'cleared_at' => $data['cleared'] ? now() : null,
            'notes' => $data['notes'] ?? $item->notes,
            'updated_at' => now(),
        ]);
        $audit->record('clearance_changed', 'offboarding', $id, 'Status clearance diperbarui.', $item, $data, $request);

        return back()->with('status', 'Status clearance diperbarui.');
    }

    public function updatePerformanceReview(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['score' => ['required', 'numeric', 'between:0,100'], 'comments' => ['nullable', 'string']]);
        $review = DB::table('performance_reviews')->where('id', $id)->first();
        abort_unless($review, 404);
        $role = Auth::user()->hasRole('hr-administrator') ? 'hr_score' : (Auth::user()->hasRole('manager') ? 'manager_score' : 'supervisor_score');
        DB::table('performance_reviews')->where('id', $id)->update([$role => $data['score'], 'final_score' => $data['score'], 'status' => $role === 'hr_score' ? 'completed' : 'hr_review', 'updated_at' => now()]);
        DB::table('performance_details')->insert(['performance_review_id' => $id, 'reviewer_role' => str_replace('_score', '', $role), 'reviewer_id' => Auth::id(), 'comments' => $data['comments'] ?? null, 'rating' => (int) round($data['score'] / 20), 'created_at' => now(), 'updated_at' => now()]);
        $audit->record('reviewed', 'performance', $id, 'Performance review diperbarui.', $review, $data, $request);

        return back()->with('status', 'Performance review diperbarui.');
    }

    public function assignEmployeeKpi(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'performance_period_id' => ['required', 'integer'], 'employee_id' => ['required', 'integer'],
            'kpi_id' => ['required', 'integer'], 'target' => ['required', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'between:0,100'],
        ]);
        abort_unless(DB::table('performance_periods')->where('id', $data['performance_period_id'])->where('status', 'open')->exists(), 422, 'Periode performance tidak terbuka.');
        abort_if(DB::table('employee_kpis')->where($data)->exists(), 422, 'KPI sudah ditugaskan.');
        $id = DB::table('employee_kpis')->insertGetId(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        $audit->record('assigned', 'performance_kpi', $id, 'KPI ditugaskan kepada employee.', null, $data, $request);

        return back()->with('status', 'KPI berhasil ditugaskan.');
    }

    public function updateEmployeeKpi(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['actual' => ['required', 'numeric', 'min:0']]);
        $kpi = DB::table('employee_kpis')->where('id', $id)->first();
        abort_unless($kpi, 404);
        $score = $kpi->target > 0 ? min(100, ((float) $data['actual'] / (float) $kpi->target) * 100) : 0;
        DB::table('employee_kpis')->where('id', $id)->update(['actual' => $data['actual'], 'score' => $score, 'updated_at' => now()]);
        $audit->record('updated', 'performance_kpi', $id, 'Actual KPI diperbarui.', $kpi, ['actual' => $data['actual'], 'score' => $score], $request);

        return back()->with('status', 'Actual KPI diperbarui.');
    }

    public function addTrainingParticipant(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['employee_id' => ['required', 'integer']]);
        $training = DB::table('trainings')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($training, 404);
        abort_if($training->status === 'cancelled' || ($training->quota !== null && DB::table('training_participants')->where('training_id', $id)->count() >= $training->quota), 422, 'Training sudah penuh atau dibatalkan.');
        abort_if(DB::table('training_participants')->where('training_id', $id)->where('employee_id', $data['employee_id'])->exists(), 422, 'Employee sudah terdaftar.');
        $participantId = DB::table('training_participants')->insertGetId(['training_id' => $id, 'employee_id' => $data['employee_id'], 'status' => 'registered', 'created_at' => now(), 'updated_at' => now()]);
        $audit->record('registered', 'training', $participantId, 'Peserta training didaftarkan.', null, $data, $request);

        return back()->with('status', 'Peserta training ditambahkan.');
    }

    public function updateTrainingParticipant(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['status' => ['required', 'in:registered,attended,absent,completed']]);
        $before = DB::table('training_participants')->where('id', $id)->first();
        abort_unless($before, 404);
        DB::table('training_participants')->where('id', $id)->update(['status' => $data['status'], 'updated_at' => now()]);
        $audit->record('status_changed', 'training', $id, 'Status peserta training diperbarui.', $before, $data, $request);

        return back()->with('status', 'Status peserta training diperbarui.');
    }

    public function recordTrainingAttendance(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['date' => ['required', 'date'], 'attended' => ['required', 'boolean']]);
        $participant = DB::table('training_participants')->where('id', $id)->first();
        abort_unless($participant, 404);
        DB::table('training_attendance')->updateOrInsert(
            ['training_participant_id' => $id, 'date' => $data['date']],
            ['attended' => $data['attended'] ? 1 : 0, 'updated_at' => now(), 'created_at' => now()]
        );
        $status = $data['attended'] ? 'attended' : 'absent';
        DB::table('training_participants')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
        $audit->record('attendance_recorded', 'training', $id, 'Kehadiran training dicatat.', $participant, $data, $request);

        return back()->with('status', 'Kehadiran training dicatat.');
    }

    public function assignAsset(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['employee_id' => ['required', 'integer'], 'condition_on_assign' => ['nullable', 'max:50'], 'notes' => ['nullable', 'max:255']]);
        $asset = DB::table('assets')->where('id', $id)->where('status', 'available')->first();
        abort_unless($asset, 422, 'Asset tidak tersedia.');
        $assignmentId = DB::table('asset_assignments')->insertGetId(array_merge($data, ['asset_id' => $id, 'assigned_date' => today(), 'created_at' => now(), 'updated_at' => now()]));
        DB::table('assets')->where('id', $id)->update(['status' => 'assigned', 'updated_at' => now()]);
        $audit->record('assigned', 'asset', $id, 'Asset ditugaskan.', $asset, $data, $request);

        return back()->with('status', 'Asset ditugaskan.');
    }

    public function returnAsset(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['condition_on_return' => ['nullable', 'max:50'], 'notes' => ['nullable', 'max:255']]);
        $assignment = DB::table('asset_assignments')->where('id', $id)->whereNull('returned_date')->first();
        abort_unless($assignment, 404);
        DB::table('asset_assignments')->where('id', $id)->update(array_merge($data, ['returned_date' => today(), 'verified_by' => Auth::id(), 'updated_at' => now()]));
        DB::table('assets')->where('id', $assignment->asset_id)->update(['status' => 'available', 'updated_at' => now()]);
        $audit->record('returned', 'asset', $assignment->asset_id, 'Asset dikembalikan.', $assignment, $data, $request);

        return back()->with('status', 'Asset dikembalikan.');
    }

    public function addAssetMaintenance(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['maintenance_date' => ['required', 'date'], 'description' => ['required', 'max:255'], 'cost' => ['required', 'numeric', 'min:0'], 'performed_by' => ['nullable', 'max:150']]);
        $asset = DB::table('assets')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($asset, 404);
        abort_if($asset->status === 'assigned', 422, 'Asset yang sedang dipinjam tidak dapat masuk maintenance.');
        $maintenanceId = DB::table('asset_maintenance')->insertGetId(array_merge($data, ['asset_id' => $id, 'created_at' => now(), 'updated_at' => now()]));
        DB::table('assets')->where('id', $id)->update(['status' => 'maintenance', 'updated_at' => now()]);
        $audit->record('maintenance_added', 'asset', $id, 'Maintenance asset dicatat.', $asset, $data, $request);

        return back()->with('status', 'Maintenance asset dicatat.');
    }

    public function completeAssetMaintenance(int $id, AuditService $audit)
    {
        $asset = DB::table('assets')->where('id', $id)->where('status', 'maintenance')->first();
        abort_unless($asset, 404);
        DB::table('assets')->where('id', $id)->update(['status' => 'available', 'updated_at' => now()]);
        $audit->record('maintenance_completed', 'asset', $id, 'Maintenance asset diselesaikan.', $asset, ['status' => 'available']);

        return back()->with('status', 'Asset tersedia kembali.');
    }

    public function addTripExpense(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['category' => ['required', 'max:100'], 'description' => ['nullable', 'max:255'], 'amount' => ['required', 'numeric', 'min:0']]);
        $trip = DB::table('business_trips')->where('id', $id)->first();
        abort_unless($trip, 404);
        abort_if(in_array($trip->status, ['rejected', 'settled'], true), 422, 'Perjalanan tidak dapat menerima expense.');
        $spent = DB::table('business_trip_expenses')->where('business_trip_id', $id)->sum('amount');
        abort_if((float) $trip->budget > 0 && (float) $spent + (float) $data['amount'] > (float) $trip->budget, 422, 'Expense melebihi budget perjalanan.');
        $expenseId = DB::table('business_trip_expenses')->insertGetId(array_merge($data, ['business_trip_id' => $id, 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'business_trip', $expenseId, 'Expense business trip dicatat.', null, $data, $request);

        return back()->with('status', 'Expense perjalanan ditambahkan.');
    }

    public function settleBusinessTrip(int $id, AuditService $audit)
    {
        $trip = DB::table('business_trips')->where('id', $id)->whereIn('status', ['approved', 'completed'])->first();
        abort_unless($trip, 422, 'Perjalanan harus approved atau completed sebelum settlement.');
        DB::table('business_trips')->where('id', $id)->update(['status' => 'settled', 'updated_at' => now()]);
        $audit->record('settled', 'business_trip', $id, 'Business trip diselesaikan.', $trip, ['status' => 'settled']);

        return back()->with('status', 'Business trip berhasil diselesaikan.');
    }

    public function payReimbursement(Request $request, int $id, AuditService $audit)
    {
        $reimbursement = DB::table('reimbursements')->where('id', $id)->whereIn('status', ['finance_verified', 'manager_approved'])->first();
        abort_unless($reimbursement, 422, 'Reimbursement belum siap dibayar.');
        DB::table('reimbursements')->where('id', $id)->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);
        $audit->record('paid', 'reimbursement', $id, 'Reimbursement dibayar.', $reimbursement, ['status' => 'paid'], $request);

        return back()->with('status', 'Reimbursement ditandai sudah dibayar.');
    }

    public function addAnnouncementTarget(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['target_type' => ['required', 'in:department,branch,role,employee'], 'target_id' => ['required', 'integer']]);
        $announcement = DB::table('announcements')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($announcement, 404);
        abort_if($announcement->status === 'published', 422, 'Target tidak dapat diubah setelah publish.');
        $targetId = DB::table('announcement_targets')->insertGetId(array_merge($data, ['announcement_id' => $id, 'created_at' => now()]));
        $audit->record('target_added', 'announcement', $id, 'Target announcement ditambahkan.', null, $data, $request);

        return back()->with('status', 'Target announcement ditambahkan.');
    }

    public function markAnnouncementRead(int $id, AuditService $audit)
    {
        $employeeId = Auth::user()->employee_id;
        abort_unless($employeeId, 403);
        $announcement = DB::table('announcements')->where('id', $id)->where('status', 'published')->first();
        abort_unless($announcement, 404);
        DB::table('announcement_reads')->updateOrInsert(['announcement_id' => $id, 'employee_id' => $employeeId], ['read_at' => now()]);
        $audit->record('read', 'announcement', $id, 'Announcement dibaca.', null, ['employee_id' => $employeeId]);

        return back()->with('status', 'Announcement ditandai sudah dibaca.');
    }

    public function publishAnnouncement(int $id, AuditService $audit)
    {
        $announcement = DB::table('announcements')->where('id', $id)->first();
        abort_unless($announcement, 404);
        DB::table('announcements')->where('id', $id)->update(['status' => 'published', 'publish_at' => $announcement->publish_at ?: now(), 'updated_at' => now()]);
        $audit->record('published', 'announcement', $id, 'Announcement dipublikasikan.', $announcement, ['status' => 'published']);

        return back()->with('status', 'Announcement dipublikasikan.');
    }

    private function config(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }

    private function columns(string $table): array
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }
}

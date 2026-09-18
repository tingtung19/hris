<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HrModuleController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(Auth::check() ? '/dashboard' : '/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::middleware('permission:employee.create')->group(function () {
        Route::resource('employees', EmployeeController::class)->only(['create', 'store']);
    });
    Route::middleware('permission:employee.view')->group(function () {
        Route::resource('employees', EmployeeController::class)->only(['index', 'show']);
        Route::get('/employees/{employee}/documents/{document}', [EmployeeController::class, 'downloadDocument'])->name('employees.documents.download');
    });
    Route::middleware('permission:employee.update')->group(function () {
        Route::resource('employees', EmployeeController::class)->only(['edit', 'update']);
    });
    Route::middleware('permission:employee.delete')->delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('/attendance/today', [AttendanceController::class, 'today'])->name('attendance.today');
        Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');
        Route::get('/attendance/corrections', [AttendanceController::class, 'corrections'])->name('attendance.corrections');
    });
    Route::middleware('permission:attendance.create')->group(function () {
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::post('/attendance/corrections', [AttendanceController::class, 'requestCorrection'])->name('attendance.corrections.store');
    });
    Route::middleware('permission:attendance.approve')->post('/attendance/corrections/{id}/decide', [AttendanceController::class, 'decideCorrection'])->name('attendance.corrections.decide');
    Route::middleware('permission:leave.view')->get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::middleware('permission:leave.create')->group(function () {
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/{id}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    });
    Route::middleware('permission:leave.approve')->post('/leave/{id}/decide', [LeaveController::class, 'decide'])->name('leave.decide');
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/periods/{id}', [PayrollController::class, 'show'])->name('payroll.periods.show');
        Route::get('/payroll/payslip/{id}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::get('/payroll/payslip/{id}/pdf', [PayrollController::class, 'payslipPdf'])->name('payroll.payslip.pdf');
    });
    Route::middleware('permission:payroll.create')->post('/payroll/periods', [PayrollController::class, 'storePeriod'])->name('payroll.periods.store');
    Route::middleware('permission:payroll.generate')->post('/payroll/periods/{id}/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::middleware('permission:payroll.approve')->post('/payroll/periods/{id}/{status}', [PayrollController::class, 'transition'])->name('payroll.transition');
    Route::middleware('permission:recruitment.view')->group(function () {
        Route::get('/recruitment/vacancies', [RecruitmentController::class, 'vacancies'])->name('recruitment.vacancies');
        Route::get('/recruitment/vacancies/{id}/pipeline', [RecruitmentController::class, 'pipeline'])->name('recruitment.pipeline');
        Route::get('/recruitment/candidates/{id}', [RecruitmentController::class, 'candidate'])->name('recruitment.candidates.show');
    });
    Route::middleware('permission:recruitment.create')->group(function () {
        Route::post('/recruitment/vacancies', [RecruitmentController::class, 'storeVacancy'])->name('recruitment.vacancies.store');
        Route::post('/recruitment/vacancies/{id}/candidates', [RecruitmentController::class, 'storeCandidate'])->name('recruitment.candidates.store');
        Route::post('/recruitment/candidates/{id}/interviews', [RecruitmentController::class, 'storeInterview'])->name('recruitment.candidates.interviews.store');
        Route::post('/recruitment/candidates/{id}/assessments', [RecruitmentController::class, 'storeAssessment'])->name('recruitment.candidates.assessments.store');
    });
    Route::middleware('permission:recruitment.update')->put('/recruitment/vacancies/{id}', [RecruitmentController::class, 'updateVacancy'])->name('recruitment.vacancies.update');
    Route::middleware('permission:recruitment.delete')->delete('/recruitment/vacancies/{id}', [RecruitmentController::class, 'destroyVacancy'])->name('recruitment.vacancies.destroy');
    Route::middleware('permission:recruitment.update')->post('/recruitment/candidates/{id}/stage', [RecruitmentController::class, 'updateStage'])->name('recruitment.candidates.stage');
    Route::middleware('permission:recruitment.update')->post('/recruitment/interviews/{id}/decide', [RecruitmentController::class, 'decideInterview'])->name('recruitment.interviews.decide');
    Route::middleware('permission:recruitment.update')->post('/recruitment/candidates/{id}/hire', [RecruitmentController::class, 'hire'])->name('recruitment.candidates.hire');
    Route::middleware('permission:performance.view')->prefix('kpi')->name('kpi.')->group(function () {
        Route::get('/', [KpiController::class, 'dashboard'])->name('dashboard');
        Route::get('/manage', [KpiController::class, 'index'])->name('index');
        Route::patch('/actual/{id}', [KpiController::class, 'updateActual'])->name('actual.update');
    });
    Route::middleware('permission:performance.create')->group(function () {
        Route::post('/kpi/periods', [KpiController::class, 'storePeriod'])->name('kpi.periods.store');
        Route::post('/kpi/indicators', [KpiController::class, 'storeIndicator'])->name('kpi.indicators.store');
        Route::post('/kpi/assignments', [KpiController::class, 'assign'])->name('kpi.assignments.store');
    });
    foreach ([
        'onboarding' => 'onboarding', 'offboarding' => 'offboarding', 'performance' => 'performance',
        'training' => 'training', 'assets' => 'asset', 'business-trips' => 'business_trip',
        'reimbursements' => 'reimbursement', 'announcements' => 'announcement',
    ] as $module => $permissionModule) {
        Route::middleware('permission:'.$permissionModule.'.view')->get('/'.$module, [HrModuleController::class, 'index'])->defaults('module', $module)->name($module.'.index');
        Route::middleware('permission:'.$permissionModule.'.create')->post('/'.$module, [HrModuleController::class, 'store'])->defaults('module', $module)->name($module.'.store');
    }
    Route::middleware('permission:onboarding.create')->post('/onboarding/{id}/tasks', [HrModuleController::class, 'storeOnboardingTask'])->name('onboarding.tasks.store');
    Route::middleware('permission:onboarding.update')->patch('/onboarding/tasks/{id}', [HrModuleController::class, 'toggleOnboardingTask'])->name('onboarding.tasks.toggle');
    Route::middleware('permission:offboarding.create')->post('/offboarding/{id}/exit-interview', [HrModuleController::class, 'storeExitInterview'])->name('offboarding.exit-interview.store');
    Route::middleware('permission:offboarding.create')->post('/offboarding/{id}/clearance', [HrModuleController::class, 'storeClearanceItem'])->name('offboarding.clearance.store');
    Route::middleware('permission:offboarding.update')->patch('/offboarding/clearance/{id}', [HrModuleController::class, 'clearClearanceItem'])->name('offboarding.clearance.update');
    Route::middleware('permission:performance.review')->patch('/performance/reviews/{id}', [HrModuleController::class, 'updatePerformanceReview'])->name('performance.reviews.update');
    Route::middleware('permission:performance.create')->post('/performance/kpis', [HrModuleController::class, 'assignEmployeeKpi'])->name('performance.kpis.store');
    Route::middleware('permission:performance.update')->patch('/performance/kpis/{id}', [HrModuleController::class, 'updateEmployeeKpi'])->name('performance.kpis.update');
    Route::middleware('permission:training.create')->post('/training/{id}/participants', [HrModuleController::class, 'addTrainingParticipant'])->name('training.participants.store');
    Route::middleware('permission:training.update')->patch('/training/participants/{id}', [HrModuleController::class, 'updateTrainingParticipant'])->name('training.participants.update');
    Route::middleware('permission:training.update')->post('/training/participants/{id}/attendance', [HrModuleController::class, 'recordTrainingAttendance'])->name('training.participants.attendance');
    Route::middleware('permission:asset.update')->post('/assets/{id}/assign', [HrModuleController::class, 'assignAsset'])->name('assets.assign');
    Route::middleware('permission:asset.update')->patch('/assets/assignments/{id}/return', [HrModuleController::class, 'returnAsset'])->name('assets.return');
    Route::middleware('permission:asset.update')->post('/assets/{id}/maintenance', [HrModuleController::class, 'addAssetMaintenance'])->name('assets.maintenance.store');
    Route::middleware('permission:asset.update')->patch('/assets/{id}/maintenance/complete', [HrModuleController::class, 'completeAssetMaintenance'])->name('assets.maintenance.complete');
    Route::middleware('permission:business_trip.update')->post('/business-trips/{id}/expenses', [HrModuleController::class, 'addTripExpense'])->name('business-trips.expenses.store');
    Route::middleware('permission:business_trip.approve')->patch('/business-trips/{id}/settle', [HrModuleController::class, 'settleBusinessTrip'])->name('business-trips.settle');
    Route::middleware('permission:reimbursement.approve')->patch('/reimbursements/{id}/pay', [HrModuleController::class, 'payReimbursement'])->name('reimbursements.pay');
    Route::middleware('permission:announcement.update')->patch('/announcements/{id}/publish', [HrModuleController::class, 'publishAnnouncement'])->name('announcements.publish');
    Route::middleware('permission:announcement.update')->post('/announcements/{id}/targets', [HrModuleController::class, 'addAnnouncementTarget'])->name('announcements.targets.store');
    Route::middleware('permission:announcement.view')->post('/announcements/{id}/read', [HrModuleController::class, 'markAnnouncementRead'])->name('announcements.read');
    Route::middleware('permission:announcement.view')->get('/notifications', [HrModuleController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [HrModuleController::class, 'markNotification'])->name('notifications.read');
    Route::middleware('permission:audit.view')->get('/audit-logs', [HrModuleController::class, 'audit'])->name('audit.index');
    Route::middleware('permission:system.manage')->group(function () {
        Route::get('/settings', [HrModuleController::class, 'settings'])->name('settings.index');
        Route::put('/settings/{id}', [HrModuleController::class, 'saveSetting'])->name('settings.update');
    });
    Route::middleware('permission:report.view')->get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::middleware('permission:report.export')->get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::middleware('permission:organization.view')->group(function () {
        Route::get('/organization/chart', [OrganizationController::class, 'chart'])->name('organization.chart');
        Route::get('/organization/{slug}', [OrganizationController::class, 'index'])->name('organization.index');
    });
    Route::middleware('permission:organization.create')->post('/organization/{slug}', [OrganizationController::class, 'store'])->name('organization.store');
    Route::middleware('permission:organization.update')->put('/organization/{slug}/{id}', [OrganizationController::class, 'update'])->name('organization.update');
    Route::middleware('permission:organization.delete')->delete('/organization/{slug}/{id}', [OrganizationController::class, 'destroy'])->name('organization.destroy');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

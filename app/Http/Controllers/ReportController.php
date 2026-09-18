<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'employees' => DB::table('employees')->whereNull('deleted_at')->count(),
            'activeEmployees' => DB::table('employees')->whereNull('deleted_at')->where('employment_status', 'active')->count(),
            'openVacancies' => DB::table('vacancies')->whereNull('deleted_at')->where('status', 'open')->count(),
            'pendingLeave' => DB::table('leave_requests')->where('status', 'pending')->count(),
            'pendingReimbursement' => DB::table('reimbursements')->where('status', 'pending')->count(),
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->validate(['type' => ['required', 'in:employees,attendance,leave,payroll,recruitment'], 'format' => ['required', 'in:csv,xlsx,pdf']]);
        $rows = $this->rows($type['type'], $request);
        $headers = $rows->isEmpty() ? [] : array_keys((array) $rows->first());
        if ($type['format'] === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, (array) $row);
                }
                fclose($handle);
            }, $type['type'].'-report.csv', ['Content-Type' => 'text/csv']);
        }
        if ($type['format'] === 'pdf') {
            $dompdf = new Dompdf;
            $dompdf->loadHtml(view('reports.export', ['title' => ucfirst($type['type']).' Report', 'headers' => $headers, 'rows' => $rows])->render());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$type['type'].'-report.pdf"']);
        }
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($headers as $column => $header) {
            $sheet->setCellValueByColumnAndRow($column + 1, 1, $header);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $column => $header) {
                $sheet->setCellValueByColumnAndRow($column + 1, $rowIndex + 2, (array) $row[$header]);
            }
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $type['type'].'-report.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function rows(string $type, Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        return match ($type) {
            'employees' => DB::table('employees')->whereNull('deleted_at')->select('employee_number', 'first_name', 'last_name', 'employment_status', 'employment_type', 'join_date')->orderBy('employee_number')->get(),
            'attendance' => DB::table('attendances')->join('employees', 'employees.id', '=', 'attendances.employee_id')->when($from, fn ($q) => $q->whereDate('attendances.date', '>=', $from))->when($to, fn ($q) => $q->whereDate('attendances.date', '<=', $to))->select('employees.employee_number', 'employees.first_name', 'employees.last_name', 'attendances.date', 'attendances.clock_in', 'attendances.clock_out', 'attendances.work_minutes', 'attendances.status')->orderBy('attendances.date')->get(),
            'leave' => DB::table('leave_requests')->join('employees', 'employees.id', '=', 'leave_requests.employee_id')->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')->when($from, fn ($q) => $q->whereDate('leave_requests.start_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('leave_requests.end_date', '<=', $to))->select('employees.employee_number', 'employees.first_name', 'employees.last_name', 'leave_types.name as leave_type', 'leave_requests.start_date', 'leave_requests.end_date', 'leave_requests.total_days', 'leave_requests.status')->orderBy('leave_requests.start_date')->get(),
            'payroll' => DB::table('payrolls')->join('employees', 'employees.id', '=', 'payrolls.employee_id')->join('payroll_periods', 'payroll_periods.id', '=', 'payrolls.payroll_period_id')->when($from, fn ($q) => $q->whereDate('payroll_periods.start_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('payroll_periods.end_date', '<=', $to))->select('employees.employee_number', 'employees.first_name', 'employees.last_name', 'payroll_periods.name as period', 'payrolls.gross_salary', 'payrolls.total_deduction', 'payrolls.net_salary', 'payrolls.status')->orderBy('payroll_periods.start_date')->get(),
            'recruitment' => DB::table('candidates')->join('vacancies', 'vacancies.id', '=', 'candidates.vacancy_id')->whereNull('candidates.deleted_at')->select('vacancies.title as vacancy', 'candidates.full_name', 'candidates.email', 'candidates.stage', 'candidates.source', 'candidates.created_at')->orderByDesc('candidates.created_at')->get(),
        };
    }
}

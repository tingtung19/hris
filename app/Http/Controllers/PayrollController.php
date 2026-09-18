<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index()
    {
        return view('payroll.index', ['periods' => DB::table('payroll_periods')->orderByDesc('start_date')->paginate(15)]);
    }

    public function storePeriod(Request $request, AuditService $audit)
    {
        $data = $request->validate(['name' => ['required', 'max:100'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'payment_date' => ['nullable', 'date']]);
        $id = DB::table('payroll_periods')->insertGetId(array_merge($data, ['status' => 'draft', 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'payroll', $id, 'Periode payroll dibuat.', null, $data, $request);

        return back()->with('status', 'Periode payroll berhasil dibuat.');
    }

    public function show(int $id)
    {
        $period = DB::table('payroll_periods')->where('id', $id)->first();
        abort_unless($period, 404);

        return view('payroll.period', ['period' => $period, 'payrolls' => DB::table('payrolls')->join('employees', 'employees.id', '=', 'payrolls.employee_id')->where('payroll_period_id', $id)->select('payrolls.*', 'employees.first_name', 'employees.last_name', 'employees.employee_number')->get()]);
    }

    public function generate(int $id, AuditService $audit)
    {
        $period = DB::table('payroll_periods')->where('id', $id)->where('status', 'draft')->first();
        abort_unless($period, 422, 'Periode harus berstatus draft.');
        $employees = DB::table('employees')->whereNull('deleted_at')->whereIn('employment_status', ['active', 'probation'])->get();
        DB::transaction(function () use ($employees, $period, $id) {
            foreach ($employees as $employee) {
                $salary = DB::table('employee_salaries')->where('employee_id', $employee->id)->where('is_active', 1)->whereDate('effective_date', '<=', $period->end_date)->orderByDesc('effective_date')->first();
                if (! $salary) {
                    continue;
                }
                $payrollId = DB::table('payrolls')->where(['payroll_period_id' => $id, 'employee_id' => $employee->id])->value('id');
                if ($payrollId) {
                    DB::table('payroll_details')->where('payroll_id', $payrollId)->delete();
                }
                $components = DB::table('employee_salary_components')
                    ->join('salary_components', 'salary_components.id', '=', 'employee_salary_components.salary_component_id')
                    ->where('employee_salary_id', $salary->id)->get();
                $income = (float) $salary->basic_salary;
                $deduction = 0.0;
                $details = [[
                    'salary_component_id' => DB::table('salary_components')->where('code', 'BASIC')->value('id'),
                    'component_name' => 'Gaji Pokok',
                    'type' => 'income',
                    'amount' => $income,
                ]];
                foreach ($components as $component) {
                    $amount = $this->componentAmount($component, $income);
                    if ($component->type === 'income') {
                        $income += $amount;
                    } else {
                        $deduction += $amount;
                    }
                    $details[] = [
                        'salary_component_id' => $component->salary_component_id,
                        'component_name' => $component->name,
                        'type' => $component->type,
                        'amount' => $amount,
                    ];
                }
                $overtime = DB::table('overtime_requests')->where('employee_id', $employee->id)->where('status', 'approved')->whereBetween('date', [$period->start_date, $period->end_date])->sum('amount');
                if ($overtime > 0) {
                    $income += (float) $overtime;
                    $details[] = ['salary_component_id' => DB::table('salary_components')->where('code', 'OVERTIME')->value('id'), 'component_name' => 'Lembur', 'type' => 'income', 'amount' => $overtime];
                }
                $workingDays = $this->workingDays($period->start_date, $period->end_date);
                $absentDays = DB::table('attendances')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$period->start_date, $period->end_date])
                    ->where('status', 'absent')
                    ->count();
                $absenceDeduction = $workingDays > 0 ? round((float) $salary->basic_salary / $workingDays * $absentDays, 2) : 0;
                if ($absenceDeduction > 0) {
                    $deduction += $absenceDeduction;
                    $details[] = ['salary_component_id' => DB::table('salary_components')->where('code', 'ABSENCE')->value('id'), 'component_name' => 'Potongan Absensi', 'type' => 'deduction', 'amount' => $absenceDeduction];
                }
                $bpjsBase = $this->setting('bpjs_calculation_base', 'basic') === 'gross' ? $income : (float) $salary->basic_salary;
                foreach ([
                    ['bpjs_health_employee_percent', 'BPJS Kesehatan', 'BPJS_HEALTH'],
                    ['bpjs_employment_employee_percent', 'BPJS Ketenagakerjaan', 'BPJS_EMP'],
                ] as [$setting, $label, $code]) {
                    $amount = round($bpjsBase * ((float) $this->setting($setting, '0') / 100), 2);
                    if ($amount > 0) {
                        $deduction += $amount;
                        $details[] = ['salary_component_id' => DB::table('salary_components')->where('code', $code)->value('id'), 'component_name' => $label, 'type' => 'deduction', 'amount' => $amount];
                    }
                }
                $taxableIncome = collect($details)->where('type', 'income')->sum(function ($detail) {
                    $component = $detail['salary_component_id'] ? DB::table('salary_components')->where('id', $detail['salary_component_id'])->first() : null;

                    return ! $component || (int) $component->is_taxable === 1 ? (float) $detail['amount'] : 0;
                });
                $pph21 = $this->calculatePph21($taxableIncome, $employee->ptkp_status ?: $this->setting('ptkp_default_status', 'TK/0'));
                if ($pph21 > 0) {
                    $deduction += $pph21;
                    $details[] = ['salary_component_id' => DB::table('salary_components')->where('code', 'PPH21')->value('id'), 'component_name' => 'PPh 21', 'type' => 'deduction', 'amount' => $pph21];
                }
                $pendingDeductions = DB::table('payroll_deductions')->where('employee_id', $employee->id)->whereNull('payroll_period_id')->where('status', 'pending')->get();
                foreach ($pendingDeductions as $item) {
                    $deduction += (float) $item->amount;
                    $details[] = ['salary_component_id' => null, 'component_name' => $item->description, 'type' => 'deduction', 'amount' => $item->amount];
                }
                DB::table('payrolls')->updateOrInsert(
                    ['payroll_period_id' => $id, 'employee_id' => $employee->id],
                    ['basic_salary' => $salary->basic_salary, 'total_income' => $income, 'gross_salary' => $income, 'total_deduction' => $deduction, 'net_salary' => $income - $deduction, 'total_overtime_amount' => $overtime, 'status' => 'review', 'created_at' => now(), 'updated_at' => now()]
                );
                $payrollId ??= DB::table('payrolls')->where(['payroll_period_id' => $id, 'employee_id' => $employee->id])->value('id');
                foreach ($details as $detail) {
                    DB::table('payroll_details')->insert(array_merge($detail, [
                        'payroll_id' => $payrollId, 'created_at' => now(), 'updated_at' => now(),
                    ]));
                }
                DB::table('payroll_deductions')->whereIn('id', $pendingDeductions->pluck('id'))->update([
                    'payroll_period_id' => $id, 'status' => 'processed', 'updated_at' => now(),
                ]);
            }
        });
        DB::table('payroll_periods')->where('id', $id)->update(['status' => 'review', 'updated_at' => now()]);
        $audit->record('generated', 'payroll', $id, 'Payroll periode digenerate.');

        return back()->with('status', 'Payroll berhasil digenerate.');
    }

    public function transition(int $id, string $status, AuditService $audit)
    {
        $allowed = ['approved' => 'review', 'paid' => 'approved', 'locked' => 'paid'];
        abort_unless(isset($allowed[$status]), 404);
        $period = DB::table('payroll_periods')->where('id', $id)->where('status', $allowed[$status])->first();
        abort_unless($period, 422, 'Status periode tidak sesuai.');
        DB::transaction(function () use ($id, $status) {
            DB::table('payroll_periods')->where('id', $id)->update(['status' => $status, 'approved_by' => $status === 'approved' ? Auth::id() : DB::raw('approved_by'), 'approved_at' => $status === 'approved' ? now() : DB::raw('approved_at'), 'updated_at' => now()]);
            DB::table('payrolls')->where('payroll_period_id', $id)->update(['status' => $status, 'updated_at' => now()]);
            if ($status === 'paid') {
                $payrolls = DB::table('payrolls')->where('payroll_period_id', $id)->get();
                foreach ($payrolls as $payroll) {
                    DB::table('payslips')->updateOrInsert(
                        ['payroll_id' => $payroll->id],
                        [
                            'payslip_number' => 'PS-'.$id.'-'.str_pad((string) $payroll->employee_id, 5, '0', STR_PAD_LEFT),
                            'generated_at' => now(),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });
        $audit->record('status_changed', 'payroll', $id, "Status payroll diubah menjadi {$status}.");

        return back()->with('status', 'Status payroll diperbarui.');
    }

    public function payslip(int $id)
    {
        $payroll = DB::table('payrolls')->join('employees', 'employees.id', '=', 'payrolls.employee_id')->join('payroll_periods', 'payroll_periods.id', '=', 'payrolls.payroll_period_id')->where('payrolls.id', $id)->select('payrolls.*', 'employees.first_name', 'employees.last_name', 'employees.employee_number', 'payroll_periods.name as period_name')->first();
        abort_unless($payroll && in_array($payroll->status, ['paid', 'locked'], true), 404);
        abort_unless(Auth::user()->employee_id === $payroll->employee_id || Auth::user()->hasRole('super-administrator'), 403);

        return view('payroll.payslip', [
            'payroll' => $payroll,
            'details' => DB::table('payroll_details')->where('payroll_id', $id)->orderBy('type')->orderBy('component_name')->get(),
        ]);
    }

    public function payslipPdf(int $id)
    {
        $payroll = DB::table('payrolls')
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payrolls.payroll_period_id')
            ->where('payrolls.id', $id)
            ->select('payrolls.*', 'employees.first_name', 'employees.last_name', 'employees.employee_number', 'payroll_periods.name as period_name')
            ->first();
        abort_unless($payroll && in_array($payroll->status, ['paid', 'locked'], true), 404);
        abort_unless(Auth::user()->employee_id === $payroll->employee_id || Auth::user()->hasRole('super-administrator'), 403);
        $details = DB::table('payroll_details')->where('payroll_id', $id)->get();
        $html = view('payroll.payslip-pdf', compact('payroll', 'details'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payslip-'.$payroll->employee_number.'.pdf"',
        ]);
    }

    private function componentAmount(object $component, float $currentIncome): float
    {
        if ($component->calculation_type === 'percentage') {
            return round($currentIncome * ((float) $component->amount / 100), 2);
        }

        return (float) $component->amount;
    }

    private function setting(string $key, string $default): string
    {
        return (string) (DB::table('system_settings')->where('setting_key', $key)->value('setting_value') ?? $default);
    }

    private function calculatePph21(float $monthlyTaxableIncome, ?string $ptkpStatus): float
    {
        if ($monthlyTaxableIncome <= 0) {
            return 0;
        }
        $ptkp = match (strtoupper((string) $ptkpStatus)) {
            'K/0' => 58500000,
            'K/1' => 63000000,
            'K/2' => 67500000,
            'K/3' => 72000000,
            default => 54000000,
        };
        $annualTaxable = max(0, floor(($monthlyTaxableIncome * 12 - $ptkp) / 1000) * 1000);
        $tax = 0;
        foreach ([[60000000, 0.05], [190000000, 0.15], [250000000, 0.25], [4500000000, 0.30], [PHP_FLOAT_MAX, 0.35]] as [$limit, $rate]) {
            $portion = min($annualTaxable, $limit);
            $tax += $portion * $rate;
            $annualTaxable -= $portion;
            if ($annualTaxable <= 0) {
                break;
            }
        }

        return round($tax / 12, 2);
    }

    private function workingDays(string $start, string $end): int
    {
        $days = 0;
        for ($date = strtotime($start); $date <= strtotime($end); $date = strtotime('+1 day', $date)) {
            if ((int) date('N', $date) < 6 && ! DB::table('holidays')->whereDate('date', date('Y-m-d', $date))->exists()) {
                $days++;
            }
        }

        return $days;
    }
}

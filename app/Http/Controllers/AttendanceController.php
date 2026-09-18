<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function today()
    {
        $employeeId = Auth::user()->employee_id;
        abort_unless($employeeId, 403, 'Akun belum tertaut ke karyawan.');

        return view('attendance.today', [
            'attendance' => DB::table('attendances')->where('employee_id', $employeeId)->whereDate('date', today())->first(),
        ]);
    }

    public function clockIn(Request $request, AuditService $audit)
    {
        $employeeId = Auth::user()->employee_id;
        abort_unless($employeeId, 403, 'Akun belum tertaut ke karyawan.');
        abort_if(DB::table('attendances')->where('employee_id', $employeeId)->whereDate('date', today())->exists(), 422, 'Anda sudah clock in hari ini.');
        $data = $request->validate(['lat' => ['nullable', 'numeric'], 'lng' => ['nullable', 'numeric']]);
        $assignment = DB::table('shift_assignments')
            ->leftJoin('work_schedule_days', function ($join) {
                $join->on('work_schedule_days.work_schedule_id', '=', 'shift_assignments.work_schedule_id')
                    ->whereRaw('work_schedule_days.day_of_week = ?', [now()->dayOfWeek]);
            })
            ->leftJoin('shifts', 'shifts.id', '=', 'work_schedule_days.shift_id')
            ->where('shift_assignments.employee_id', $employeeId)
            ->whereDate('shift_assignments.start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('shift_assignments.end_date')->orWhereDate('shift_assignments.end_date', '>=', today());
            })->select('shifts.id as shift_id', 'shifts.start_time', 'shifts.grace_period_minutes')->first();
        $employee = DB::table('employees')->where('id', $employeeId)->first();
        $location = $employee?->work_location_id ? DB::table('work_locations')->where('id', $employee->work_location_id)->first() : null;
        if ($location && $data['lat'] !== null && $data['lng'] !== null) {
            abort_if($this->distanceMeters((float) $data['lat'], (float) $data['lng'], (float) $location->latitude, (float) $location->longitude) > (float) $location->radius_meter, 422, 'Lokasi berada di luar area kerja.');
        }
        $lateMinutes = 0;
        if ($assignment?->start_time) {
            $shiftStart = today()->setTimeFromTimeString($assignment->start_time)->addMinutes((int) $assignment->grace_period_minutes);
            $lateMinutes = max(0, $shiftStart->diffInMinutes(now(), false));
        }

        DB::table('attendances')->insert([
            'employee_id' => $employeeId, 'date' => today(), 'clock_in' => now(),
            'clock_in_lat' => $data['lat'], 'clock_in_lng' => $data['lng'],
            'clock_in_ip' => $request->ip(), 'clock_in_device' => $request->userAgent(),
            'shift_id' => $assignment?->shift_id, 'late_minutes' => $lateMinutes,
            'status' => $lateMinutes > 0 ? 'late' : 'present', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $audit->record('clock_in', 'attendance', null, 'Clock in berhasil.', null, ['employee_id' => $employeeId], $request);

        return back()->with('status', 'Clock in berhasil.');
    }

    public function clockOut(Request $request, AuditService $audit)
    {
        $employeeId = Auth::user()->employee_id;
        $attendance = DB::table('attendances')->where('employee_id', $employeeId)->whereDate('date', today())->first();
        abort_unless($attendance, 422, 'Anda belum clock in hari ini.');
        abort_if($attendance->clock_out, 422, 'Anda sudah clock out hari ini.');

        DB::table('attendances')->where('id', $attendance->id)->update([
            'clock_out' => now(), 'clock_out_lat' => $request->input('lat'), 'clock_out_lng' => $request->input('lng'),
            'clock_out_ip' => $request->ip(), 'clock_out_device' => $request->userAgent(),
            'work_minutes' => now()->diffInMinutes($attendance->clock_in), 'updated_at' => now(),
        ]);
        $audit->record('clock_out', 'attendance', $attendance->id, 'Clock out berhasil.', $attendance, ['clock_out' => now()], $request);

        return back()->with('status', 'Clock out berhasil.');
    }

    public function history(Request $request)
    {
        $employeeId = Auth::user()->employee_id;
        $month = $request->input('month', now()->format('Y-m'));

        return view('attendance.history', [
            'month' => $month,
            'attendances' => DB::table('attendances')->where('employee_id', $employeeId)->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->orderByDesc('date')->paginate(20)->withQueryString(),
        ]);
    }

    public function requestCorrection(Request $request, AuditService $audit)
    {
        $employeeId = Auth::user()->employee_id;
        $data = $request->validate([
            'date' => ['required', 'date'],
            'requested_clock_in' => ['nullable'],
            'requested_clock_out' => ['nullable'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $attendance = DB::table('attendances')->where('employee_id', $employeeId)->whereDate('date', $data['date'])->first();
        $id = DB::table('attendance_corrections')->insertGetId([
            'employee_id' => $employeeId, 'attendance_id' => $attendance?->id, 'date' => $data['date'],
            'requested_clock_in' => $data['requested_clock_in'] ? $data['date'].' '.$data['requested_clock_in'] : null,
            'requested_clock_out' => $data['requested_clock_out'] ? $data['date'].' '.$data['requested_clock_out'] : null,
            'reason' => $data['reason'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $audit->record('correction_requested', 'attendance', $id, 'Koreksi absensi diajukan.', null, $data, $request);

        return back()->with('status', 'Pengajuan koreksi berhasil dikirim.');
    }

    public function corrections()
    {
        return view('attendance.corrections', [
            'corrections' => DB::table('attendance_corrections')->where('employee_id', Auth::user()->employee_id)->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function decideCorrection(Request $request, int $id, AuditService $audit)
    {
        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string', 'max:255']]);
        $correction = DB::table('attendance_corrections')->where('id', $id)->where('status', 'pending')->first();
        abort_unless($correction, 404);
        DB::transaction(function () use ($correction, $decision) {
            DB::table('attendance_corrections')->where('id', $correction->id)->update(['status' => $decision['decision'], 'approved_by' => Auth::id(), 'approved_at' => now(), 'notes' => $decision['notes'] ?? null, 'updated_at' => now()]);
            if ($decision['decision'] === 'approved') {
                $workMinutes = $correction->requested_clock_in && $correction->requested_clock_out
                    ? now()->parse($correction->requested_clock_in)->diffInMinutes(now()->parse($correction->requested_clock_out))
                    : 0;
                DB::table('attendances')->updateOrInsert(
                    ['employee_id' => $correction->employee_id, 'date' => $correction->date],
                    ['clock_in' => $correction->requested_clock_in, 'clock_out' => $correction->requested_clock_out, 'work_minutes' => $workMinutes, 'status' => 'present', 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });
        $audit->record($decision['decision'], 'attendance', $id, 'Keputusan koreksi absensi disimpan.', $correction, $decision, $request);

        return back()->with('status', 'Keputusan koreksi berhasil disimpan.');
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

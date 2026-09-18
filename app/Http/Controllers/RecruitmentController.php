<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecruitmentController extends Controller
{
    public function vacancies()
    {
        return view('recruitment.vacancies', ['vacancies' => DB::table('vacancies')->whereNull('deleted_at')->orderByDesc('created_at')->paginate(15)]);
    }

    public function storeVacancy(Request $request, AuditService $audit)
    {
        $data = $request->validate(['title' => ['required', 'max:150'], 'department_id' => ['nullable', 'integer'], 'position_id' => ['nullable', 'integer'], 'employment_type' => ['required', 'in:permanent,contract,intern,daily,freelance'], 'description' => ['nullable', 'string'], 'requirements' => ['nullable', 'string'], 'quota' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:open,closed,on_hold'], 'posted_date' => ['nullable', 'date'], 'closing_date' => ['nullable', 'date', 'after_or_equal:posted_date']]);
        $id = DB::table('vacancies')->insertGetId(array_merge($data, ['created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'recruitment', $id, 'Lowongan dibuat.', null, $data, $request);

        return back()->with('status', 'Lowongan berhasil dibuat.');
    }

    public function updateVacancy(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['title' => ['required', 'max:150'], 'department_id' => ['nullable', 'integer'], 'position_id' => ['nullable', 'integer'], 'employment_type' => ['required', 'in:permanent,contract,intern,daily,freelance'], 'description' => ['nullable', 'string'], 'requirements' => ['nullable', 'string'], 'quota' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:open,closed,on_hold'], 'posted_date' => ['nullable', 'date'], 'closing_date' => ['nullable', 'date', 'after_or_equal:posted_date']]);
        $before = DB::table('vacancies')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($before, 404);
        $hired = DB::table('candidates')->where('vacancy_id', $id)->where('stage', 'hired')->count();
        abort_if((int) $data['quota'] < $hired, 422, 'Kuota tidak boleh lebih kecil dari kandidat yang sudah diterima.');
        DB::table('vacancies')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $audit->record('updated', 'recruitment', $id, 'Lowongan diperbarui.', $before, $data, $request);

        return back()->with('status', 'Lowongan berhasil diperbarui.');
    }

    public function destroyVacancy(int $id, AuditService $audit)
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($vacancy, 404);
        abort_if(DB::table('candidates')->where('vacancy_id', $id)->whereNull('deleted_at')->exists(), 422, 'Lowongan yang sudah memiliki kandidat tidak dapat dihapus.');
        DB::table('vacancies')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        $audit->record('deleted', 'recruitment', $id, 'Lowongan dihapus.', $vacancy, null);

        return back()->with('status', 'Lowongan berhasil dihapus.');
    }

    public function pipeline(int $id)
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($vacancy, 404);

        return view('recruitment.pipeline', ['vacancy' => $vacancy, 'candidates' => DB::table('candidates')->where('vacancy_id', $id)->whereNull('deleted_at')->orderByDesc('created_at')->get()]);
    }

    public function candidate(int $id)
    {
        $candidate = DB::table('candidates as c')->join('vacancies as v', 'v.id', '=', 'c.vacancy_id')
            ->where('c.id', $id)->whereNull('c.deleted_at')->select('c.*', 'v.title as vacancy_title')->first();
        abort_unless($candidate, 404);

        return view('recruitment.candidate', [
            'candidate' => $candidate,
            'interviews' => DB::table('interviews')->where('candidate_id', $id)->orderByDesc('schedule_at')->get(),
            'assessments' => DB::table('candidate_assessments')->where('candidate_id', $id)->orderByDesc('created_at')->get(),
            'history' => DB::table('recruitment_stages')->where('candidate_id', $id)->orderByDesc('changed_at')->get(),
        ]);
    }

    public function storeCandidate(Request $request, int $id, AuditService $audit)
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($vacancy, 404);
        abort_if($vacancy->status !== 'open' || ($vacancy->closing_date && today()->gt($vacancy->closing_date)), 422, 'Lowongan tidak sedang dibuka.');
        abort_if(DB::table('candidates')->where('vacancy_id', $id)->whereIn('stage', ['hired', 'offering'])->count() >= $vacancy->quota, 422, 'Kuota lowongan sudah penuh.');
        $data = $request->validate(['full_name' => ['required', 'max:150'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'max:30'], 'birth_date' => ['nullable', 'date'], 'gender' => ['nullable', 'in:male,female'], 'address' => ['nullable', 'string'], 'source' => ['nullable', 'max:50'], 'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120']]);
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('candidate-cv', 'private');
        }
        unset($data['cv']);
        $candidateId = DB::table('candidates')->insertGetId(array_merge($data, ['vacancy_id' => $id, 'stage' => 'applied', 'created_at' => now(), 'updated_at' => now()]));
        DB::table('recruitment_stages')->insert(['candidate_id' => $candidateId, 'stage' => 'applied', 'notes' => 'Kandidat ditambahkan', 'changed_by' => Auth::id(), 'changed_at' => now(), 'created_at' => now()]);
        $audit->record('created', 'recruitment', $candidateId, 'Kandidat dibuat.', null, $data, $request);

        return back()->with('status', 'Kandidat berhasil ditambahkan.');
    }

    public function updateStage(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['stage' => ['required', 'in:applied,screening,interview,test,hr_interview,offering,hired,rejected'], 'notes' => ['nullable', 'max:255']]);
        DB::table('candidates')->where('id', $id)->update(['stage' => $data['stage'], 'updated_at' => now()]);
        DB::table('recruitment_stages')->insert(['candidate_id' => $id, 'stage' => $data['stage'], 'notes' => $data['notes'] ?? null, 'changed_by' => Auth::id(), 'changed_at' => now(), 'created_at' => now()]);
        $audit->record('stage_changed', 'recruitment', $id, 'Tahap kandidat diperbarui.', null, $data, $request);

        return back()->with('status', 'Tahap kandidat berhasil diperbarui.');
    }

    public function storeInterview(Request $request, int $id)
    {
        $data = $request->validate([
            'schedule_at' => ['required', 'date'], 'location' => ['nullable', 'max:255'],
            'type' => ['required', 'in:hr,user,technical'], 'notes' => ['nullable', 'string'],
        ]);
        abort_unless(DB::table('candidates')->where('id', $id)->whereNull('deleted_at')->exists(), 404);
        DB::table('interviews')->insert(array_merge($data, [
            'candidate_id' => $id, 'interviewer_id' => $request->integer('interviewer_id') ?: Auth::user()->employee_id,
            'created_at' => now(), 'updated_at' => now(),
        ]));

        return back()->with('status', 'Jadwal interview berhasil disimpan.');
    }

    public function decideInterview(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['result' => ['required', 'in:pass,fail'], 'notes' => ['nullable', 'string']]);
        $interview = DB::table('interviews')->where('id', $id)->first();
        abort_unless($interview, 404);
        DB::table('interviews')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $audit->record('decided', 'recruitment.interview', $id, 'Hasil interview diperbarui.', $interview, $data, $request);

        return back()->with('status', 'Hasil interview berhasil disimpan.');
    }

    public function storeAssessment(Request $request, int $id)
    {
        $data = $request->validate([
            'assessment_name' => ['required', 'max:150'], 'score' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'max:255'],
        ]);
        abort_unless(DB::table('candidates')->where('id', $id)->whereNull('deleted_at')->exists(), 404);
        DB::table('candidate_assessments')->insert(array_merge($data, [
            'candidate_id' => $id, 'assessed_by' => Auth::id(),
            'created_at' => now(), 'updated_at' => now(),
        ]));

        return back()->with('status', 'Assessment kandidat berhasil disimpan.');
    }

    public function hire(Request $request, int $id, AuditService $audit)
    {
        $data = $request->validate(['join_date' => ['required', 'date']]);
        $candidate = DB::table('candidates')->where('id', $id)->where('stage', 'offering')->first();
        abort_unless($candidate, 422, 'Kandidat harus berada pada tahap offering.');
        abort_if($candidate->employee_id, 422, 'Kandidat sudah menjadi karyawan.');
        $vacancy = DB::table('vacancies')->where('id', $candidate->vacancy_id)->first();
        abort_unless($vacancy, 404);
        abort_if(DB::table('candidates')->where('vacancy_id', $vacancy->id)->where('stage', 'hired')->count() >= $vacancy->quota, 422, 'Kuota lowongan sudah penuh.');
        $employeeId = DB::transaction(function () use ($candidate, $vacancy, $id, $data) {
            $number = 'EMP-'.str_pad((string) ((int) DB::table('employees')->max('id') + 1), 4, '0', STR_PAD_LEFT);
            $employeeId = DB::table('employees')->insertGetId([
                'employee_number' => $number, 'first_name' => $candidate->full_name,
                'gender' => $candidate->gender ?? 'male', 'phone' => $candidate->phone,
                'personal_email' => $candidate->email, 'company_id' => DB::table('companies')->value('id'),
                'department_id' => $vacancy->department_id, 'position_id' => $vacancy->position_id,
                'join_date' => $data['join_date'], 'employment_status' => 'probation',
                'employment_type' => $vacancy->employment_type, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('candidates')->where('id', $id)->update(['stage' => 'hired', 'employee_id' => $employeeId, 'updated_at' => now()]);
            DB::table('onboarding')->insert([
                'employee_id' => $employeeId, 'candidate_id' => $id, 'start_date' => today(),
                'status' => 'in_progress', 'progress_percent' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $employeeId;
        });
        $audit->record('hired', 'recruitment', $id, 'Kandidat diterima menjadi karyawan.', $candidate, ['employee_id' => $employeeId, 'join_date' => $data['join_date']], $request);

        return back()->with('status', 'Kandidat diterima dan onboarding dibuat untuk employee #'.$employeeId.'.');
    }
}

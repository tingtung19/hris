<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyRemindersCommand extends Command
{
    protected $signature = 'hris:legacy-reminders {task : training|birthday|contract|cleanup}';

    protected $description = 'Run migrated HRIS reminder and cleanup jobs.';

    public function handle(): int
    {
        $count = match ($this->argument('task')) {
            'training' => $this->training(),
            'birthday' => $this->birthday(),
            'contract' => $this->contract(),
            'cleanup' => DB::table('notifications')->where('is_read', 1)->where('read_at', '<', now()->subDays(90))->delete(),
        };
        $this->info('Completed '.$this->argument('task').': '.$count);

        return self::SUCCESS;
    }

    private function training(): int
    {
        $participants = DB::table('training_participants as tp')
            ->join('trainings as t', 't.id', '=', 'tp.training_id')
            ->join('users as u', 'u.employee_id', '=', 'tp.employee_id')
            ->whereDate('t.start_date', today()->addDay())->where('tp.status', 'registered')->where('u.status', 'active')
            ->select('u.id as user_id', 'tp.training_id', 't.title', 't.location')->get();
        foreach ($participants as $participant) {
            $this->notify($participant->user_id, 'training_reminder', 'Pengingat Training', 'Training "'.$participant->title.'" akan dimulai besok'.($participant->location ? ' di '.$participant->location : '').'.', '/training/'.$participant->training_id.'/participants');
        }

        return $participants->count();
    }

    private function birthday(): int
    {
        $users = DB::table('users as u')->join('employees as e', 'e.id', '=', 'u.employee_id')
            ->where('u.status', 'active')->whereNull('e.deleted_at')->whereNotNull('e.birth_date')
            ->whereRaw("DATE_FORMAT(e.birth_date, '%m-%d') = ?", [today()->format('m-d')])->pluck('u.id');
        foreach ($users as $userId) {
            $this->notify($userId, 'birthday', 'Ulang Tahun Karyawan', 'Selamat ulang tahun! Semoga sehat dan sukses.', '/dashboard');
        }

        return $users->count();
    }

    private function contract(): int
    {
        $hrUsers = DB::table('users')->join('user_roles', 'user_roles.user_id', '=', 'users.id')->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.slug', ['super-administrator', 'hr-administrator', 'hr-manager'])->where('users.status', 'active')->distinct()->pluck('users.id');
        $sent = 0;
        foreach ([90, 60, 30, 14, 7] as $days) {
            $contracts = DB::table('employee_contracts as c')->join('employees as e', 'e.id', '=', 'c.employee_id')
                ->where('c.status', 'active')->whereDate('c.end_date', today()->addDays($days))->select('c.*', 'e.first_name', 'e.last_name')->get();
            foreach ($contracts as $contract) {
                foreach ($hrUsers as $userId) {
                    $this->notify($userId, 'contract_expiring', 'Kontrak Akan Berakhir', 'Kontrak '.$contract->contract_number.' atas nama '.$contract->first_name.' '.$contract->last_name.' berakhir dalam '.$days.' hari.', '/employees/'.$contract->employee_id);
                }
                $sent++;
            }
        }
        DB::table('employee_contracts')->where('status', 'active')->whereDate('end_date', '<', today())->update(['status' => 'expired', 'updated_at' => now()]);

        return $sent;
    }

    private function notify(int $userId, string $type, string $title, string $message, string $link): void
    {
        DB::table('notifications')->insert(['user_id' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, 'link' => $link, 'created_at' => now()]);
    }
}

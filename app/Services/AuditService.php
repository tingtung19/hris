<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function record(
        string $action,
        string $module,
        string|int|null $recordId = null,
        ?string $description = null,
        mixed $before = null,
        mixed $after = null,
        ?Request $request = null,
    ): void {
        $request ??= request();
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId === null ? null : (string) $recordId,
            'description' => $description,
            'before_data' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_data' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}

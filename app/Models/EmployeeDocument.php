<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }
}

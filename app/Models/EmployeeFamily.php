<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeFamily extends Model
{
    protected $table = 'employee_families';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
        ];
    }
}

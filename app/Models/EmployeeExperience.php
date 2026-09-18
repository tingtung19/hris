<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeExperience extends Model
{
    protected $table = 'employee_experiences';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}

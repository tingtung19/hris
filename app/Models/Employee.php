<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function families()
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    public function educations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function experiences()
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class)->whereNull('deleted_at');
    }

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'birth_date' => 'date',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivedStaff extends Model
{
    use HasFactory;

    protected $table = 'archived_staff';

    protected $fillable = [
        'business_id',
        'name',
        'position',
        'email',
        'phone',
        'hire_date',
        'termination_date',
        'termination_reason',
        'last_salary',
        'notes',
        'status',
        'archived_at',
        'birth_date',
        'height',
        'weight',
        'gender',
        'experience_years',
        'seniority_years',
        'education',
        'employment_type',
        'current_schedule',
        'avatar_path',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'last_salary' => 'decimal:2',
        'archived_at' => 'datetime',
        'birth_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
} 
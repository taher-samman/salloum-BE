<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Application extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public static $statuses = ['pending', 'active', 'done'];

    protected $fillable = [
        'dr_email',
        'media_link',
        'student_id',
        'school_name_address',
        'study_specialty',
        'current_study_year',
        'study_avg',
        'tuition_cost_semester',
        'tuition_cost_year',
        'father_work',
        'mother_work',
        'family_members',
        'anyone_working',
        'household_income',
        'terms',
        'extra_fields',
        'status'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'student'
    ];

    public function student()
    {
        return $this->hasOne(Student::class, 'id', 'student_id');
    }

    public function getStudentAttribute()
    {
        return $this->student()->first();
    }

    public function getExtraFieldsAttribute($value)
    {
        return json_decode($value, true);
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->extra_fields = json_encode($model->extra_fields);
        });

        self::saving(function ($model) {
            if (!in_array($model->status, self::$statuses)) {
                throw new \InvalidArgumentException("Invalid status value");
            }
        });

        self::created(function ($model) {
            $data = [
                "id" => $model->id,
                "email" => $model->dr_email
            ];
            $code = Crypt::encryptString(json_encode($data));
            \Log::info('[SET-FEEDBACK-URL]: ' . 'http://localhost/amc/feedback/' . $code);
        });
    }
}

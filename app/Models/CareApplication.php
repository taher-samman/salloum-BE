<?php

namespace App\Models;

use App\Mail\HealthCareApplicantEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CareApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'be_wrhb3syq97_care_applications';
    public static $statuses = ['active', 'done'];

    protected $fillable = [
        'applicant_id',
        'media_link',
        'help_type',
        'treatment_cost',
        'dr_name',
        'dr_mobile',
        'hospital_name',
        'family_members_working',
        'work_type',
        'household_income',
        'old_program',
        'old_program_details',
        'social_health_situation',
        'extra_fields',
        'status'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'applicant'
    ];

    static function reformulateAttachments($data)
    {
        $attachments = [];
        $data = array_filter($data, function ($value, $key) use (&$attachments) {
            if (strpos($key, 'attachments') === 0) {
                $attachments[$key] = $value;
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $data['attachments'] = $attachments;
        return $data;
    }

    public function applicant()
    {
        return $this->hasOne(Applicant::class, 'id', 'applicant_id');
    }

    public function getApplicantAttribute()
    {
        return $this->applicant()->first();
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
            Mail::to($model->applicant()->first()->email)->send(new HealthCareApplicantEmail());
        });
    }
}

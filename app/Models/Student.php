<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ZipArchive;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'be_wrhb3syq97_students';

    protected $fillable = [
        'email',
        'fullname',
        'dob',
        'address',
        'mobile'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];

    public static function boot()
    {
        parent::boot();
        self::created(function ($model) {
            try {
                $fileKeys = ['school_grades_image', 'id_image', 'scholarship_reason', 'tuition_cost_image'];
                $data = request()->all();
                $data['student_id'] = $model->id;
                $data['extra_fields'] = '{}';
                $data['media_link'] = '';
                $data['status'] = Application::$statuses[0];

                $zipFilename = public_path($data['email'] . ".zip");
                $zip = new ZipArchive();
                if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($fileKeys as $key) {
                        if (request()->input($key)) {
                            $base64_data = request()->input($key);
                            $ext = request()->input($key . '_ext');
                            $file = base64_decode($base64_data);
                            $zip->addFromString($key . '.' . $ext, $file);
                        }
                    }

                    $zip->close();
                } else {
                    $model->delete();
                    throw new Exception('error zipping media', 1);
                }
                Application::create($data);
            } catch (\Throwable $th) {
                $model->delete();
                throw new Exception($th->getMessage(), 1);
            }
        });
    }
}

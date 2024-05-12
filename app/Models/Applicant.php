<?php

namespace App\Models;

use App\Http\Controllers\Apis\BaseController;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ZipArchive;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class Applicant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'be_wrhb3syq97_care_applicants';

    protected $fillable = [
        'email',
        'name',
        'father_name',
        'familly',
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
                $data = CareApplication::reformulateAttachments(request()->all());
                $data['applicant_id'] = $model->id;
                $data['extra_fields'] = '{}';
                $data['media_link'] = '';
                $data['status'] = CareApplication::$statuses[0];
                $fileKeys = ['treatment_cost_image', 'attachments'];
                $zipFilename = public_path($data['email'] . ".zip");
                $zip = new ZipArchive();
                if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($fileKeys as $key) {
                        if (isset($data[$key]['code']) && $data[$key]['code'] != '') {
                            $base64_data = $data[$key]['code'];
                            $file = base64_decode($base64_data);
                            $zip->addFromString($data[$key]['name'], $file);
                        } else {
                            foreach ($data[$key] as $att) {
                                if (isset($att['code']) && $att['code'] != '') {
                                    $base64_data = $att['code'];
                                    $file = base64_decode($base64_data);
                                    $zip->addFromString($att['name'], $file);
                                }
                            }
                        }
                    }

                    $zip->close();

                    $accessToken = BaseController::token();
                    $client = new Google_Client();
                    $client->setAccessToken($accessToken);
                    $driveService = new Google_Service_Drive($client);
                    $fileMetadata = new Google_Service_Drive_DriveFile(array(
                        'name' => 'HealthCare_' . $data['email'] . ".zip"
                    ));
                    $mimeType = mime_content_type($zipFilename);
                    $createdFile = $driveService->files->create($fileMetadata, [
                        'data' => file_get_contents($zipFilename),
                        'mimeType' => $mimeType,
                        'uploadType' => 'multipart',
                    ]);

                    if ($createdFile && isset($createdFile->id)) {
                        $data['media_link'] = $createdFile->id;
                        CareApplication::create($data);

                        unlink($zipFilename);
                    } else {
                        $model->delete();
                        throw new \Exception('[Google Drive] Media id not found');
                    }
                } else {
                    $model->delete();
                    throw new Exception('error zipping media', 1);
                }
            } catch (\Throwable $th) {
                $model->delete();
                throw new Exception($th->getMessage(), 1);
            }
        });
    }
}

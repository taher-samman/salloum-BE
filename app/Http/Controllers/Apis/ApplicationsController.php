<?php

namespace App\Http\Controllers\Apis;

use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\Student;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ApplicationsController extends BaseController
{

    public function postLog(Request $request)
    {
        \Log::info('[REQUEST postLog]:' . json_encode($request->all()));
    }
    public function postApplication(Request $request)
    {
        // \Log::info('[REQUEST postApplication]:' . json_encode($request->all()));


        $fileValidation = 'required|base64';
        $stringValidation = 'required|string';
        $emailValidation = 'required|string|email_strict';

        $validate = Validator::make($request->all(), [
            'email' => $emailValidation,
            'fullname' => $stringValidation,
            'dob' => 'required|date',
            'address' => $stringValidation,
            'mobile' => $stringValidation,
            'dr_email' => $emailValidation,
            'school_name_address' => $stringValidation,
            'study_specialty' => $stringValidation,
            'current_study_year' => $stringValidation,
            'study_avg' => 'required|numeric',
            'tuition_cost_semester' => $stringValidation,
            'tuition_cost_image' => $fileValidation,
            'tuition_cost_year' => $stringValidation,
            'scholarship_reason' => $fileValidation,
            'father_work' => $stringValidation,
            'mother_work' => $stringValidation,
            'family_members' => 'required|numeric',
            'anyone_working' => $stringValidation,
            'household_income' => $stringValidation,
            'id_image' => $fileValidation,
            'school_grades_image' => $fileValidation,
            'terms' => $stringValidation
        ], [
            'email_strict' => 'The :attribute field must be a valid email address.',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            $message = '';
            foreach ($errors->all() as $error) {
                $message .= $error . ' ';
            }

            return $this->sendError($message, [], 403);
        }

        try {
            $student = Student::create($request->all());
            return $this->sendResponse($student, 'Add Scholarship successfly');
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->sendError('Email used by another student', [], 403);
            } else {
                return $this->sendError($e->getMessage(), [], 403);
            }
        }
    }

    public function getApplications(Request $request)
    {
        \Log::info('[REQUEST getApplications]');
        try {
            $applications = Application::whereIn('status', [Application::$statuses[1], Application::$statuses[2]])
                ->orderBy('status', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            return $this->sendResponse($applications);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 403);
        }
    }

    public function postApplications(Request $request)
    {
        $applications = $request->input('applications');
        \Log::info('[REQUEST postApplications]:' . json_encode($applications));
        try {
            foreach ($applications as $app) {
                if (isset($app['student_id'])) {
                    $appModel = Application::where('student_id', $app['student_id'])->first();
                    if ($appModel) {
                        $appModel->update($app);
                    }
                }
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 403);
        }

        return $this->sendResponse([], 'success');
    }

    public function postDoneApplication(Request $request)
    {
        $application = $request->input('application');
        \Log::info('[REQUEST postDoneApplication]:' . json_encode($application));
        try {
            if (isset($application['student_id'])) {
                $appModel = Application::where('student_id', $application['student_id'])
                    ->where('status', Application::$statuses[1])
                    ->first();
                if ($appModel) {
                    // remove media from Google Drive
                    $accessToken = $this->token();
                    $response = Http::withToken($accessToken)
                        ->delete('https://www.googleapis.com/drive/v3/files/' . $appModel->media_link);

                    if ($response->successful()) {
                        $appModel->status = Application::$statuses[2];
                        $appModel->save();
                        return $this->sendResponse([], 'success');
                    } else {
                        return $this->sendError($response->json(), [], 403);
                    }
                }
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 403);
        }

        return $this->sendResponse([], 'success');
    }

    public function postValidateFeedback(Request $request)
    {
        $code = $request->input('code');
        $data = json_decode(Crypt::decryptString($code));
        try {
            \Log::info('[getValidateFeedback] decrypted data: ' . json_encode($data));
            $application = Application::where(['id' => $data->id, 'dr_email' => $data->email])->firstOrFail();
            if (!empty($application) && $application->status === Application::$statuses[0]) {
                return $this->sendResponse([
                    'hasAccess' => true
                ], 'successfly to add feedback');
            } else {
                return $this->sendError('you don\'t have access to add feedback');
            }
        } catch (\Throwable $th) {
            \Log::info('[getValidateFeedback] Can\'t find app with data: ' . json_encode($data));
            \Log::info('[Throwable] : ' . $th->getMessage());
            return $this->sendError('Internal server error');
        }
    }

    public function postFeedback(Request $request)
    {
        $data = $request->all();

        $validate = Validator::make($data, [
            'code' => 'required|string',
            'file' => 'required|base64',
            'ext' => 'required|string'
        ]);

        if ($validate->fails()) {
            return $this->sendError('Wrong Data', $validate->errors(), 403);
        }

        $code = json_decode(Crypt::decryptString($data['code']));
        try {
            $application = Application::where(['id' => $code->id, 'dr_email' => $code->email])->firstOrFail();
            if (!empty($application) && $application->status === Application::$statuses[0]) {
                $email = $application->student()->first()->email;
                $drFeedback = base64_decode($data['file']);
                $drFeedbackExt = $data['ext'];

                $oldZip = public_path($email . ".zip");
                $oldImagesFolder = public_path($email);
                $oldZipFile = new ZipArchive();
                if ($oldZipFile->open($oldZip) === TRUE) {
                    $oldZipFile->extractTo($oldImagesFolder);
                    $oldZipFile->close();
                } else {
                    return $this->sendError('error with old zip file');
                }

                touch(public_path($email . '/' . $email . '.txt'));

                $newZip = public_path($email . ".zip");
                $newZipFile = new ZipArchive;
                if ($newZipFile->open($newZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $oldFiles = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($oldImagesFolder),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($oldFiles as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($oldImagesFolder) + 1);
                            $newZipFile->addFile($filePath, $relativePath);
                        }
                    }
                    $newZipFile->addFromString('dr_feedback.' . $drFeedbackExt, $drFeedback);

                    $newZipFile->close();

                    $accessToken = $this->token();
                    $response = Http::withToken($accessToken)
                        ->attach('data', file_get_contents($newZip), $email . '.zip')
                        ->post(
                            'https://www.googleapis.com/upload/drive/v3/files',
                            [
                                'name' => $email . '.zip'
                            ],
                            [
                                'Content-Type' => 'application/octet-stream',
                            ]
                        );

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true);
                        if (isset($responseData['id'])) {
                            $application->media_link = $responseData['id'];
                            $application->status = Application::$statuses[1];
                            $application->save();

                            if (File::isDirectory($oldImagesFolder)) {
                                File::deleteDirectory($oldImagesFolder);
                            }
                            unlink($newZip);
                        } else {
                            throw new \Exception('[Google Drive] Media id not found');
                        }
                    } else {
                        $error = $response->json();
                        throw new \Exception('[Google Drive] ' . $error);
                    }

                    return $this->sendResponse([], 'successsssssssssss');
                } else {
                    return $this->sendError('error with new zip file');
                }
            } else {
                return $this->sendError('you don\'t have access to add feedback');
            }
        } catch (\Throwable $th) {
            \Log::info('[Throwable] : ' . $th->getMessage());
            return $this->sendError('Internal server error');
        }
    }

    public function postDownloadMedia(Request $request)
    {
        \Log::info('[postDownloadMedia] ' . json_encode($request->all()));

        $validate = Validator::make($request->all(), [
            'media_id' => 'required|string'
        ]);

        if ($validate->fails()) {
            return $this->sendError('Wrong Data', $validate->errors(), 403);
        }

        $fileId = $request->input('media_id');

        $accessToken = $this->token();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media");

        if ($response->successful()) {
            $fileContents = $response->getBody()->getContents();
            $base64Encoded = base64_encode($fileContents);

            return $this->sendResponse(['file' => $base64Encoded], 'Downloading...');
        }
        return $this->sendError('internal server error');
    }
}

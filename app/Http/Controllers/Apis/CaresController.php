<?php

namespace App\Http\Controllers\Apis;

use App\Models\Applicant;
use App\Models\CareApplication;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class CaresController extends BaseController
{
    public function postHealthcare(Request $request)
    {
        $requestData = CareApplication::reformulateAttachments($request->all());

        // \Log::info('[REQUEST postHealthcare]:' . json_encode($requestData));

        $fileValidation = 'required|base64|base64size:1000';
        $stringValidation = 'required|string';
        $emailValidation = 'required|string|email_strict';

        $validate = Validator::make($requestData, [
            'name' => $stringValidation,
            'father_name' => $stringValidation,
            'familly' => $stringValidation,
            'dob' => 'required|date',
            'email' => $emailValidation,
            'address' => $stringValidation,
            'mobile' => $stringValidation,
            'help_type' => $stringValidation,
            'treatment_cost' => $stringValidation,
            'treatment_cost_image.code' => $fileValidation,
            'treatment_cost_image.name' => $stringValidation,
            'dr_name' => $stringValidation,
            'dr_mobile' => $stringValidation,
            'hospital_name' => $stringValidation,
            'family_members_working' => $stringValidation,
            'work_type' => $stringValidation,
            'household_income' => $stringValidation,
            'old_program' => $stringValidation,
            'old_program_details' => $stringValidation,
            'social_health_situation' => $stringValidation,
            'attachments' => 'required|array|min:1',
            'attachments.*.code' => $fileValidation,
            'attachments.*.name' => $stringValidation,
        ], [
            'email_strict' => 'The :attribute field must be a valid email address.',
            'base64size' => 'Uploaded files should be less then 1M',
            'base64' => 'The :attribute file type is incorrect'
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
            $applicant = Applicant::create($requestData);
            return $this->sendResponse($applicant, 'Health Care requested Successfly');
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->sendError('Email used by another applicant', [], 403);
            } else {
                return $this->sendError($e->getMessage(), [], 403);
            }
        }
    }

    public function getApplications(Request $request)
    {
        \Log::info('[REQUEST getApplications]');
        try {
            $applications = CareApplication::orderBy('created_at', 'desc')
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
                if (isset($app['applicant_id'])) {
                    $appModel = CareApplication::where('applicant_id', $app['applicant_id'])->first();
                    if ($appModel) {
                        $appModel->update($app);
                    }
                }else{
                    return $this->sendError('Application Not Found', [], 403);
                }
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 403);
        }

        return $this->sendResponse([], 'Your applications has been successfully updated');
    }

    public function postDoneApplication(Request $request)
    {
        $application = $request->input('application');
        \Log::info('[REQUEST postDoneApplication]:' . json_encode($application));
        try {
            if (isset($application['applicant_id'])) {
                $appModel = CareApplication::where('applicant_id', $application['applicant_id'])
                    ->where('status', CareApplication::$statuses[0])
                    ->first();
                if ($appModel) {
                    // remove media from Google Drive
                    $accessToken = BaseController::token();
                    $response = Http::withToken($accessToken)
                        ->delete('https://www.googleapis.com/drive/v3/files/' . $appModel->media_link);

                    if ($response->successful()) {
                        $appModel->status = CareApplication::$statuses[1];
                        $appModel->save();
                        return $this->sendResponse([], 'success');
                    } else {
                        return $this->sendError($response->json(), [], 403);
                    }
                } else {
                    return $this->sendError('Application not found', [], 403);
                }
            } else {
                return $this->sendError('student_id required', [], 403);
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 403);
        }

        return $this->sendResponse([], 'success');
    }
}

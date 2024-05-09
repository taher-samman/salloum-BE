<?php

namespace App\Http\Controllers\Apis;

use App\Mail\ContactUsEmail;
use App\Mail\VolunteerEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class EmailsController extends BaseController
{

    public function postContactUsEmail(Request $request)
    {
        \Log::info('[REQUEST postContactUsEmail]:' . json_encode($request->all()));

        $stringValidation = 'required|string';
        $emailValidation = 'required|string|email_strict';
        $validate = Validator::make($request->all(), [
            'email' => $emailValidation,
            'name' => $stringValidation,
            'subject' => $stringValidation,
            'phone' => $stringValidation,
            'message' => $stringValidation
        ], [
            'email_strict' => 'The :attribute field must be a valid email address.'
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
            Mail::to(config('mail.from.address'))->send(new ContactUsEmail($request->all()));
            return $this->sendResponse([], 'Mail Sent thank you');
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), [], 403);
        }
    }

    public function postVolunteerEmail(Request $request)
    {
        \Log::info('[REQUEST postVolunteerEmail]:' . json_encode($request->all()));
        return $this->sendError('shbk');
        $stringValidation = 'required|string';
        $emailValidation = 'required|string|email_strict';
        $validate = Validator::make($request->all(), [
            'name' => $stringValidation,
            'age' => 'required|numeric',
            'academic' => $stringValidation,
            'occupation' => $stringValidation,
            'email' => $emailValidation,
            'phone' => $stringValidation,
            'days' => $stringValidation,
            'time' => $stringValidation,
            'activities' => $stringValidation,
        ], [
            'email_strict' => 'The :attribute field must be a valid email address.'
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
            Mail::to(config('mail.from.address'))->send(new VolunteerEmail($request->all()));
            return $this->sendResponse([], 'Mail Sent thank you');
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), [], 403);
        }
    }
}

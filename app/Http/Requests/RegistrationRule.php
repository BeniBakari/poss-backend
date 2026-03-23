<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Constants\Error;
class RegistrationRule extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|max:30|alpha|regex:/^[A-Z]/',
            'middle_name' =>'nullable|max:30|alpha|regex:/^[A-Z]/',
            'last_name'=> 'required|max:30|alpha|regex:/^[A-Z]/',
            'phone' => 'required|numeric|digits:10|unique:users',
            'role_id' => 'required|numeric|digits:10|unique:roles',
            'address' => 'required|string|min:4',
            'email' => 'required|email|unique:users',
            'password' =>'required',
            'dob' => 'required|date',
            'confirm_password' => 'required|same:password',
            'gender' => 'required|max:1|alpha|regex:/^[M,F]/'  
        ];
    }

    /**
     * Failed Validation
     */
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => Error::$validationError,
            'data'      => $validator->errors()
        ],400));
    }
}

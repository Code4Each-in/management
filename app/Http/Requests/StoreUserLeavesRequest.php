<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserLeavesRequest extends FormRequest
{
    public $validator = null;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => ['required',
            function ($attribute, $value, $fail) {
                $from = Carbon::parse($value);
                $to = Carbon::parse($this->input('to'));
                if ($to !=null && $to->isBefore($from)) {
                    $fail('To date has to be greater than From date.');
                }
            }
            ],
			'to' => 'required', 
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $this->validator = $validator;
    }
}
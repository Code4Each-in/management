<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
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
            'intime' => 'required',
            // function ($attribute, $value, $fail) {
            //     $intime = Carbon::parse($value);
            //     $outime = Carbon::parse($this->input('intime'));
            //     if ($outime !=null && $outime->isBefore($outime)) {
            //         $fail('Intime has to be greater than Outtime .');
            //     }
            // }
            
			'outime' => 'required', 
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $this->validator = $validator;
    }
}
<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserLeaves extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => [
                'required',
                // 'date',
                // function ($attribute, $value, $fail) {
                //     $from = Carbon::parse($value);
                //     $to = Carbon::parse($this->input('to'));
                //     if ($this->input('to') !=null && $to > $from) {
                //         $fail('From date has to be greater than to date.');
                //     }
                // }
            ],
            'to' => [
                'required',
                // function ($attribute, $value, $fail) {
                //     $to = Carbon::parse($value);
                //     $from = Carbon::parse($this->input('from'));
                //     if ($to<$from) {
                //         $fail('To date has to be lesser than from date.');
                //     }
                // }
            ],
        ];
    }
}
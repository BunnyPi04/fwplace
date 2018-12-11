<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesignDiagramRequests extends FormRequest
{
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
            'diagram' => 'nullable',
            'content' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('Required'),
            'name.unique' => __('Exists'),
            'diagram' => __('Required'),
            'content' => __('Required'),
        ];
    }
}

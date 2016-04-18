<?php namespace Core\Http\Requests\Admin;

use Core\Http\Requests\FormRequest as AbsFormRequest;

/**
 * Class SettingsSetFormRequest
 * @package Core\Http\Requests\Admin
 */
class SettingsSetFormRequest extends AbsFormRequest
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
            'setting_key' => 'required|alpha_dash',
            'setting_value' => 'required',
        ];
    }
}
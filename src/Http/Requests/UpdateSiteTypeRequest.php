<?php

namespace Adaptcms\SiteTypes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteTypeRequest extends FormRequest
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
      'vendor'  => 'sometimes|required',
      'package' => 'sometimes|required'
    ];
  }
}

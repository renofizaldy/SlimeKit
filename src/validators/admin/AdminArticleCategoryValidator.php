<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminArticleCategoryValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $rules = [
          'status' => 'required|not_empty',
        ];
      break;
      case 'detail':
        $rules = [
          'id'    => 'required|not_empty',
          'field' => 'required|not_empty',
        ];
      break;
      case 'add':
        $rules = [
          'slug'             => 'required|not_empty',
          'title'            => 'required|not_empty',
          'description'      => 'required|not_empty',
          'meta_title'       => 'required|not_empty',
          'meta_description' => 'required|not_empty',
          'meta_robots'      => 'required|not_empty',
          'status'           => 'required|not_empty',
        ];
      break;
      case 'edit':
        $rules = [
          'id'               => 'required|not_empty',
          'title'            => 'required|not_empty',
          'slug'             => 'required|not_empty',
          'description'      => 'required|not_empty',
          'meta_title'       => 'required|not_empty',
          'meta_description' => 'required|not_empty',
          'meta_robots'      => 'required|not_empty',
          'status'           => 'required|not_empty',
        ];
      break;
      case 'drop':
        $rules = [
          'id' => 'required|not_empty',
        ];
      break;
      case 'check_slug':
        $rules = [
          'slug' => 'required|not_empty',
        ];
      break;
      default:
        throw new Exception('Can\'t validate some field', 400);
      break;
    }
    $this->helper->validateByRules($input, $rules);
    return true;
  }
}
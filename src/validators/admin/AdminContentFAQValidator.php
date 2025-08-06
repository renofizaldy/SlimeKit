<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminContentFAQValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'add':
        $rules = [
          'title'       => 'required|not_empty',
          'description' => 'required|not_empty',
        ];
      break;
      case 'edit':
        $rules = [
          'id'          => 'required|not_empty',
          'title'       => 'required|not_empty',
          'description' => 'required|not_empty',
        ];
      break;
      case 'sort':
        $rules = [
          'order' => 'required|not_empty'
        ];
      break;
      case 'drop':
        $rules = [
          'id' => 'required|not_empty'
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
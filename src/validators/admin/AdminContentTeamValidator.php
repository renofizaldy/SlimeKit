<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminContentTeamValidator
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
          'name'    => 'required|string|not_empty',
          'title'   => 'required|string|not_empty',
          'picture' => 'required|string|not_empty'
        ];
      break;
      case 'edit':
        $rules = [
          'id'      => 'required|not_empty',
          'name'    => 'required|not_empty',
          'title'   => 'required|not_empty',
          'picture' => 'required',
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
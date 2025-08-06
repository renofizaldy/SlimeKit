<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminSettingUserValidator
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
          'fullname' => 'required|not_empty',
          'username' => 'required|string|not_empty',
          'password' => 'required|string|not_empty',
          'status'   => 'required',
          'role'     => 'required|not_empty'
        ];
      break;
      case 'edit':
        $rules = [
          'id'       => 'required|not_empty',
          'fullname' => 'required|string|not_empty',
          'username' => 'required|string|not_empty',
          'status'   => 'required',
          'role'     => 'required|not_empty'
        ];
      break;
      case 'pass':
        $rules = [
          'id'       => 'required|not_empty',
          'password' => 'required|string|not_empty'
        ];
      break;
      case 'drop':
        $rules = [
          'id' => 'required|not_empty'
        ];
      break;
      case 'roles_add':
        $rules = [
          'label' => 'required|string|not_empty',
          'role'  => 'required|not_empty'
        ];
      break;
      case 'roles_edit':
        $rules = [
          'id'    => 'required|not_empty',
          'label' => 'required|string|not_empty',
          'role'  => 'required|not_empty'
        ];
      break;
      case 'roles_drop':
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
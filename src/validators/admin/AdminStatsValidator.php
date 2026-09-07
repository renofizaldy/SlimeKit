<?php

namespace App\Validators\Admin;

use Exception;
use Rakit\Validation\Validator;
use App\Helpers\General;

class AdminStatsValidator
{
  public function __construct(
    private Validator $validator,
    private General $helper
  ) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list_log':
        $rules = $this->helper->paginationRules([
          'limit'  => 'nullable',
          'table'  => 'nullable',
          'user'   => 'nullable',
          'action' => 'nullable'
        ]);
        break;
      case 'list_cron':
        $rules = $this->helper->paginationRules([
          'site'   => 'required'
        ]);
        break;
      case 'drop_log':
        $rules = [
          'id' => 'required'
        ];
        break;
      case 'total_article':
        $rules = [
          'site'   => 'required',
          'status' => 'required'
        ];
        break;
      case 'list_visits':
        $rules = [
          'site'  => 'required',
          'start' => 'required|date:Y-m-d',
          'end'   => 'required|date:Y-m-d'
        ];
        break;
      case 'list_visits_slug':
        $rules = [
          'site'  => 'required',
          'start' => 'required|date:Y-m-d',
          'end'   => 'required|date:Y-m-d'
        ];
        break;
      default:
        throw new Exception('Unknown validation type', 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      $errors = $validation->errors()->firstOfAll();
      throw new Exception(json_encode(['validation_errors' => $errors]), 400);
    }

    return true;
  }
}
<?php

namespace App\Validators;

use Exception;
use Rakit\Validation\Validator;

class JobValidator
{
  public function __construct(private Validator $validator) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'article_publish':
        $rules = [
          'site' => 'required',
          'slug' => 'required',
        ];
        break;
      case 'article_track_stats':
        $rules = [
          'site' => 'required',
          'slug' => 'required',
        ];
        break;
      case 'database_backup':
        $rules = [
          'provider' => 'in:r2,gcs,both',
        ];
        break;
      default:
        throw new Exception('Can\'t validate some field', 400);
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
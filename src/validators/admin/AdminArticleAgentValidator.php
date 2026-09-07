<?php

namespace App\Validators\Admin;

use Rakit\Validation\Validator;
use Exception;

class AdminArticleAgentValidator
{
  public function __construct(private Validator $validator) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'step1_inputTopic':
        $topicMode = $input['topic_mode'] ?? 'manual';
        $rules = [];
        if ($topicMode === 'manual') {
          $rules['topic'] = 'required';
        }
        break;
      case 'step1_inputSelection':
        $rules = [
          'selection'         => 'required',
          'selection.title'   => 'required',
          'selection.keyword' => 'required',
        ];
        break;
      case 'step2_doResearch':
        $rules = [
          'uuid' => 'required'
        ];
        break;
      case 'step3_doWrite':
        $rules = [
          'uuid'             => 'required',
          'research_summary' => 'required',
        ];
        break;
      case 'detail':
        $rules = [
          'id_article' => 'required|numeric'
        ];
        break;
      default:
        throw new Exception('Can\'t validate some field', 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      $errors = $validation->errors()->toArray();
      throw new Exception(json_encode(['validation_errors' => $errors]), 400);
    }

    return true;
  }
}

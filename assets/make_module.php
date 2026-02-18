#!/usr/bin/env php
<?php

if ($argc < 2) {
  echo "Usage: composer make:module <ModuleName>\n";
  exit(1);
}

$moduleName  = ucfirst($argv[1]);
$baseDir     = __DIR__ . '/../src';
$directories = [
  "$baseDir/controllers",
  "$baseDir/services",
  "$baseDir/validators"
];
foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

//! Create Controller
$controllerPath = "$baseDir/controllers/{$moduleName}Controller.php";
if (!file_exists($controllerPath)) {

  $controllerCode = <<<EOD
  <?php

  namespace App\Controllers;

  use Psr\Http\Message\ResponseInterface as Response;
  use Psr\Http\Message\ServerRequestInterface as Request;
  use Exception;
  use App\Helpers\General;
  use App\Services\\{$moduleName}Service;
  use App\Validators\\{$moduleName}Validator;

  class {$moduleName}Controller
  {
    private \$helper;
    private \$service;
    private \$validator;

    public function __construct()
    {
      \$this->helper = new General;
      \$this->service = new {$moduleName}Service;
      \$this->validator = new {$moduleName}Validator;
    }

    public function list(Request \$request, Response \$response)
    {
      \$input = \$request->getQueryParams();
      try {
        //* VALIDATOR
        \$this->validator->validate('list', \$input);
        //* SERVICES
        \$data = \$this->service->list(\$input);

        \$result = [
          'data'    => \$data,
          'message' => 'Ok',
          'status'  => 200
        ];
      }
      catch (Exception \$e) {
        \$result = [
          'message' => 'Error: ' . \$e->getMessage(),
          'status'  => \$this->helper->normalizeHttpStatus(\$e->getCode())
        ];
      }

      \$response->getBody()->write(json_encode(\$result, JSON_UNESCAPED_UNICODE));
      return \$response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(\$result['status']);
    }

    public function detail(Request \$request, Response \$response)
    {
      \$input = \$request->getQueryParams();
      try {
        //* VALIDATOR
        \$this->validator->validate('detail', \$input);
        //* SERVICES
        \$data = \$this->service->detail(\$input);

        \$result = [
          'data'    => \$data,
          'message' => 'Ok',
          'status'  => 200
        ];
      }
      catch (Exception \$e) {
        \$result = [
          'message' => 'Error: ' . \$e->getMessage(),
          'status'  => \$this->helper->normalizeHttpStatus(\$e->getCode())
        ];
      }

      \$response->getBody()->write(json_encode(\$result, JSON_UNESCAPED_UNICODE));
      return \$response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(\$result['status']);
    }

    public function add(Request \$request, Response \$response)
    {
      \$input = \$request->getParsedBody();
      \$user  = [
        'user'       => \$request->getAttribute('user'),
        'user_agent' => \$request->getHeaderLine('User-Agent'),
        'ip_address' => \$this->helper->getClientIp(\$request),
      ];
      try {
        //* VALIDATOR
        \$this->validator->validate('add', \$input);
        //* SERVICES
        \$data = \$this->service->add(\$input, \$user);

        \$result = [
          'data'    => \$data,
          'message' => 'Ok',
          'status'  => 200
        ];
      }
      catch (Exception \$e) {
        \$result = [
          'message' => 'Error: ' . \$e->getMessage(),
          'status'  => \$this->helper->normalizeHttpStatus(\$e->getCode())
        ];
      }

      \$response->getBody()->write(json_encode(\$result, JSON_UNESCAPED_UNICODE));
      return \$response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(\$result['status']);
    }

    public function edit(Request \$request, Response \$response)
    {
      \$input = \$request->getParsedBody();
      \$user  = [
        'user'       => \$request->getAttribute('user'),
        'user_agent' => \$request->getHeaderLine('User-Agent'),
        'ip_address' => \$this->helper->getClientIp(\$request),
      ];
      try {
        //* VALIDATOR
        \$this->validator->validate('edit', \$input);
        //* SERVICES
        \$data = \$this->service->edit(\$input, \$user);

        \$result = [
          'data'    => \$data,
          'message' => 'Ok',
          'status'  => 200
        ];
      }
      catch (Exception \$e) {
        \$result = [
          'message' => 'Error: ' . \$e->getMessage(),
          'status'  => \$this->helper->normalizeHttpStatus(\$e->getCode())
        ];
      }

      \$response->getBody()->write(json_encode(\$result, JSON_UNESCAPED_UNICODE));
      return \$response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(\$result['status']);
    }

    public function drop(Request \$request, Response \$response)
    {
      \$input = \$request->getParsedBody();
      \$user  = [
        'user'       => \$request->getAttribute('user'),
        'user_agent' => \$request->getHeaderLine('User-Agent'),
        'ip_address' => \$this->helper->getClientIp(\$request),
      ];
      try {
        //* VALIDATOR
        \$this->validator->validate('drop', \$input);
        //* SERVICES
        \$data = \$this->service->drop(\$input, \$user);

        \$result = [
          'data'    => \$data,
          'message' => 'Ok',
          'status'  => 200
        ];
      }
      catch (Exception \$e) {
        \$result = [
          'message' => 'Error: ' . \$e->getMessage(),
          'status'  => \$this->helper->normalizeHttpStatus(\$e->getCode())
        ];
      }

      \$response->getBody()->write(json_encode(\$result, JSON_UNESCAPED_UNICODE));
      return \$response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(\$result['status']);
    }
  }
  EOD;

  file_put_contents($controllerPath, $controllerCode);

  echo "✅ Created: $controllerPath\n";
}

//! Create Service
$servicePath = "$baseDir/services/{$moduleName}Service.php";
if (!file_exists($servicePath)) {

  $serviceCode = <<<EOD
  <?php

  namespace App\Services;

  use Exception;
  use App\Models\Model;
  use App\Helpers\General;
  use App\Lib\Cloudinary;

  class {$moduleName}Service
  {
    private \$helper;
    private \$cloudinary;

    public function __construct()
    {
      \$this->helper = new General;
      \$this->cloudinary = new Cloudinary;
    }

    private function checkExist(array \$input)
    {
      \$check = Model::where('id', (int) \$input['id'])->first();
      if (!\$check) {
        throw new Exception('Not Found', 404);
      }
      return \$check;
    }

    public function list(array \$input)
    {
      \$data  = [];
      \$query = Model::where('id', (int) \$input['id'])->get();

      foreach (\$query as \$row) {
        \$data[] = \$row->toArray();
      }

      return \$data;
    }

    public function detail(array \$input)
    {
      \$detail = \$this->checkExist(\$input);
      \$data   = \$detail->toArray();
      return \$data;
    }

    public function add(array \$input, array \$user)
    {
      DB::beginTransaction();
      try {
        //? PICTURE UPLOAD
          \$picture_id = \$this->helper->pictureUpload(\$this->cloudinary, \$input['picture'] ?? null);
        //? PICTURE UPLOAD

        //? INSERT TO table
          \$insert = Model::create([
            'id_picture' => \$picture_id,
            'column'     => (int) \$input['field'],
          ]);
          \$insertId = \$insert->id;
        //? INSERT TO table

        //? LOG Record
          \$this->helper->addLog(\$user, 'table_name', \$insertId, 'INSERT');
        //? LOG Record

        DB::commit();
      }
      catch (Exception \$e) {
        DB::rollBack();
        throw \$e;
      }
    }

    public function edit(array \$input, array \$user)
    {
      \$check = \$this->checkExist(\$input);
      DB::beginTransaction();
      try {
        //? PICTURE UPLOAD
          \$picture_id = \$this->helper->pictureUpload(\$this->cloudinary, \$input['picture'] ?? null);
        //? PICTURE UPLOAD

        //? UPDATE ON table

          \$data = [
            'column' => (int) \$input['field'],
          ];
          if (!empty(\$picture_id)) {
            \$data['id_picture'] = \$picture_id;
          }
          \$update = Model::where('id', (int) \$input['id'])->update();
        //? UPDATE ON table

        //? LOG Record
          \$this->helper->addLog(\$user, 'table_name', (int) \$input['id'], 'UPDATE');
        //? LOG Record

        DB::commit();
      }
      catch (Exception \$e) {
        DB::rollBack();
        throw \$e;
      }
    }

    public function drop(array \$input, array \$user)
    {
      \$check = \$this->checkExist(\$input);

      DB::beginTransaction();
      try {
        //? DELETE table
          \$check->delete();
        //? DELETE table

        //? LOG Record
          \$this->helper->addLog(\$user, 'table_name', (int) \$check['id'], 'DELETE');
        //? LOG Record

        DB::commit();
      }
      catch (Exception \$e) {
        DB::rollBack();
        throw \$e;
      }
    }
  }
  EOD;

  file_put_contents($servicePath, $serviceCode);

  echo "✅ Created: $servicePath\n";
}

//! Create Validator
$validatorPath = "$baseDir/validators/{$moduleName}Validator.php";
if (!file_exists($validatorPath)) {

  $validatorCode = <<<EOD
  <?php

  namespace App\Validators;

  use Exception;
  use App\Helpers\General;

  class {$moduleName}Validator
  {
    private \$helper;

    public function __construct()
    {
      \$this->helper = new General;
    }

    public function validate(string \$type, array \$input)
    {
      switch (\$type) {
        case 'list':
          \$rules = [
            'field' => 'required|string|not_empty',
          ];
        break;
        case 'detail':
          \$rules = [
            'id'    => 'required|integer|not_empty',
            'field' => 'required|string|not_empty',
          ];
        break;
        case 'add':
          \$rules = [
            'field' => 'required|string|not_empty',
          ];
        break;
        case 'edit':
          \$rules = [
            'id'    => 'required|integer|not_empty',
            'field' => 'required|string|not_empty',
          ];
        break;
        case 'drop':
          \$rules = [
            'id' => 'required|integer|not_empty'
          ];
        break;
        default:
          throw new Exception('Can\\'t validate some field', 400);
        break;
      }
      \$this->helper->validateByRules(\$input, \$rules);
      return true;
    }
  }
  EOD;

  file_put_contents($validatorPath, $validatorCode);

  echo "✅ Created: $validatorPath\n";
}

echo "🎉 Module {$moduleName} created successfully!\n";

#!/usr/bin/env php
<?php

if ($argc < 3) {
  echo "Usage: php assets/make_module.php <admin|client> <ModuleName>\n";
  echo "   or: composer make:module:<admin|client> <ModuleName>\n";
  exit(1);
}

$type = strtolower($argv[1]);
if (!in_array($type, ['admin', 'client'])) {
  echo "Type must be 'admin' or 'client'\n";
  exit(1);
}

$moduleName    = ucfirst($argv[2]);
$namespaceType = ucfirst($type);
$dirType       = strtolower($type);

$baseDir     = __DIR__ . '/../src';
$directories = [
  "$baseDir/controllers/$dirType",
  "$baseDir/services/$dirType",
  "$baseDir/validators/$dirType"
];
foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

//! Create Controller
$controllerPath = "$baseDir/controllers/$dirType/{$moduleName}Controller.php";
if (!file_exists($controllerPath)) {

  $controllerCode = <<<EOD
  <?php

  namespace App\Controllers\\{$namespaceType};

  use Psr\Http\Message\ResponseInterface as Response;
  use Psr\Http\Message\ServerRequestInterface as Request;
  use Exception;
  use App\Helpers\General;
  use App\Helpers\ApiResponse;
  use App\Services\\{$namespaceType}\\{$moduleName}Service;
  use App\Validators\\{$namespaceType}\\{$moduleName}Validator;

  class {$moduleName}Controller
  {
    public function __construct(
      private General \$helper,
      private {$moduleName}Service \$service,
      private {$moduleName}Validator \$validator,
      private ApiResponse \$apiResponse
    ) {}

    public function list(Request \$request, Response \$response)
    {
      try {
        //* INPUT
        \$input = \$request->getQueryParams();

        //* VALIDATOR
        \$this->validator->validate('list', \$input);

        //* SERVICE
        \$data = \$this->service->list(\$input);

        return \$this->apiResponse->success(\$response, \$data);
      }
      catch (Exception \$e) {
        return \$this->apiResponse->handleException(\$response, \$e);
      }
    }

    public function detail(Request \$request, Response \$response)
    {
      try {
        //* INPUT
        \$input = \$request->getQueryParams();

        //* VALIDATOR
        \$this->validator->validate('detail', \$input);

        //* SERVICE
        \$data = \$this->service->detail(\$input);

        return \$this->apiResponse->success(\$response, \$data);
      }
      catch (Exception \$e) {
        return \$this->apiResponse->handleException(\$response, \$e);
      }
    }

    public function add(Request \$request, Response \$response)
    {
      try {
        //* INPUT
        \$input = \$request->getParsedBody();

        //* VALIDATOR
        \$this->validator->validate('add', \$input);

        //* SERVICE
        \$data = \$this->service->add(\$input, \$this->helper->getUserContext(\$request));

        return \$this->apiResponse->success(\$response, \$data);
      }
      catch (Exception \$e) {
        return \$this->apiResponse->handleException(\$response, \$e);
      }
    }

    public function edit(Request \$request, Response \$response)
    {
      try {
        //* INPUT
        \$input = \$request->getParsedBody();

        //* VALIDATOR
        \$this->validator->validate('edit', \$input);

        //* SERVICE
        \$data = \$this->service->edit(\$input, \$this->helper->getUserContext(\$request));

        return \$this->apiResponse->success(\$response, \$data);
      }
      catch (Exception \$e) {
        return \$this->apiResponse->handleException(\$response, \$e);
      }
    }

    public function drop(Request \$request, Response \$response)
    {
      try {
        //* INPUT
        \$input = \$request->getParsedBody();

        //* VALIDATOR
        \$this->validator->validate('drop', \$input);

        //* SERVICE
        \$data = \$this->service->drop(\$input, \$this->helper->getUserContext(\$request));

        return \$this->apiResponse->success(\$response, \$data);
      }
      catch (Exception \$e) {
        return \$this->apiResponse->handleException(\$response, \$e);
      }
    }
  }
  EOD;

  file_put_contents($controllerPath, $controllerCode);

  echo "✅ Created: $controllerPath\n";
}

//! Create Service
$servicePath = "$baseDir/services/$dirType/{$moduleName}Service.php";
if (!file_exists($servicePath)) {

  $serviceCode = <<<EOD
  <?php

  namespace App\Services\\{$namespaceType};

  use Exception;
  use Illuminate\Database\Capsule\Manager as DB;
  use App\Helpers\General;
  use App\Lib\Cloudinary;
  use App\Models\Model;

  class {$moduleName}Service
  {
    public function __construct(
      private General \$helper,
      private Cloudinary \$cloudinary
    ) {}

    private function checkExist(array \$input)
    {
      \$check = Model::where('id', (int) \$input['id'])->first();
      if (!\$check) {
        throw new Exception('Not Found', 404);
      }
      return \$check;
    }

    public function list(array \$input = [])
    {
      \$data  = [];
      \$query = Model::where('id', (int) (\$input['id'] ?? 0))->get();

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
          \$update = Model::where('id', (int) \$input['id'])->update(\$data);
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
$validatorPath = "$baseDir/validators/$dirType/{$moduleName}Validator.php";
if (!file_exists($validatorPath)) {

  $validatorCode = <<<EOD
  <?php

  namespace App\Validators\\{$namespaceType};

  use Exception;
  use Rakit\Validation\Validator;
  use App\Helpers\General;

  class {$moduleName}Validator
  {
    public function __construct(
      private Validator \$validator,
      private General \$helper
    ) {}

    public function validate(string \$type, array \$input)
    {
      switch (\$type) {
        case 'list':
          \$rules = [
            'field' => 'required',
          ];
          break;
        case 'detail':
          \$rules = [
            'id'    => 'required|numeric',
            'field' => 'required',
          ];
          break;
        case 'add':
          \$rules = [
            'field' => 'required',
          ];
          break;
        case 'edit':
          \$rules = [
            'id'    => 'required|numeric',
            'field' => 'required',
          ];
          break;
        case 'drop':
          \$rules = [
            'id' => 'required|numeric'
          ];
          break;
        default:
          throw new Exception('Can\\'t validate some field', 400);
      }

      \$validation = \$this->validator->make(\$input, \$rules);
      \$validation->validate();

      if (\$validation->fails()) {
        \$errors = \$validation->errors()->toArray();
        throw new Exception(json_encode(['validation_errors' => \$errors]), 400);
      }

      return true;
    }
  }
  EOD;

  file_put_contents($validatorPath, $validatorCode);

  echo "✅ Created: $validatorPath\n";
}

echo "🎉 Module {$namespaceType}\\{$moduleName} created successfully!\n";

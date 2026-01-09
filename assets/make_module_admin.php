#!/usr/bin/env php
<?php

if ($argc < 2) {
  echo "Usage: composer make:module:admin <ModuleName>\n";
  exit(1);
}

$moduleName  = ucfirst($argv[1]);
$baseDir     = __DIR__ . '/../src';
$directories = [
  "$baseDir/controllers/admin",
  "$baseDir/services/admin",
  "$baseDir/validators/admin"
];
foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

//! Create Controller
$controllerPath = "$baseDir/controllers/admin/{$moduleName}Controller.php";
if (!file_exists($controllerPath)) {

  $controllerCode = <<<EOD
  <?php

  namespace App\Controllers\Admin;

  use Psr\Http\Message\ResponseInterface as Response;
  use Psr\Http\Message\ServerRequestInterface as Request;
  use Exception;
  use App\Helpers\General;
  use App\Services\Admin\\{$moduleName}Service;
  use App\Validators\Admin\\{$moduleName}Validator;

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
        //* VALIDATION
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
        //* VALIDATION
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
        //* VALIDATION
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
        //* VALIDATION
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
        //* VALIDATION
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
$servicePath = "$baseDir/services/admin/{$moduleName}Service.php";
if (!file_exists($servicePath)) {

  $serviceCode = <<<EOD
  <?php

  namespace App\Services\Admin;

  use Exception;
  use App\Models\\{$moduleName};
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

    public function list(array \$input)
    {
      return {$moduleName}::orderBy('id', 'desc')->get()->toArray();
    }

    public function detail(array \$input)
    {
      return {$moduleName}::find(\$param['id']);
    }

    public function add(array \$input, array \$user)
    {
      return {$moduleName}::create(\$data);
    }

    public function edit(array \$input, array \$user)
    {
      \$item = {$moduleName}::find(\$data['id']);
      \$item->update(\$data);
      return \$item;
    }

    public function drop(array \$input, array \$user)
    {
      return {$moduleName}::destroy(\$param['id']);
    }
  }
  EOD;

  file_put_contents($servicePath, $serviceCode);

  echo "✅ Created: $servicePath\n";
}

//! Create Validator
$validatorPath = "$baseDir/validators/admin/{$moduleName}Validator.php";
if (!file_exists($validatorPath)) {

  $validatorCode = <<<EOD
  <?php

  namespace App\Validators\Admin;

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
            'id' => 'required|integer|not_empty',
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
            'id' => 'required|integer|not_empty',
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

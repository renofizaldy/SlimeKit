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
  use App\Lib\Database;
  use App\Helpers\General;
  use App\Lib\Cloudinary;

  class {$moduleName}Service
  {
    private \$db;
    private \$helper;
    private \$cloudinary;
    private \$tableMain = 'table';

    public function __construct()
    {
      \$this->db = (new Database())->getConnection();
      \$this->helper = new General;
      \$this->cloudinary = new Cloudinary;
    }

    private function checkExist(array \$input)
    {
      \$check = \$this->db->createQueryBuilder()
        ->select('*')
        ->from(\$this->tableMain)
        ->where('id = :id')
        ->setParameter('id', (int) \$input['id'])
        ->fetchAssociative();
      if (!\$check) {
        throw new Exception('Not Found', 404);
      }
      return \$check;
    }

    public function list(array \$input)
    {
      \$data = [];

      \$query = \$this->db->createQueryBuilder()
        ->select('*')
        ->from(\$this->tableMain)
        ->where('id = :id')
        ->setParameter('id', (int) \$input['id'])
        ->executeQuery()
        ->fetchAllAssociative();

      if (!empty(\$query)) {
        foreach (\$query as \$row) {
          \$data[] = [
            'field' => \$row['column']
          ];
        }
      }

      return \$data;
    }

    public function detail(array \$input)
    {
      \$data = [];
      \$event = \$this->checkExist(\$input);

      \$query = \$this->db->createQueryBuilder()
        ->select('*')
        ->from(\$this->tableMain)
        ->leftJoin(
          \$this->tableMain,
          'table_to_join',
          'table_to_join',
          \$this->tableMain.'.id = table_to_join.id'
        )
        ->where('id = :id')
        ->setParameter('id', (int) \$input['id'])
        ->executeQuery()
        ->fetchAssociative();

      if (!empty(\$query)) {
        \$data = \$query;
      }

      return \$data;
    }

    public function add(array \$input, array \$user)
    {
      \$this->db->beginTransaction();
      try {
        //? PICTURE UPLOAD
          \$picture_id = \$this->helper->pictureUpload(\$this->db, \$this->cloudinary, \$input['picture'] ?? null);
        //? PICTURE UPLOAD

        //? INSERT TO table
          \$this->db->createQueryBuilder()
            ->insert(\$this->tableMain)
            ->values([
              'id_picture' => ':id_picture',
              'column'     => ':field',
            ])
            ->setParameters([
              'id_picture' => \$picture_id,
              'field' => (int) \$input['field'],
            ]);
            ->executeStatement();
        //? INSERT TO table

        //? LOG Record
          \$this->helper->addLog(\$this->db, \$user, \$this->tableMain, \$this->db->lastInsertId(), 'INSERT');
        //? LOG Record

        \$this->db->commit();
      }
      catch (Exception \$e) {
        if (\$this->db->isTransactionActive()) {
          \$this->db->rollBack();
        }
        throw \$e;
      }
    }

    public function edit(array \$input, array \$user)
    {
      \$this->db->beginTransaction();
      try {
        //? PICTURE UPLOAD
          \$picture_id = \$this->helper->pictureUpload(\$this->db, \$this->cloudinary, \$input['picture'] ?? null);
        //? PICTURE UPLOAD

        //? UPDATE ON table
          \$update = \$this->db->createQueryBuilder()
            ->update(\$this->tableMain)
            ->set('column', ':field')
            ->where('id = :id')
            ->setParameters([
              'id'    => (int) \$input['id'],
              'field' => (int) \$input['field'],
            ]);
          if (!empty(\$picture_id)) {
            \$update->set('id_picture', ':id_picture')->setParameter('id_picture', \$picture_id);
          }
          \$update->executeStatement();
        //? UPDATE ON table

        //? LOG Record
          \$this->helper->addLog(\$this->db, \$user, \$this->tableMain, (int) \$input['id'], 'UPDATE');
        //? LOG Record

        \$this->db->commit();
      }
      catch (Exception \$e) {
        if (\$this->db->isTransactionActive()) {
          \$this->db->rollBack();
        }
        throw \$e;
      }
    }

    public function drop(array \$input, array \$user)
    {
      \$check = \$this->checkExist(\$input);

      \$this->db->beginTransaction();
      try {
        //? DELETE table
          \$this->db->createQueryBuilder()
            ->delete(\$this->tableMain)
            ->where('id = :id')
            ->setParameter('id', \$check['id'])
            ->executeStatement();
        //? DELETE table

        //? LOG Record
          \$this->helper->addLog(\$this->db, \$user, \$this->tableMain, (int) \$check['id'], 'DELETE');
        //? LOG Record

        \$this->db->commit();
      }
      catch (Exception \$e) {
        if (\$this->db->isTransactionActive()) {
          \$this->db->rollBack();
        }
        throw \$e;
      }
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

  class {$moduleName}Validator
  {
    public function validate(string \$type, array \$input)
    {
      switch (\$type) {
        case 'list':
          \$rules = [
            'field',
          ];
        break;
        case 'detail':
          \$rules = [
            'id',
            'field',
          ];
        break;
        case 'add':
          \$rules = [
            'field',
          ];
        break;
        case 'edit':
          \$rules = [
            'id',
            'field',
          ];
        break;
        case 'drop':
          \$rules = [
            'id'
          ];
        break;
        default:
          throw new Exception('Can\\'t validate some field', 400);
        break;
      }
      \$this->validateByRules($input, $rules);
      return true;
    }

    private function validateByRules(array \$input, array \$rules)
    {
      foreach (\$rules as \$field => \$ruleString) {
        \$rulesArray = explode('|', \$ruleString);

        foreach (\$rulesArray as \$rule) {
          switch (\$rule) {
            case 'required':
              if (!array_key_exists(\$field, \$input)) {
                throw new \Exception("Field '{\$field}' is required.", 400);
              }
              break;

            case 'not_empty':
              if (
                !isset(\$input[\$field]) ||
                (is_string(\$input[\$field]) && trim(\$input[\$field]) === '') ||
                (is_array(\$input[\$field]) && count(\$input[\$field]) === 0)
              ) {
                throw new \Exception("Field '{\$field}' cannot be empty.", 400);
              }
              break;

            case 'string':
              if (isset(\$input[\$field]) && !is_string(\$input[\$field])) {
                throw new \Exception("Field '{\$field}' must be a string.", 400);
              }
              break;

            case 'integer':
              if (isset(\$input[\$field]) && !filter_var(\$input[\$field], FILTER_VALIDATE_INT)) {
                throw new \Exception("Field '{\$field}' must be an integer.", 400);
              }
              break;

            case 'date':
              if (isset(\$input[\$field]) && strtotime(\$input[\$field]) === false) {
                throw new \Exception("Field '{\$field}' must be a valid date.", 400);
              }
              break;

            default:
              throw new \Exception("Unknown validation rule '{\$rule}' for field '{\$field}'", 500);
          }
        }
      }
    }
  }
  EOD;

  file_put_contents($validatorPath, $validatorCode);

  echo "✅ Created: $validatorPath\n";
}

echo "🎉 Module {$moduleName} created successfully!\n";

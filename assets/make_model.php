#!/usr/bin/env php
<?php

if ($argc < 2) {
  echo "Usage: composer make:model <ModuleName>\n";
  exit(1);
}

$moduleName  = ucfirst($argv[1]);
$baseDir     = __DIR__ . '/../src';
$directories = [
  "$baseDir/models/",
];
foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

//! Create Controller
$modelPath = "$baseDir/models/{$moduleName}.php";
if (!file_exists($modelPath)) {

  $modelCode = <<<EOD
  <?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Model;

  class {$moduleName} extends Model {
    protected \$table = 'table_name';
    protected \$guarded = ['id'];
    public \$timestamps = true; // If created_at & updated_at
  }
  EOD;

  file_put_contents($modelPath, $modelCode);

  echo "✅ Created: $modelPath\n";
}
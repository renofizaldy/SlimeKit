<?php
require './vendor/autoload.php';
use Doctrine\Migrations\Configuration\Migration\PhpFile;
$config = new PhpFile('migrations.php');
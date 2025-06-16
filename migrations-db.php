<?php
use App\Lib\Database;
$db = new Database();
return $db->getConnection();
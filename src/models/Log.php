<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model {
  protected $table = 'tb_log';
  protected $guarded = ['id'];
  public $timestamps = true;
  protected $casts = [
    'changes' => 'array',
  ];
}
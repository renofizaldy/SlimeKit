<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Picture extends Model {
  protected $table = 'tb_picture';
  protected $guarded = ['id'];
  public $timestamps = false;
  const CREATED_AT = 'created_at';
  const UPDATED_AT = null;
}
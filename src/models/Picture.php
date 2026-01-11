<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Picture extends Model {
  protected $table = 'tb_picture';
  protected $guarded = ['id'];

  // If created_at & updated_at
  public $timestamps = true;
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cronhooks extends Model {
  protected $table = 'tb_cronhooks';
  protected $guarded = ['id'];

  // If created_at & updated_at
  public $timestamps = true;
}
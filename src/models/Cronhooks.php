<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cronhooks extends Model {
  protected $table = 'tb_cronhooks';
  protected $guarded = ['id'];
  public $timestamps = false;

  public function article() {
    return $this->belongsTo(Article::class, 'id_parent');
  }
}
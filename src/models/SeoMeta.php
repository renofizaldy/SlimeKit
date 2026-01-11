<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model {
  protected $table = 'tb_seo_meta';
  protected $guarded = ['id'];

  // If created_at & updated_at
  public $timestamps = true;
}
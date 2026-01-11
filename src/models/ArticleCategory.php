<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model {
  protected $table = 'tb_article_category';
  protected $guarded = ['id'];

  // If created_at & updated_at
  public $timestamps = true;
}
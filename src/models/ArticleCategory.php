<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model {
  protected $table = 'tb_article_category';
  protected $guarded = ['id'];
  public $timestamps = true;

  public function articles() {
    return $this->hasMany(Article::class, 'id_category');
  }

  public function seoMeta() {
    return $this->hasOne(SeoMeta::class, 'id_parent')->where('type', 'article_category');
  }
}
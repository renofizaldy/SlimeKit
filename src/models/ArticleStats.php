<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleStats extends Model
{
  protected $table = 'tb_article_stats';
  protected $guarded = ['id'];
  public $timestamps = true;
}

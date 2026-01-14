<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentFAQ extends Model {
  protected $table = 'tb_content_faq';
  protected $guarded = ['id'];
  public $timestamps = true;
}
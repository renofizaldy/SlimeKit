<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentContact extends Model {
  protected $table = 'tb_content_contact';
  protected $guarded = ['id'];
  public $timestamps = true;
}
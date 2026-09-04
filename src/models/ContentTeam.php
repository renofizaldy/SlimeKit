<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTeam extends Model
{
  protected $table = 'tb_content_team';
  protected $guarded = ['id'];
  public $timestamps = true;

  public function picture()
  {
    return $this->belongsTo(Picture::class, 'id_picture');
  }
}

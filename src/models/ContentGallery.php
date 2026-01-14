<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentGallery extends Model {
  protected $table = 'tb_content_gallery';
  protected $guarded = ['id'];
  public $timestamps = true;

  public function picture() {
    return $this->belongsTo(Picture::class, 'id_picture');
  }
}
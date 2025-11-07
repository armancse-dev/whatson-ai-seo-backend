<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;
    protected $fillable = ['project_id','keyword','search_volume','difficulty','cluster_group'];
    public function user(){ return $this->belongsTo(User::class); }
    public function keywords(){ return $this->hasMany(Keyword::class); }
    public function reports(){ return $this->hasMany(Report::class); }
}

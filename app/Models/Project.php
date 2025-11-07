<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'project_name', 'website_url'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}

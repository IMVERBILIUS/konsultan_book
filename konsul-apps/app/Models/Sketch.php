<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sketch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'author',
        'thumbnail',
        'status',
        'views',
        'content',
    ];

    /**
     * Get the user that owns the sketch.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Opsional: Accessor untuk mendapatkan URL thumbnail
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        return null;
    }
}

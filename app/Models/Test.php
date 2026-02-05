<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'description',
        'duration_minutes',
        'total_questions',
        'passing_score',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function sections()
    {
        return $this->hasMany(TestSection::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    
}

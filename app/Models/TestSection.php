<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_id',
        'title',
        'content',
        'audio_url',
        'image_url',
        'audio_duration',
        'order',
        'question_count'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }
    
}

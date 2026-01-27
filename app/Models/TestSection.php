<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'title',
        'content',
        'audio_url',
        'audio_duration',
        'order',
        'question_count'
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }
    
}

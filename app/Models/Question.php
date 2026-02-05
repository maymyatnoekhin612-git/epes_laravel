<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_section_id',
        'type',
        'question_text',
        'options',
        'correct_answers',
        'points',
        'order',
        'metadata'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'metadata' => 'array'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function section()
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

}

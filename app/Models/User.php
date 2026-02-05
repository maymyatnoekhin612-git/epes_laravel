<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verification_code',
        'email_verification_code_expires_at',
        'email_verification_attempts',
        'password_reset_code',
        'password_reset_code_expires_at',
        'password_reset_attempts'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'password_reset_code'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_verification_code_expires_at' => 'datetime',
            'password_reset_code_expires_at' => 'datetime'
        ];
    }

    public function generateVerificationCode()
    {
        $this->email_verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->email_verification_code_expires_at = Carbon::now()->addMinutes(15);
        $this->email_verification_attempts = 0;
        $this->save();
        
        return $this->email_verification_code;
    }

     public function generatePasswordResetCode()
    {
        $this->password_reset_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->password_reset_code_expires_at = Carbon::now()->addMinutes(15);
        $this->password_reset_attempts = 0;
        $this->save();
        
        return $this->password_reset_code;
    }

    public function isVerificationCodeValid($code)
    {
        if ($this->email_verification_attempts >= 3) {
            return false;
        }
        
        return $this->email_verification_code === $code && 
               $this->email_verification_code_expires_at > Carbon::now();
    }

    public function isPasswordResetCodeValid($code)
    {
        if ($this->password_reset_attempts >= 3) {
            return false;
        }
        
        return $this->password_reset_code === $code && 
               $this->password_reset_code_expires_at > Carbon::now();
    }

    public function incrementVerificationAttempts()
    {
        $this->email_verification_attempts++;
        $this->save();
    }

    public function incrementPasswordResetAttempts()
    {
        $this->password_reset_attempts++;
        $this->save();
    }

    public function clearVerificationCode()
    {
        $this->email_verification_code = null;
        $this->email_verification_code_expires_at = null;
        $this->email_verification_attempts = 0;
        $this->save();
    }

    public function clearPasswordResetCode()
    {
        $this->password_reset_code = null;
        $this->password_reset_code_expires_at = null;
        $this->password_reset_attempts = 0;
        $this->save();
    }
    
    public function createdTests()
    {
        return $this->hasMany(Test::class, 'user_id');
    }

    public function createdSections()
    {
        return $this->hasMany(TestSection::class, 'user_id');
    }

    public function createdQuestions()
    {
        return $this->hasMany(Question::class, 'user_id');
    }
    
    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }
}

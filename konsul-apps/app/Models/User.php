<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Import this trait

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // Add HasApiTokens

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Tambahkan 'role'
        'google_id', // Tambahkan 'google_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    // --- Relasi ke model lain ---

    // Relasi One-to-Many dengan Article (user bisa jadi author banyak artikel)
    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    // Relasi One-to-Many dengan Comment (user bisa membuat banyak komentar)
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Relasi One-to-Many dengan ConsultationBooking (user bisa membuat banyak booking)
    public function consultationBookings()
    {
        return $this->hasMany(ConsultationBooking::class);
    }

    // Relasi One-to-Many dengan Invoice (user bisa memiliki banyak invoice)
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Relasi One-to-One dengan UserProfile (user memiliki satu profil tambahan)
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // Relasi One-to-Many dengan Sketch (user bisa membuat banyak sketsa)
    public function sketches()
    {
        return $this->hasMany(Sketch::class);
    }

    // Relasi One-to-Many dengan ReferralCode (user bisa membuat banyak kode referral)
    public function createdReferralCodes()
    {
        return $this->hasMany(ReferralCode::class, 'created_by');
    }
}

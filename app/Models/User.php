<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'clerk_user_id',
        'google_id',
        'avatar',
        'phone',
        'birth_date',
        'gender',
        'address',
        'address_number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'country',
        'clerk_metadata',
        'marketing_emails',
        'first_login_at',
        'last_login_at',
        'customer_type',
        'document_type',
        'document_number',
    ];

    /**
     * Boot method to set default password for Clerk users
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            // If it's a Clerk user without password, set a random one
            if ($user->clerk_user_id && !$user->password) {
                $user->password = bcrypt(Str::random(32));
            }
        });
    }

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'clerk_metadata' => 'array',
            'marketing_emails' => 'boolean',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get or create cart for this user
     */
    public function getOrCreateCart()
    {
        if (!$this->cart) {
            $cart = $this->cart()->create([]);
            $this->load('cart'); // Reload the relationship
            return $cart;
        }
        return $this->cart;
    }

    /**
     * Check if user is a Clerk user (authenticated via Clerk)
     */
    public function isClerkUser(): bool
    {
        return !empty($this->clerk_user_id);
    }

    /**
     * Check if user is a Google user (authenticated via Google)
     */
    public function isGoogleUser(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * Get user's complete address as string
     */
    public function getFullAddressAttribute(): ?string
    {
        if (!$this->address) {
            return null;
        }

        $parts = array_filter([
            $this->address . ($this->address_number ? ', ' . $this->address_number : ''),
            $this->complement,
            $this->neighborhood,
            $this->city . ($this->state ? ' - ' . $this->state : ''),
            $this->zip_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        
        // Set first login if not set
        if (!$this->first_login_at) {
            $this->first_login_at = now();
        }
        
        $this->save();
    }

    /**
     * Scope for Clerk users
     */
    public function scopeClerkUsers($query)
    {
        return $query->whereNotNull('clerk_user_id');
    }

    /**
     * Scope for users with complete profile
     */
    public function scopeCompleteProfile($query)
    {
        return $query->whereNotNull('phone')
                    ->whereNotNull('address')
                    ->whereNotNull('city')
                    ->whereNotNull('state')
                    ->whereNotNull('zip_code');
    }

    /**
     * Check if user has complete address
     */
    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && 
               !empty($this->city) && 
               !empty($this->state) && 
               !empty($this->zip_code);
    }

    /**
     * Get total orders value
     */
    public function getTotalOrdersValue(): float
    {
        return $this->orders()->sum('total_amount');
    }

    /**
     * Get orders count
     */
    public function getOrdersCount(): int
    {
        return $this->orders()->count();
    }
}

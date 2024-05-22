<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Mail\TwoFAMail;
use App\Models\{
    Advisor,
    Student,
    Admin
};
use Exception;
use Emargareten\TwoFactor\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
//use Spatie\Permission\Traits\HasRoles;
use stdClass;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
    use HasFactory, SoftDeletes, CanResetPassword, HasApiTokens, HasFactory, Notifiable;
    //use HasRoles;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'role',
        'unlockDuration',
        'logAttempts',
        'rank',
        'two_factor_status',
        'two_factor_locked',
        'two_factor_confirmed_at',
        'two_factor_method',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    private $namesLoaded = false;

    protected static $accounts = [
        'student'  => Student::class,
        'admin'    => Admin::class,
        'staff' => Staff::class,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'two_factor_locked',
        'token',
        'unlockDuration',
        'activation_token',

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        //'password' => 'hashed',
        'email_verified_at' => 'datetime',
        // 'two_factor_enabled' => 'boolean',
    ];




    /**
     * Get the phone number for SMS notifications.
     *
     * @return string
     */
    public function routeNotificationForNexmo()
    {
        return $this->phone;
    }



    /**
     * Get the email address for email notifications.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }



    public function devices()
    {
        return $this->hasMany(Device::class);
    }



    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getHashedPassword()
    {
        if (request()->has('password')) {
            $request = request();
            $password = $request->get('password');
            $old_password = $request->get('old_password');

            // check if the user is the owner, if not unset password field
            // because only the owner can change it
            $auth_id = auth()->id();
            $user_id = $this->id;

            if ($auth_id != $user_id) {
                return null;
            } else if (!$request->has('old_password')) {
                throw new Exception('Current password is required');
            }
            if (!Hash::compare($password, $this->password)) {
                throw new Exception('Passwords do not match');
            }
            return Hash::make($password);
        }

        return null;
    }

    public static function getFullnameFromRequest()
    {
        $request = request();
        $firstname = $request->get('firstname', '');
        $lastname = $request->get('lastname', '');
        $middlename = $request->get('middlename', '');

        if (!$firstname && !$lastname) {
            return null;
        }


        $fullname = [$firstname, $lastname, $middlename];

        return implode(' ', $fullname);
    }

    public function getFullnameAttribute($value)
    {
        $nameParts = explode(' ', $this->name);

        $obj = new stdClass;

        $obj->firstname = $nameParts[0];
        $obj->lastname = count($nameParts) > 1 ? $nameParts[1] : '';
        $obj->middlename = count($nameParts) > 2 ? $nameParts[2] : '';
        return $obj;
    }







    public function generateId($role, $prefix = null)
    {
        $prefix ??= strtoupper(substr($role, 0, 3));
        do {
            $randomNumber = mt_rand(10000, 99999);
            $uniqueId = "$prefix-$randomNumber";
        } while (User::where('unique_id', $uniqueId)->exists());

        return $uniqueId;
    }






    public static function store_user(array $data)
    {


        // Default rolr
        $data['role'] ??= 'student';

        if (!array_key_exists($data['role'], self::$accounts)) {
            return null;
        }

        // dd($data);

        $role = $data['role'];

        $account = self::$accounts[$role];

        // Create user account for authentification
        $authUser = User::_create($data);


        $data['id'] = $authUser->id;

        // Add User to role table
        $account::_create($data);

        return $authUser;
    }

  


    public static function active()
    {
        return auth()->user();
    }





    public static function saveUser(array $userData = [])
    {
        $user = new User();

        if (count($userData) === 0) {
            $data = request()->only($user->fillable);
        } else {
            $data = Arr::only($userData, $user->fillable);
        }


        $user = self::create($data);

        self::register($user, $data);

        return $user;
    }


    private static function register(User $user, array $data = [])
    {

        $instance = match ($user->role) {
            'admin' => new Admin(),
            default => new Student(),
        };

        if ($instance) {
            if ($fillables = $instance->getFillables()) {
                foreach ($fillables as $field) {
                    if (request()->has($field)) {
                        $instance->{$field} = request()->input($field);
                    }
                }
            }

            $instance->id = $user->id;
            $instance->save();
        }
    }

    public static function redirectToDashboardx()
    {
        $dashboard = 'login';
        if (auth()->check()) {
            $role = auth()->user()->role;

            $dashboard = $role . '.dashboard';
        }


        return redirect()->route($dashboard);
    }




    public function profile()
    {

        return match ($this->role) {
            'student' => $this->hasOne(Student::class, 'id'),
            'admin' => $this->hasOne(Admin::class, 'id'),
            'staff' => $this->hasOne(Staff::class, 'id', 'id'),
            default => null,
        };
    }





    public function student()
    {
        return $this->hasOne(Student::class, 'id', 'id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'id', 'id');
    }

    public function advisor()
    {
        return $this->hasOne(Staff::class, 'id', 'id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id');
    }











    public function courses()
    {
        return $this->hasMany(AcademicSet::class)->with('_course');
    }








    public function todos()
    {
        return $this->hasMany(Todo::class)->orderBy('created_at', 'DESC');
    }






    public function picture()
    {
        $image = $this->profile->image;
        if ($image) {
            return asset('storage/' . $image);
        }

        return asset(match ($this->profile->gender) {
            'female' => 'images/avatar-f.png',
            'male' => 'images/avatar-m.png',
            default => 'images/avatar-u.png',
        });
    }


    /**
     * Generate a unique username randomly based on the provided full name.
     *
     * @param string $fullName The full name of the user.
     * @return string The generated unique username.
     */
    public function generateUsername($fullName)
    {
        // Convert the full name to lowercase
        $lowercaseFullName = strtolower($fullName);

        // Extract first name and last name initials
        $nameParts = preg_split('/\s+/', $lowercaseFullName, -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach ($nameParts as $part) {
            $initials .= $part[0];
        }

        // Generate various combinations of initials
        $combinations = [];
        foreach ($nameParts as $part) {
            $combinations[] = $part;
        }
        foreach ($nameParts as $part) {
            $combinations[] = $initials . $part;
        }

        // Shuffle the combinations to create randomness
        shuffle($combinations);

        // Check if the username already exists, if not, return it
        foreach ($combinations as $username) {
            if (!$this->usernameExists($username)) {
                return $username;
            }
        }

        // If all generated usernames exist, append a random number
        $username = $combinations[array_rand($combinations)];
        $count = 1;
        while ($this->usernameExists($username . $count)) {
            $count++;
        }
        return $username . $count;
    }

    /**
     * Check if the username already exists in the database.
     *
     * @param string $username The username to check.
     * @return bool True if the username exists, false otherwise.
     */
    protected function usernameExists($username)
    {
        return User::where('username', $username)->exists();
    }



    /**
     * Create a new user.
     *
     * @param array $data The user data.
     * @return User The created user.
     */
    public static function createUser(array $data)
    {
        // Create a new user instance
        $user = new User($data);

        // Generate username based on the provided name
        if (array_key_exists('name', $data)) {
            $user->username = $user->generateUsername($data['name']);
        }

        // Save the user to the database
        $user->save();

        return $user;
    }





    public function notifications()
    {
        $role = $this->role;

        $notifications = Announcement::where('target', 'everyone')
            ->orWhere('target', $role . 's')
            ->orWhere(function ($query) use ($role) {
                $query->where('target', $role)
                    ->where('target_id', $this->id);
            })
            ->get();

        return $notifications;
    }


    public function incrementLogAttempts()
    {
        $this->logAttempts++;

        $this->update([
            'logAttempts' => $this->logAttempts
        ]);
    }

    public function lockAccount(int $unlockDuration)
    {
        $logAttempts = 4;
        $this->update(compact('logAttempts', 'unlockDuration'));
    }

    public function unlockAccount()
    {
        $unlockDuration = null;
        $logAttempts = 0;
        $this->update(compact('logAttempts', 'unlockDuration'));
    }


    public function isLocked()
    {
        return $this->unlockDuration && $this->unlockDuration > time();
    }



    /**2-FACTOR AUTHENTICATION */
    public function enableTwoFactorAuth()
    {
        $this->secret = $this->generateNewSecret();
        $this->save();

        // Notificate user of his choice=
        Email(new TwoFAMail(false));
        return true;
    }

    public function disableTwoFactorAuth()
    {
        $this->secret = null;
        $this->save();

        // Notificate user of his choice
        Email(new TwoFAMail(true));
        return true;
    }

    private function generateNewSecret()
    {
        return app('two-factor-auth')->generateSecretKey(); // Uses the package's helper
    }


    public function isTwoFactorEnabled()
    {
        return !empty($this->secret);
    }


    /**
     * @description Generates activation token and store it
     * to the associated account
     */
    public function generateActivationLink()
    {

        do {
            // Generate random activation token
            $token = generateToken(null, 32);

            // if exist regenerate another until unique token is generated
        } while (User::where('activation_token', $token)->exists());

        // store the generated token
        $this->update(['activation_token' => $token]);

        return route('activate-account', ['token' => $token]);
    }

    public function is_admin()
    {
        return $this->role === 'admin';
    }

    public function is_staff()
    {
        return $this->role === 'staff';
    }

    public function is_advisor()
    {
        if ($this->role !== 'staff') {
            return null;
        }
        return $this->staff->is_class_advisor;
    }

    public function is($role)
    {

        $match = match ($role) {
            'advisor' => $this->staff->is_class_advisor && $this->role == 'staff',
            'hod' => $this->staff->is_hod && $this->role == 'staff',
            default => $this->role == $role,
        };

        return $match;
    }







    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasRole($role)
    {
        return in_array($role, $this->roles->pluck('name')->toArray());
    }








    /// TWO FACTOR AUTHENTICATION

    /**
     * Increment the number of failed 2FA verification attempts.
     */
    public function incrementTwoFactorAttempts()
    {
        $this->two_factor_attempts++;
        $this->save();
    }

    /**
     * Determine if the user's account should be locked due to multiple failed 2FA attempts.
     *
     * @return bool
     */
    public function shouldLockTwoFactorAccount()
    {
        return $this->two_factor_attempts >= 5;
    }

    /**
     * Lock the user's account due to multiple failed 2FA attempts.
     */
    public function lockTwoFactorAccount()
    {
        $this->two_factor_locked = true;
        $this->save();
    }

    /**
     * Reset the number of failed 2FA verification attempts.
     */
    public function resetTwoFactorAttempts()
    {
        $this->two_factor_attempts = 0;
        $this->save();
    }

    /**
     * Verify the provided 2FA code against the stored secret.
     *
     * @param string $code The 2FA code to verify.
     * @return bool True if the code is valid, false otherwise.
     */
    public function verifyTwoFactorCode($code)
    {
        // Get the user's 2FA secret from the database
        $secret = $this->two_factor_secret;

        // Instantiate the Google2FA library
        $google2fa = new Google2FA();

        // Verify the provided code against the secret
        return $google2fa->verifyKey($secret, $code);
    }


    public function enableTwoFactor()
    {
        $this->two_factor_enabled = true;
        $google2fa = new Google2FA();
        $this->secret = $google2fa->generateSecretKey();
        $this->save();
    }

    public function disableTwoFactor()
    {
        $this->two_factor_enabled = false;
        $this->secret = null;
        $this->save();
    }

    public function verifyTwoFactor($code)
    {
        $google2fa = new Google2FA();
        $isCodeValid = $google2fa->verifyKey($this->secret, $code);
    }


    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set the default value for two_factor_enabled when creating a new user
            $user->two_factor_status = 'enabled';
        });
    }



    public function pronoun($type)
    {
        $role = $this->role;
        $gender = $this->$role->gender;

        return match ($type) {
            'his' => $gender === 'female' ? 'her' : 'his',
            'him' => $gender === 'female' ? 'her' : 'him',
            'he' => $gender === 'female' ? 'she' : 'he',
            default => 'their',
        };
    }


    public function saveProfileImagePath($imagePath)
    {
        $role = $this->role;
        $this->$role->image = $imagePath;
        $this->$role->save();
    }
}
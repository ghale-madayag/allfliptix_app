<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class UsersController extends Controller
{
    protected $creator;

    public function __construct(CreatesNewUsers $creator)
    {
        $this->creator = $creator;
    }

    public function index(){
        $currentUser = Auth::user();
        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->whereNotIn('id', [$currentUser->id, 1])
            ->get();

        $formattedData = $users->map(function ($user) {
            return (object) [
                'id' => $user->id,
                'name' => $user->name, // Ensure fname exists in your User model
                'email' => $user->email,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'), // Format date if needed
                'roles' => $user->getRoleNames()->toArray(), // Get assigned roles
                'is_verified' => $user->email_verified_at !== null, 
            ];
        });
            
        return Inertia::render('user/index',[
            'users' => $formattedData,
        ]);
    }

    public function store(Request $request, User $user)
    {

        $validatedData = $request->validate($this->getValidationRules($user)); 
        $password = Str::random(8);
        $user = $this->creator->create(array_merge($validatedData,['password'=> $password]));
        $user['password'] = $password;

       $this->sendEmailVerification($user, $request->roles, $password);

        return Redirect::back()->with('message', 'Registered Successfully. Please inform them to verify using their email address');
    }

    private function sendEmailVerification($user, $roles,$password)
    {

        if($roles=='Administrator'){
            $user->assignRole('Administrator');
            Mail::to($user)->queue(new WelcomeEmail($user, $password));
        }else {
            $user->assignRole('User');
            Mail::to($user)->queue(new WelcomeEmail($user, $password));
        }
    }

    private function getValidationRules($contact = null)
    {
        $rules = [
            'name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email'],
        ];
    
        // If $contact is provided (for update), exclude unique validation for email and phone
        if ($contact) {
            $rules['email'][] = 'unique:users,email,' . $contact->id;
        } else {
            // If it's a store operation, include unique validation for email and phone
            $rules['email'][] = 'unique:users';
        }
    
        return $rules;
    }
}

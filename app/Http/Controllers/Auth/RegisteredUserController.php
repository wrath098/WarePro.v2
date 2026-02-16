<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    // public function create(): Response
    // {
    //     return Inertia::render('Auth/Register');
    // }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'officeId' => 'required'
        ]);

        $user = User::create([
            'name' => $request->name,
            'position' => $request->position,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'office_id' => $request->officeId
        ]);

        // event(new Registered($user));

        // Auth::login($user);

        return redirect()->back()->with(['message' => 'User '. $user->name .' has been added successfully!']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Constants\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Jobs\RegistrationMailJob;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view users', only: ['index', 'show']),
            new Middleware('permission:create users', only: ['store']),
            new Middleware('permission:edit users', only: ['update']),
            new Middleware('role:Admin', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        // foreach ($users as $user) {
        //     $user->role;
        // }
        return Response::response('Users retrieved', $users);
    }

    /**
     * Show Profile of current authenticated user.
     */
    public function profile()
    {
        $profile = auth()->user();
        $percent = 35;

        // 1. Calculate Percentage
        if ($profile->roles->isNotEmpty())
            $percent += 15;
        if ($profile->phone)
            $percent += 10;
        if ($profile->gender)
            $percent += 10;
        if ($profile->address)
            $percent += 10;
        if ($profile->dob)
            $percent += 10;
        if ($profile->imagePath)
            $percent += 10;

        // Set the transient percent attribute
        $profile->percent = $percent;

        // 2. Load Spatie Relationships
        // 'roles' returns the role objects, 'permissions' returns direct permissions
        //$profile->load(['roles', 'permissions']);

        // 3. Optional: Include all permissions (inherited from roles)
        // We can append this manually so the frontend sees everything the user can do
        $profile->all_permissions = $profile->getAllPermissions()->pluck('name');
        $profile->role_names = $profile->getRoleNames();

        return Response::response('Profile retrieved successfully', $profile);
    }


    /**
     * Store a newly created user.
     */
    public function store(RegistrationRule $request)
    {
        $data = $request->validated();

        // 1. Handle Photo Upload (Normal Logic)
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        // 2. Hash Password
        $data['password'] = Hash::make($request->password);

        // 3. Create the User
        $user = User::create($data);

        // 4. Role Logic: Admin uses role_id, others get 'Worker'
        // Check if there is a logged-in user and if they are an Admin
        if (auth()->check() && auth()->user()->hasRole('Admin')) {

            $role = \Spatie\Permission\Models\Role::findOrFail($request->role_id);
            $user->assignRole($role->name);

            // Response for Admin (Resource Created, No Token)
            return Response::getResourceCreatedResponse('User created by Admin', $user->load('roles'));
        }

        // 5. Logic for "Others" (Self-Registration)
        $user->assignRole('Worker');

        // Create Token for the new user
        $token = $user->createToken('api-token')->plainTextToken;

        return Response::response('Registration successful', [
            'user' => $user->load('roles'),
            'token' => $token
        ]);
    }


    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->role;
        return Response::response('User retrieved', $user);
    }

    /**
     * Update the specified user.
     */
   public function update(Request $request, User $user = null)
{
    
    // 1. Identify Target: If {user} is in URL, use it. Otherwise, use Auth user.
    $targetUser = $user && $user->exists ? $user : auth()->user();

    // 2. Fix Validation: Remove $request->all() from the first argument
    $validatedData = $request->validate([
        'first_name' => 'required|max:30|alpha|regex:/^[A-Z]/',
        'last_name'  => 'required|max:30|alpha|regex:/^[A-Z]/',
        'phone'      => 'nullable|numeric|digits:10|unique:users,phone,' . $targetUser->id,
        'address'    => 'nullable|string|min:4',
        'role_id'    => 'nullable|exists:roles,id',
        'dob'        => 'nullable|date',
        'gender'     => 'nullable|in:M,F',
        'password'   => 'sometimes|nullable|min:4'
    ]);

    // 3. Authorization Check
    if (auth()->id() !== $targetUser->id && !auth()->user()->hasRole('Admin')) {
        return Response::getUnauthorizedResponse();
    }

    // 4. Role Update: Only Admins can change roles
    if ($request->filled('role_id') && auth()->user()->hasRole('Admin')) {
        $role = \Spatie\Permission\Models\Role::findById($request->role_id);
        $targetUser->syncRoles($role->name);
    }

    // 5. Password Security
    if ($request->filled('password')) {
        if (auth()->id() === $targetUser->id || auth()->user()->hasRole('Admin')) {
            $validatedData['password'] = $request->password;
        } else {
            unset($validatedData['password']);
        }
    }
    // 6. Update and Return
    $targetUser->update($validatedData);

    // Load role_names for your Flutter app
    return Response::response('Profile updated successfully', $targetUser->load('roles'));
}


    public function update_user(Request $request, User $user)
    {
        // 1. Authorization: Only Admins can use this specific 'update others' route
        if (!auth()->user()->hasRole('Admin')) {
            return Response::getUnauthorizedResponse();
        }

        // 2. Validation
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30|alpha|regex:/^[A-Z]/',
            'last_name' => 'required|max:30|alpha|regex:/^[A-Z]/',
            'phone' => 'nullable|numeric|digits:10|unique:users,phone,' . $user->id,
            'address' => 'nullable|string|min:4',
            'role_id' => 'nullable|exists:roles,id', // Admin can assign any valid role
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:M,F' // Cleaner than regex
        ]);

        if ($validator->fails()) {
            return Response::getNotValidResponse($validator->errors());
        }

        // 3. Update basic profile data
        // Only updates fields present in the request; others remain unchanged
        $user->update($request->only([
            'first_name',
            'last_name',
            'gender',
            'phone',
            'dob',
            'address'
        ]));

        // 4. Update Role via Spatie
        if ($request->filled('role_id')) {
            $role = \Spatie\Permission\Models\Role::findById($request->role_id);
            $user->syncRoles($role->name); // Replaces old roles with the new one
        }

        return Response::response("User updated successfully", $user->load('role_names'));
    }



    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // The middleware already checked if auth()->user()->hasRole('Admin')

        // Prevent the Admin from accidentally deleting themselves
        if (auth()->id() === $user->id) {
            return Response::response('You cannot delete your own account.', null, 403);
        }

        $user->delete();

        return Response::response('User deleted successfully', null);
    }


    public function changePassword(Request $request)
    {
        // 1. Validate the input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|current_password', // Checks if it matches DB
            'new_password' => 'required|min:8|confirmed',  // Requires new_password_confirmation
        ]);

        if ($validator->fails()) {
            return Response::response('Validation failed', $validator->errors(), 422);
        }

        // 2. Update the password for the AUTHENTICATED user
        $user = auth()->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 3. Optional: Revoke other tokens to force re-login on other devices
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return Response::response('Password changed successfully', null);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:2048']);

        if ($request->hasFile('photo')) {
            // Store file in storage/app/public/photos
            $path = $request->file('photo')->store('photos', 'public');

            $user = auth()->user();
            $user->update(['imagePath' => $path]);

            return Response::response('Photo updated', [
                'url' => asset('storage/' . $path)
            ]);
        }
    }


}
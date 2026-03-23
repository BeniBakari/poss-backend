<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Constants\Response;
use Illuminate\Support\Facades\Validator;
class ShopController extends Controller
{

    private function validator($data)
    {
        return Validator::make($data, [
            'name' => 'required|max:30',
            'address' => 'required|max:30',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // 1. Admin or Shop Owner (assuming roles 'Admin' and 'Shop Owner' see all)
        if ($user->hasAnyRole(['Admin', 'Shop Owner'])) {
            $shops = Shop::with('owner')->orderBy('name', 'ASC')->get();
        }

        // 2. Manager (assuming 'Manager' owns specific shops)
        else if ($user->hasRole('Manager')) {
            $shops = $user->shops()->with('owner')->get();
        }

        // 3. Worker (assuming 'Worker' belongs to one shop via employee profile)
        else if ($user->hasRole('Worker')) {
            // Ensure the relationship 'employee' and 'shop' are defined in your models
            $shop = $user->employee?->shop?->load('owner');
            $shops = $shop ? collect([$shop]) : null;
        } else {
            $shops = null;
        }

        return Response::response('Shops retrieved successfully', $shops);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation (Using your existing validator)
        $validated = $this->validator($request->all());
        if ($validated->fails()) {
            return Response::getNotValidResponse($validated->errors());
        }

        // 2. Authorization (Replacing Gate with Spatie's 'can')
        if (!auth()->user()->can('create shops')) {
            return Response::getUnauthorizedResponse();
        }

        // 3. Logic: Default to Auth user, but allow Admin to specify an owner_id
        $ownerId = (auth()->user()->hasRole('Admin') && $request->filled('owner_id'))
            ? $request->owner_id
            : auth()->id();

        // 4. Create Shop
        $shop = Shop::create([
            'name' => $request->name,
            'address' => $request->address,
            'owner_id' => $ownerId
        ]);

        // 5. Load the owner relationship (Replacing $shop->owner; for cleaner JSON)
        return Response::getResourceCreatedResponse("Shop", $shop->load('owner'));
    }


    /**
     * Display the specified resource.
     */
    public function show(string $shopId)
    {
        // 1. Find the shop (Load 'owner' immediately for the response)
        $shop = Shop::with('owner')->find($shopId);

        if (!$shop) {
            return Response::getResourceNotFoundResponse('Shop');
        }

        // 2. Authorization with Spatie
        // This checks if the user has the 'view shops' permission 
        // OR if you have a specific Policy for 'view-shop'
        if (!auth()->user()->can('view shops')) {
            return Response::getUnauthorizedResponse();
        }

        // 3. Logic Check: If they are a Manager/Worker, can they only see THEIR shop?
        if (auth()->user()->hasRole('Manager') && auth()->id() !== $shop->owner_id) {
            return Response::getUnauthorizedResponse();
        }

        return Response::response("Shop retrieved successfully", $shop);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $shopId)
    {
        // 1. Find the Shop
        $shop = Shop::find($shopId);

        if (!$shop) {
            return Response::getResourceNotFoundResponse('Shop');
        }

        // 2. Validation
        $validated = $this->validator($request->all());
        if ($validated->fails()) {
            return Response::getNotValidResponse($validated->errors());
        }

        // 3. Authorization with Spatie
        $user = auth()->user();

        // Check 1: Does the user have the general 'edit shops' permission?
        if (!$user->can('edit shops')) {
            return Response::getUnauthorizedResponse();
        }

        // Check 2: If the user is a Manager/Owner, is this THEIR shop?
        // Admins bypass this check automatically if you set up the Gate::before bypass
        if (!$user->hasRole('Admin') && $shop->owner_id !== $user->id) {
            return Response::getUnauthorizedResponse();
        }

        // 4. Update the Shop
        $shop->update($request->only(['name', 'address']));

        return Response::response("Shop updated successfully", $shop->load('owner'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $shopId)
    {
        // 1. Find the Shop
        $shop = Shop::find($shopId);

        if (!$shop) {
            return Response::getResourceNotFoundResponse('Shop');
        }

        // 2. Authorization with Spatie
        $user = auth()->user();

        // Check 1: Does the user have the 'delete shops' permission?
        if (!$user->can('delete shops')) {
            return Response::getUnauthorizedResponse();
        }

        // Check 2: Ownership security
        // Only allow the deletion if the user is an Admin OR the actual Owner of the shop
        if (!$user->hasRole('Admin') && $shop->owner_id !== $user->id) {
            return Response::getUnauthorizedResponse();
        }

        // 3. Delete the Shop
        $shop->delete();

        // 4. Return Success (204 No Content is standard for successful deletion)
        return Response::getResponseMessage(true, "Shop deleted successfully", 204);
    }

}

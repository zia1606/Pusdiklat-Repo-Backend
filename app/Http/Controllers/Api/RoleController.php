<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        try {
            $roles = Role::all();
            
            return response()->json([
                'status' => true,
                'message' => 'Roles retrieved successfully',
                'data' => $roles
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get roles: ' . $e->getMessage()
            ], 500);
        }
    }
}

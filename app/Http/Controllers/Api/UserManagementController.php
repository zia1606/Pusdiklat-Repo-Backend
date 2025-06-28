<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    // Get all users with pagination and filtering
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $role = $request->input('role', '');
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $query = User::with('role')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->when($role, function ($query) use ($role) {
                    $query->whereHas('role', function ($q) use ($role) {
                        $q->where('name', $role);
                    });
                });

            // Apply sorting
            $validSortFields = ['name', 'email', 'created_at'];
            $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'created_at';
            $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create a new user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user->load('role')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update a user
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role_id' => 'sometimes|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = User::findOrFail($id);

            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('password')) $updateData['password'] = bcrypt($request->password);
            if ($request->has('role_id')) $updateData['role_id'] = $request->role_id;

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $user->load('role')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete a user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting the currently authenticated admin
            // if (auth()->check() && auth()->id() == $user->id) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'You cannot delete your own account'
            //     ], 403);
            // }

            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk delete users
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $userIds = $request->user_ids;
            
            // Prevent deleting the currently authenticated admin
            // if (auth()->check() && in_array(auth()->id(), $userIds)) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'You cannot delete your own account'
            //     ], 403);
            // }

            User::whereIn('id', $userIds)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Users deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete users: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get all roles
    public function getRoles()
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
                'message' => 'Failed to retrieve roles: ' . $e->getMessage()
            ], 500);
        }
    }
}

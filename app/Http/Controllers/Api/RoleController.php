<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::with('permissions')->get(),
        ]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json([
            'data' => Permission::all(),
        ]);
    }
}

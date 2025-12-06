<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class TestBlockPushController extends Controller
{
    /**
     * CRITICAL: Mass assignment vulnerability
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);
        $user->update($request->all()); // CRITICAL: Allows modifying any field
        return response()->json(['success' => true]);
    }
    /**
     * Demonstrates basic input validation on user-provided search query
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('search');
        // Simple input validation: only allow alphanumeric and spaces, and limit length
        if (!is_string($search) || strlen($search) > 50 || !preg_match('/^[\w\s]+$/u', $search)) {
            return response()->json(['error' => 'Invalid search input.'], 400);
        }

        $users = User::where('name', 'like', '%' . $search . '%')->get();
        return response()->json($users);
    }
}


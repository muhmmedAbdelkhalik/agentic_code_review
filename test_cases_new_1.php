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
     * CRITICAL: SQL injection vulnerability
     */
    public function searchUsers2(Request $request)
    {
        $search = $request->input('search');
        $users = DB::select("SELECT * FROM users WHERE name LIKE '%{$search}%'");
        return response()->json($users);
    }
}

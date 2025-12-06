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
    public function searchUsers(Request $request)
    {
        $search = $request->input('search');
        $users = DB::select("SELECT * FROM users WHERE name LIKE '%{$search}%'");
        return response()->json($users);
    }

    /**
     * CRITICAL: Missing null check
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        $user->delete(); // CRITICAL: Will crash if $user is null
        return response()->json(['success' => true]);
    }

    /**
     * HIGH: N+1 query problem
     */
    public function getUserPosts($userId)
    {
        $user = User::find($userId);
        $posts = $user->posts;
        
        $result = [];
        foreach ($posts as $post) {
            $result[] = [
                'title' => $post->title,
                'author' => $post->user->name, // HIGH: N+1 query
            ];
        }
        
        return response()->json($result);
    }

    /**
     * HIGH: Missing input validation
     */
    public function createPost(Request $request)
    {
        $post = Post::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);
        return response()->json($post);
    }

    /**
     * MEDIUM: Code duplication
     */
    public function getUserProfile($userId)
    {
        $user = User::find($userId);
        return response()->json([
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
        ]);
    }

    /**
     * LOW: Code style issue - snake_case method name
     */
    public function get_user_data($id)
    {
        $User = User::find($id);
        return response()->json($User);
    }

    /**
     * LOW: Code style issue - snake_case method name
     */
    public function get_user_data2($id)
    {
        $User = User::find($id);
        return response()->json($User);
    }
}


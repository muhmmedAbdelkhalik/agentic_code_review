<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

/**
 * Test controller with intentional critical security issues
 * This file is used to test the Git hook blocking functionality
 */
class TestBlockPushController extends Controller
{
    /**
     * CRITICAL: Mass assignment vulnerability
     * User input is directly assigned without validation
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);
        
        // CRITICAL SECURITY ISSUE: Mass assignment without filtering
        $user->update($request->all()); // This allows users to modify any field!
        
        return response()->json(['success' => true]);
    }
    
    /**
     * CRITICAL: N+1 query problem
     * Database queries executed in a loop
     */
    public function getUserPosts($userId)
    {
        $user = User::find($userId);
        $posts = $user->posts; // First query
        
        $result = [];
        foreach ($posts as $post) {
            // CRITICAL PERFORMANCE ISSUE: Query executed for each iteration
            $result[] = [
                'title' => $post->title,
                'author' => $post->user->name, // N+1 query here!
                'comments' => $post->comments->count(), // Another N+1 query!
            ];
        }
        
        return response()->json($result);
    }
    
    /**
     * CRITICAL: Missing null check
     * Code will crash if user is not found
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        
        // CRITICAL BUG: No null check - will crash if user doesn't exist
        $user->delete(); // This will throw an error if $user is null!
        
        return response()->json(['success' => true]);
    }
    
    /**
     * CRITICAL: SQL injection vulnerability (if using raw queries)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('search');
        
        // CRITICAL SECURITY ISSUE: Direct string interpolation in SQL
        $users = \DB::select("SELECT * FROM users WHERE name LIKE '%{$search}%'");
        
        return response()->json($users);
    }
}

// New critical issue test

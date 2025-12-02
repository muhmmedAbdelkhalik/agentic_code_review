<?php

class UserController
{
    public function index()
    {
        // Potential N+1 query issue
        $users = User::all();
        
        foreach($users as $user) {
            // Accessing relationship in loop - N+1!
            echo $user->profile->name;
            echo $user->posts->count();
        }
        
        return view('users.index', compact('users'));
    }
    
    public function store(Request $request)
    {
        // Security issue: No validation!
        $user = User::create($request->all());
        
        return response()->json($user);
    }
    

    public function get_user_data($id)  // Style issue: snake_case method name
    {
        $user = User::find($id);
        return $user;
    }
}

// Testing Git hook

<?php

namespace App\Http\Controllers;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        // Security issue: Mass assignment vulnerability
        $user = User::find($id);
        $user->update($request->all()); // Dangerous!
        
        // Performance issue: N+1 query
        $users = User::all();
        foreach ($users as $user) {
            echo $user->posts->count();
        }
        
        return redirect()->back();
    }
}

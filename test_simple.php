<?php
// Simple test with one critical issue
class TestController {
    public function update($request, $id) {
        $user = User::find($id);
        $user->update($request->all()); // Mass assignment vulnerability
        return response()->json(['success' => true]);
    }
}

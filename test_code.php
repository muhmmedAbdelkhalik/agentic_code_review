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

// Additional test comment - testing Git hook
function testGitHook() {
    $users = User::all();
    foreach ($users as $user) {
        echo $user->profile->name; // Another potential N+1
    }
}
class OrderController
{
    public function processOrders()
    {
        // Issue 1: N+1 Query - fetching orders then accessing relationships in loop
        $orders = Order::all();
        
        foreach ($orders as $order) {
            echo $order->user->name;        // N+1 query!
            echo $order->items->count();    // Another N+1!
            echo $order->payment->status;   // Yet another N+1!
        }
        
        return view('orders.index');
    }
    
    public function updateOrder(Request $request, $id)
    {
        // Issue 2: Security - Mass assignment vulnerability
        $order = Order::find($id);
        $order->update($request->all());  // Dangerous!
        
        return response()->json($order);
    }
    
    public function delete_order($id)  // Issue 3: Style - snake_case method name
    {
        Order::destroy($id);
    }
    
    public function calculateTotal()
    {
        // Issue 4: Performance - Inefficient query
        $total = 0;
        $orders = Order::all();  // Loading all orders into memory!
        
        foreach ($orders as $order) {
            $total += $order->total;
        }
        
        return $total;  // Should use Order::sum('total') instead
    }
}

class PaymentController
{
    public function processPayment(Request $request)
    {
        // Security Issue: Mass assignment vulnerability
        $payment = Payment::create($request->all());
        
        // Performance Issue: N+1 query
        $orders = Order::where('user_id', $request->user_id)->get();
        foreach ($orders as $order) {
            echo $order->items->count();  // N+1 query!
            echo $order->user->name;      // Another N+1!
        }
        
        return response()->json($payment);
    }
    
    public function validatePayment($id)
    {
        // Bug: No null check
        $payment = Payment::find($id);
        return $payment->status == 'completed';  // Will crash if payment is null!
    }
}
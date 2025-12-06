<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Test controller with intentional security and code quality issues
 * This file is used to test the Git hook blocking functionality
 * Contains examples of: CRITICAL, HIGH, MEDIUM, and LOW severity issues
 */
class TestBlockPushController extends Controller
{
    // ============================================================================
    // 🔴 CRITICAL SEVERITY ISSUES
    // ============================================================================

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
     * CRITICAL: Mass assignment with fill()
     */
    public function createUser(Request $request)
    {
        $user = new User();
        // CRITICAL: fill() with all request data
        $user->fill($request->all());
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * CRITICAL: Mass assignment with create()
     */
    public function registerUser(Request $request)
    {
        // CRITICAL: Direct create with all request data
        $user = User::create($request->all());

        return response()->json(['user' => $user]);
    }

    /**
     * CRITICAL: SQL injection vulnerability
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('search');

        // CRITICAL SECURITY ISSUE: Direct string interpolation in SQL
        $users = DB::select("SELECT * FROM users WHERE name LIKE '%{$search}%'");

        return response()->json($users);
    }

    /**
     * CRITICAL: SQL injection with whereRaw
     */
    public function filterOrders(Request $request)
    {
        $status = $request->input('status');
        
        // CRITICAL: Unescaped variable in whereRaw
        $orders = Order::whereRaw("status = '{$status}'")->get();

        return response()->json($orders);
    }

    /**
     * CRITICAL: Missing null check - will crash
     */
    public function deleteUser($id)
    {
        $user = User::find($id);

        // CRITICAL BUG: No null check - will crash if user doesn't exist
        $user->delete(); // This will throw an error if $user is null!

        return response()->json(['success' => true]);
    }

    /**
     * CRITICAL: Missing null check with property access
     */
    public function getUserEmail($id)
    {
        $user = User::find($id);
        
        // CRITICAL: Accessing property on potentially null object
        return response()->json(['email' => $user->email]);
    }

    /**
     * CRITICAL: Missing null check with method call
     */
    public function updateUserProfile(Request $request, $id)
    {
        $user = User::find($id);
        
        // CRITICAL: Calling method on potentially null object
        $user->updateProfile($request->all());
        
        return response()->json(['success' => true]);
    }

    /**
     * CRITICAL: XSS vulnerability - unescaped output
     */
    public function displayUser(Request $request)
    {
        $name = $request->input('name');
        
        // CRITICAL: User input returned without escaping
        return response()->json(['message' => "Hello {$name}!"]);
    }

    /**
     * CRITICAL: Insecure direct object reference
     */
    public function viewOrder($orderId)
    {
        // CRITICAL: No authorization check - any user can view any order
        $order = Order::find($orderId);
        
        return response()->json($order);
    }

    /**
     * CRITICAL: Hard-coded credentials
     */
    public function adminLogin()
    {
        // CRITICAL: Hard-coded password
        $admin = User::where('email', 'admin@example.com')
                     ->where('password', Hash::make('admin123'))
                     ->first();
        
        return response()->json(['admin' => $admin]);
    }

    // ============================================================================
    // 🟡 HIGH SEVERITY ISSUES
    // ============================================================================

    /**
     * HIGH: N+1 query problem
     */
    public function getUserPosts($userId)
    {
        $user = User::find($userId);
        $posts = $user->posts; // First query

        $result = [];
        foreach ($posts as $post) {
            // HIGH PERFORMANCE ISSUE: Query executed for each iteration
            $result[] = [
                'title' => $post->title,
                'author' => $post->user->name, // N+1 query here!
                'comments' => $post->comments->count(), // Another N+1 query!
            ];
        }

        return response()->json($result);
    }

    /**
     * HIGH: N+1 query with nested relationships
     */
    public function getOrderDetails($orderId)
    {
        $order = Order::find($orderId);
        
        // HIGH: Accessing relationships without eager loading
        foreach ($order->items as $item) {
            $item->product->category->name; // Multiple N+1 queries
        }
        
        return response()->json($order);
    }

    /**
     * HIGH: Missing input validation
     */
    public function createPost(Request $request)
    {
        // HIGH: No validation - accepts any input
        $post = Post::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'user_id' => $request->input('user_id'),
        ]);

        return response()->json($post);
    }

    /**
     * HIGH: Missing input validation with file upload
     */
    public function uploadFile(Request $request)
    {
        // HIGH: No file type or size validation
        $file = $request->file('document');
        $file->storeAs('uploads', $file->getClientOriginalName());

        return response()->json(['success' => true]);
    }

    /**
     * HIGH: Missing authorization check
     */
    public function deletePost($postId)
    {
        // HIGH: No check if user owns the post
        $post = Post::find($postId);
        $post->delete();

        return response()->json(['success' => true]);
    }

    /**
     * HIGH: Missing authorization - can access any user's data
     */
    public function getUserOrders($userId)
    {
        // HIGH: No check if authenticated user matches $userId
        $orders = Order::where('user_id', $userId)->get();

        return response()->json($orders);
    }

    /**
     * HIGH: Inefficient query - loading all records
     */
    public function getAllUsers()
    {
        // HIGH: Loading all users without pagination
        $users = User::all();

        return response()->json($users);
    }

    /**
     * HIGH: Missing CSRF protection (if route not protected)
     */
    public function transferFunds(Request $request)
    {
        // HIGH: Sensitive operation without CSRF token check
        $amount = $request->input('amount');
        $toUserId = $request->input('to_user_id');
        
        // Transfer logic here
        
        return response()->json(['success' => true]);
    }

    // ============================================================================
    // 🔵 MEDIUM SEVERITY ISSUES
    // ============================================================================

    /**
     * MEDIUM: Code duplication
     */
    public function getUserProfile($userId)
    {
        $user = User::find($userId);
        // MEDIUM: Duplicated logic
        $profile = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
        ];
        return response()->json($profile);
    }

    public function getAdminProfile($adminId)
    {
        $admin = User::find($adminId);
        // MEDIUM: Same logic duplicated
        $profile = [
            'name' => $admin->first_name . ' ' . $admin->last_name,
            'email' => $admin->email,
        ];
        return response()->json($profile);
    }

    /**
     * MEDIUM: Missing error handling
     */
    public function processPayment(Request $request)
    {
        // MEDIUM: No try-catch for potential failures
        $amount = $request->input('amount');
        $payment = Payment::create(['amount' => $amount]);
        
        // Payment processing logic that might fail
        
        return response()->json(['success' => true]);
    }

    /**
     * MEDIUM: Hard-coded values
     */
    public function calculateTax($amount)
    {
        // MEDIUM: Hard-coded tax rate
        $taxRate = 0.15; // Should be configurable
        $tax = $amount * $taxRate;
        
        return response()->json(['tax' => $tax]);
    }

    /**
     * MEDIUM: Missing type hints
     */
    public function processOrder($orderId, $userId)
    {
        // MEDIUM: No type hints for parameters
        $order = Order::find($orderId);
        $order->user_id = $userId;
        $order->save();
        
        return response()->json($order);
    }

    /**
     * MEDIUM: Inefficient algorithm
     */
    public function findDuplicateUsers()
    {
        $users = User::all();
        $duplicates = [];
        
        // MEDIUM: O(n²) algorithm - inefficient for large datasets
        foreach ($users as $user1) {
            foreach ($users as $user2) {
                if ($user1->id !== $user2->id && $user1->email === $user2->email) {
                    $duplicates[] = $user1;
                }
            }
        }
        
        return response()->json($duplicates);
    }

    /**
     * MEDIUM: Missing return type declaration
     */
    public function getUserData($id)
    {
        $user = User::find($id);
        return response()->json($user);
    }

    /**
     * MEDIUM: Unused variable
     */
    public function updateOrderStatus($orderId, $status)
    {
        $order = Order::find($orderId);
        $oldStatus = $order->status; // MEDIUM: Variable assigned but never used
        $order->status = $status;
        $order->save();
        
        return response()->json(['success' => true]);
    }

    // ============================================================================
    // 🟢 LOW SEVERITY ISSUES
    // ============================================================================

    /**
     * LOW: Code style - inconsistent naming
     */
    public function get_user_data($id) // LOW: snake_case instead of camelCase
    {
        $User = User::find($id); // LOW: Variable name starts with uppercase
        return response()->json($User);
    }

    /**
     * LOW: Missing documentation
     */
    public function processData($data)
    {
        // LOW: No PHPDoc comment explaining what this does
        $result = [];
        foreach ($data as $item) {
            $result[] = $item * 2;
        }
        return response()->json($result);
    }

    /**
     * LOW: Magic numbers
     */
    public function checkAge($userId)
    {
        $user = User::find($userId);
        
        // LOW: Magic number - should be a constant
        if ($user->age < 18) {
            return response()->json(['error' => 'Too young']);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * LOW: Inconsistent spacing
     */
    public function formatName($firstName,$lastName) // LOW: Missing space after comma
    {
        return $firstName.' '.$lastName; // LOW: Should use string concatenation operator consistently
    }

    /**
     * LOW: Unnecessary complexity
     */
    public function isEven($number)
    {
        // LOW: Overcomplicated logic
        if ($number % 2 == 0) {
            return true;
        } else {
            return false;
        }
        // Should be: return $number % 2 == 0;
    }

    /**
     * LOW: Missing early return
     */
    public function validateEmail($email)
    {
        $isValid = false;
        
        // LOW: Could use early return pattern
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $isValid = true;
        }
        
        return response()->json(['valid' => $isValid]);
    }

    /**
     * LOW: Verbose code
     */
    public function getActiveUsers()
    {
        // LOW: Could be simplified
        $allUsers = User::all();
        $activeUsers = [];
        
        foreach ($allUsers as $user) {
            if ($user->status === 'active') {
                $activeUsers[] = $user;
            }
        }
        
        // Should be: User::where('status', 'active')->get();
        
        return response()->json($activeUsers);
    }

    /**
     * LOW: Missing constant for repeated string
     */
    public function checkStatus($userId)
    {
        $user = User::find($userId);
        
        // LOW: Repeated string literal
        if ($user->status === 'active') {
            return response()->json(['message' => 'User is active']);
        } elseif ($user->status === 'inactive') {
            return response()->json(['message' => 'User is inactive']);
        }
        
        return response()->json(['message' => 'Unknown status']);
    }

    // ============================================================================
    // TEST METHODS
    // ============================================================================

    public function testCriticalIssue()
    {
        $this->updateUser(new Request(['name' => 'test']), 1);
        $this->getUserPosts(1);
        $this->deleteUser(1);
        $this->searchUsers(new Request(['search' => 'test']));
    }

    public function testHighIssue()
    {
        $this->getUserPosts(1);
        $this->createPost(new Request(['title' => 'test']));
    }

    public function testMediumIssue()
    {
        $this->getUserProfile(1);
        $this->processPayment(new Request(['amount' => 100]));
    }

    public function testLowIssue()
    {
        $this->get_user_data(1);
        $this->checkAge(1);
    }
}

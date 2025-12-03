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

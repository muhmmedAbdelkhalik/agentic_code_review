# 🧪 Testing Workflow - Step by Step

This guide walks you through testing the complete code review flow from adding code to pushing to GitHub.

---

## 📋 Complete Test Flow

### Step 1: Add New Code with Issues

Add this code to `test_code.php`:

```php
<?php

// NEW TEST: Add this at the end of the file

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
```

**Expected Issues**:
- 🔴 **HIGH**: N+1 queries (3 instances)
- 🔴 **HIGH**: Mass assignment vulnerability
- 🟡 **MEDIUM**: Inefficient query (loading all records)
- 🟢 **LOW**: Style issue (snake_case method name)

---

### Step 2: Stage the Changes

```bash
cd /Users/abdelkhalik/projects/ai_engineer/agentic_code_review
git add test_code.php
```

**What happens**: Git stages your new code for commit.

---

### Step 3: Commit the Changes

```bash
git commit -m "test: add OrderController with multiple code issues for testing"
```

**What happens**: Git creates a commit with your changes.

**Output**:
```
[main abc1234] test: add OrderController with multiple code issues for testing
 1 file changed, 35 insertions(+)
```

---

### Step 4: Push to GitHub (Triggers Git Hook)

```bash
git push origin main
```

**What happens**:
1. Git hook `.git/hooks/pre-push` is automatically triggered
2. Code Review Agent runs: `python3 review_local.py`
3. Agent analyzes your committed changes
4. Review results are displayed in terminal
5. If successful, push continues to GitHub

**Expected Output**:
```
🤖 Running LocalAI Code Review Agent...

🚀 Starting LocalAI Code Review Agent...

📝 Collecting git diff...
   Found changes in 1 file(s)

🔧 Running analysis tools...
   • PHPStan...
   • PHPCS...
   • PHPUnit...

📤 Building prompt for LocalAI...

🤖 Calling LocalAI (gemma:2b)...

✅ Validating review output...

💾 Review saved to .local_review.json

================================================================================
📋 Code Review Summary
================================================================================

Code review report for test_code.php

🔍 Issues Found: 4

🔴 HIGH: 3
  • test_code.php:45 - N+1 query accessing user relationship in loop
  • test_code.php:46 - N+1 query accessing items relationship in loop
  • test_code.php:56 - Mass assignment vulnerability using $request->all()

🟡 MEDIUM: 1
  • test_code.php:68 - Inefficient query loading all orders into memory

⏱️  Analysis completed in 9.5s
================================================================================

✅ Push allowed

To https://github.com/muhmmedAbdelkhalik/agentic_code_review.git
   c262ac0..def5678  main -> main
```

---

### Step 5: Review the Detailed Results

```bash
cat .local_review.json
```

**What happens**: View the complete JSON review with all findings, evidence, and suggested fixes.

**Example Output**:
```json
{
  "summary": {
    "total_issues": 4,
    "by_severity": {
      "critical": 0,
      "high": 3,
      "medium": 1,
      "low": 0
    },
    "files_reviewed": 1,
    "analysis_time_seconds": 9.5
  },
  "issues": [
    {
      "id": "test_code.php:45:n1query",
      "file": "test_code.php",
      "line": 45,
      "type": "performance",
      "severity": "high",
      "message": "N+1 query detected: accessing 'user' relationship inside loop",
      "evidence": {
        "source": "git_diff",
        "snippet": "foreach ($orders as $order) {\n    echo $order->user->name;"
      },
      "suggested_fix": {
        "description": "Eager-load the 'user' relationship when querying orders",
        "patch": "@@ -42,7 +42,7 @@\n- $orders = Order::all();\n+ $orders = Order::with(['user', 'items', 'payment'])->get();"
      },
      "confidence": 0.95
    }
  ]
}
```

---

### Step 6: Verify on GitHub

```bash
# Open your repository in browser
open https://github.com/muhmmedAbdelkhalik/agentic_code_review/commits/main
```

**What happens**: You'll see your commit on GitHub with the commit message.

---

### Step 7: Check Git Hook Logs (Optional)

```bash
# View recent git operations
git log --oneline -5
```

**Output**:
```
def5678 (HEAD -> main, origin/main) test: add OrderController with multiple code issues for testing
c262ac0 test: add UserController with security issues
9723392 test: add ProductController to test Git hook
dbf0b65 Initial commit
```

---

## 🔄 Quick Test Cycle

### Minimal Test (30 seconds)

```bash
# 1. Add a simple issue
echo "<?php
class TestController {
    public function test() {
        \$users = User::all();
        foreach (\$users as \$user) {
            echo \$user->profile->name; // N+1
        }
    }
}" >> test_code.php

# 2. Commit and push
git add test_code.php
git commit -m "test: add simple N+1 query"
git push origin main

# 3. Review results
cat .local_review.json | grep -A 5 "message"
```

---

## 📊 Test Scenarios

### Scenario 1: Security Issues

**Code to Add**:
```php
class AuthController {
    public function login(Request $request) {
        $user = User::where('email', $request->email)->first();
        if (password_verify($request->password, $user->password)) {
            // Issue: No rate limiting, timing attack possible
            return response()->json(['token' => $user->createToken()]);
        }
    }
}
```

**Expected Findings**:
- Missing rate limiting
- Timing attack vulnerability
- No input validation

---

### Scenario 2: Performance Issues

**Code to Add**:
```php
class ReportController {
    public function generateReport() {
        $users = User::all(); // Loading all users!
        $data = [];
        
        foreach ($users as $user) {
            $data[] = [
                'user' => $user,
                'orders' => Order::where('user_id', $user->id)->get(), // N+1
                'total' => Order::where('user_id', $user->id)->sum('total') // Another query!
            ];
        }
        
        return view('report', compact('data'));
    }
}
```

**Expected Findings**:
- Loading all users into memory
- N+1 query for orders
- Inefficient aggregation query

---

### Scenario 3: Style Issues

**Code to Add**:
```php
class user_controller {  // Wrong: should be UserController
    public function get_user_data($id) {  // Wrong: should be getUserData
        $User = User::find($id);  // Wrong: should be $user
        return $User;
    }
}
```

**Expected Findings**:
- Class name not PascalCase
- Method name not camelCase
- Variable name not camelCase

---

## 🎯 Verification Checklist

After each test, verify:

- ✅ Git hook triggered automatically
- ✅ Code review completed successfully
- ✅ Issues were detected
- ✅ `.local_review.json` was created
- ✅ Terminal output was readable
- ✅ Push completed to GitHub
- ✅ Commit appears on GitHub

---

## 🐛 Troubleshooting Test Flow

### Issue: Hook Doesn't Run

**Check**:
```bash
# Verify hook is installed
ls -la .git/hooks/pre-push

# Should show:
# -rwxr-xr-x  1 user  staff  1234 Dec  2 10:00 .git/hooks/pre-push
```

**Fix**:
```bash
./install_hooks.sh
```

---

### Issue: No Issues Detected

**Check**:
```bash
# Verify changes are in the diff
git diff HEAD~1

# Should show your new code
```

**Fix**: Make sure your code actually has issues (N+1 queries, security problems, etc.)

---

### Issue: Agent Times Out

**Check**:
```bash
# Verify Ollama is running
curl http://localhost:11434/api/tags

# Should return list of models
```

**Fix**:
```bash
ollama serve  # Start Ollama if not running
```

---

### Issue: Invalid JSON Output

**Check**:
```bash
# View the raw response
cat .local_review.json | jq .

# If error, check agent logs
tail -20 .local_review.log
```

**Fix**: The agent should retry automatically. If persistent, try a different model:
```yaml
# config.yaml
localai_model: gemma2:2b  # or phi3:mini
```

---

## 📈 Success Metrics

After running a test, you should see:

| Metric | Expected Value |
|--------|---------------|
| **Review Time** | < 15 seconds |
| **Issues Found** | 1-5 per test |
| **Confidence** | > 85% |
| **False Positives** | < 20% |
| **Push Time** | < 20 seconds total |

---

## 🎓 Advanced Testing

### Test with Real Laravel Project

```bash
# 1. Go to your Laravel project
cd /path/to/your/laravel/project

# 2. Copy the agent
cp -r /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/{review_local.py,config.yaml,prompts,schema} .

# 3. Install the hook
bash /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/install_hooks.sh

# 4. Make changes and push
git add .
git commit -m "feat: add new feature"
git push origin main
```

---

### Test with Specific Commit Range

```bash
# Review last 3 commits
python3 review_local.py --commit-range HEAD~3..HEAD

# Review changes between branches
python3 review_local.py --commit-range main..feature-branch
```

---

## 🎉 Complete Test Example

Here's a full end-to-end test you can run right now:

```bash
# Navigate to project
cd /Users/abdelkhalik/projects/ai_engineer/agentic_code_review

# Add new test code
cat >> test_code.php << 'EOF'

class PaymentController
{
    public function processPayment(Request $request)
    {
        // Multiple issues for testing
        $payment = Payment::create($request->all()); // Security: mass assignment
        
        $orders = Order::where('user_id', $request->user_id)->get();
        foreach ($orders as $order) {
            echo $order->items->count(); // Performance: N+1 query
        }
        
        return response()->json($payment);
    }
}
EOF

# Stage changes
git add test_code.php

# Commit
git commit -m "test: add PaymentController with security and performance issues"

# Push (triggers hook)
git push origin main

# Review results
echo "=== Review Summary ==="
cat .local_review.json | jq '.summary'

echo -e "\n=== Issues Found ==="
cat .local_review.json | jq '.issues[] | {file: .file, line: .line, severity: .severity, message: .message}'

# Verify on GitHub
echo -e "\n✅ Test complete! Check GitHub:"
echo "https://github.com/muhmmedAbdelkhalik/agentic_code_review/commits/main"
```

---

## 📝 Test Log Template

Use this template to track your tests:

```
Test #: ___
Date: ___________
Code Added: _______________________
Expected Issues: __________________
Actual Issues Found: ______________
False Positives: __________________
False Negatives: __________________
Review Time: _______ seconds
Status: ✅ PASS / ❌ FAIL
Notes: ____________________________
```

---

**Ready to test?** Start with Step 1 above! 🚀

---

*Last Updated: December 2, 2025*


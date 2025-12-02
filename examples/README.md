# Example Files

This directory contains example files to help you understand the code review agent's output and test the system.

## Files

### sample_review.json

A complete example of the JSON output produced by the code review agent. This demonstrates:

- **Summary**: High-level overview of findings
- **Issues**: Detailed list of problems found, including:
  - Security issues (missing validation)
  - Performance issues (N+1 queries)
  - Style violations (PSR-12 naming)
- **Recommendations**: Actionable suggestions for improvement
- **Metadata**: Tool versions and timing information

### sample_diff.patch

A sample git diff showing typical Laravel code changes that would trigger various issues:

- New `OrderController` with several anti-patterns
- N+1 query pattern
- Missing input validation
- Style violations
- Mass assignment concerns

## Using These Examples

### Test the Agent Without a Real Laravel Project

You can test the agent using the sample diff:

```bash
# Apply the sample diff to a test repository
git apply examples/sample_diff.patch

# Run the review agent
python3 review_local.py

# Compare output with sample_review.json
```

### Understand the Output Format

Study `sample_review.json` to understand:

1. **Issue Structure**:
   - Each issue has a unique ID
   - Evidence includes source (which tool found it)
   - Suggested fixes include patches
   - Confidence scores indicate certainty

2. **Severity Levels**:
   - `critical`: Must fix (security, data loss)
   - `high`: Should fix soon (performance, bugs)
   - `medium`: Should fix (maintainability)
   - `low`: Nice to fix (style, minor issues)

3. **Issue Types**:
   - `security`: Security vulnerabilities
   - `performance`: Performance problems
   - `style`: Code style violations
   - `bug`: Logical errors
   - `test`: Testing issues
   - `maintenance`: Maintainability concerns

### Create Your Own Test Cases

Use `sample_diff.patch` as a template to create test cases:

```bash
# Copy and modify the sample
cp examples/sample_diff.patch examples/my_test.patch

# Edit to add your own test scenarios
vim examples/my_test.patch

# Apply and test
git apply examples/my_test.patch
python3 review_local.py
```

## Common Patterns Detected

The sample files demonstrate detection of:

### 1. N+1 Queries

```php
// Bad
$orders = Order::all();
foreach($orders as $order) {
    echo $order->user->name; // N+1!
}

// Good
$orders = Order::with('user')->get();
foreach($orders as $order) {
    echo $order->user->name; // Single query
}
```

### 2. Missing Validation

```php
// Bad
$order = Order::create($request->all());

// Good
$validated = $request->validate([
    'product_id' => 'required|exists:products,id',
    'quantity' => 'required|integer|min:1'
]);
$order = Order::create($validated);
```

### 3. Style Violations

```php
// Bad
public function get_orders() // snake_case

// Good
public function getOrders() // camelCase (PSR-12)
```

## Integration Testing

To verify the agent works correctly:

1. **Start LocalAI**:
   ```bash
   docker-compose up -d
   ```

2. **Apply sample diff**:
   ```bash
   git apply examples/sample_diff.patch
   ```

3. **Run agent**:
   ```bash
   python3 review_local.py
   ```

4. **Verify output**:
   - Check `.local_review.json` was created
   - Verify it contains issues similar to `sample_review.json`
   - Confirm all severity levels are present
   - Check that suggested fixes are actionable

5. **Clean up**:
   ```bash
   git reset --hard HEAD
   ```

## Customizing Examples

You can create additional examples for:

- Different Laravel versions
- Specific frameworks (Lumen, Laravel API)
- Custom validation scenarios
- Complex Eloquent relationships
- Testing patterns
- API security issues

Place new examples in this directory with descriptive names:

- `example_api_security.patch`
- `example_eloquent_relationships.patch`
- `example_test_coverage.patch`
- etc.

## Expected Review Time

With the sample diff:

- **PHPStan**: ~2-3 seconds
- **PHPCS**: ~1-2 seconds
- **PHPUnit**: ~3-5 seconds (if tests exist)
- **LocalAI inference**: ~5-10 seconds (first run)
- **Total**: ~12-20 seconds

Subsequent runs are faster due to model caching.


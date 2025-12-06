# Usage Guide

Complete guide for using the AI Code Review Agent with Ollama in your development workflow.

> **Note:** For installation instructions, see the main [README.md](../README.md)

## Table of Contents

- [Quick Start](#quick-start)
- [Running Reviews](#running-reviews)
- [Git Hook Integration](#git-hook-integration)
- [Understanding Output](#understanding-output)
- [Advanced Usage](#advanced-usage)
- [Best Practices](#best-practices)

## Quick Start

After installation, start using the agent:

```bash
# Run a review on current changes
python3 review_local.py

# Review specific commits
python3 review_local.py --commit-range HEAD~1..HEAD

# View results
cat .local_review.json
```

## Running Reviews

### Basic Commands

```bash
pip install -r requirements.txt
```

Or use a virtual environment (recommended):

```bash
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

### Step 2: Install PHP Tools (Laravel Projects)

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Install PHPCS
composer require --dev squizlabs/php_codesniffer

# PHPUnit (usually included with Laravel)
composer require --dev phpunit/phpunit
```

### Step 3: Setup Ollama

Install and start Ollama with the qwen2.5-coder model:

```bash
# Install Ollama (if not already installed)
# Visit https://ollama.ai for installation instructions

# Start Ollama server
ollama serve

# Pull the code review model (in a new terminal)
ollama pull qwen2.5-coder:7b

# Verify it's running
curl http://localhost:11434/api/tags
```

### Step 4: Configure the Agent

```bash
# Copy environment template
cp .env.example .env

# Edit configuration (optional)
vim config.yaml
```

## Configuration

### config.yaml

The main configuration file controls all aspects of the agent:

```yaml
# Ollama settings
localai:
  url: "http://localhost:11434"
  model: "qwen2.5-coder:7b"
  temperature: 0.2          # Lower = more deterministic
  max_tokens: 4000          # Max response length
  timeout: 120              # Request timeout in seconds

# PHP analysis tools
tools:
  phpstan:
    enabled: true
    path: "phpstan"         # or absolute path: /usr/local/bin/phpstan
    args: ["analyse", "--error-format=json", "--no-progress"]
  
  phpcs:
    enabled: true
    path: "phpcs"
    args: ["--report=json", "--standard=PSR12"]
  
  phpunit:
    enabled: true
    path: "phpunit"
    args: ["--testdox", "--colors=never"]

# Output settings
output:
  file: ".local_review.json"
  log_file: ".local_review.log"
  verbose: false            # Set to true for detailed logging

# Git settings
git:
  diff_context: 5           # Lines of context around changes
  target_branch: "main"     # Branch to compare against

# Review behavior
review:
  max_issues: 100           # Maximum issues to report
  block_on_critical: false  # Block push on critical issues
  min_confidence: 0.5       # Minimum confidence to report
```

### Environment Variables (.env)

Environment variables override config.yaml settings:

```bash
# Ollama
LOCALAI_URL=http://localhost:11434
LOCALAI_MODEL=qwen2.5-coder:7b
LOCALAI_TEMPERATURE=0.2
LOCALAI_MAX_TOKENS=4000

# Tool paths (if not in PATH)
PHPSTAN_PATH=/usr/local/bin/phpstan
PHPCS_PATH=/usr/local/bin/phpcs
PHPUNIT_PATH=/usr/local/bin/phpunit

# Output
OUTPUT_FILE=.local_review.json
VERBOSE=false

# Behavior
BLOCK_ON_CRITICAL=false
MIN_CONFIDENCE=0.5
```

## Running Reviews

### Basic Usage

```bash
# Review current changes (staged or uncommitted)
python3 review_local.py

# Review specific commit range
python3 review_local.py --commit-range HEAD~1..HEAD

# Review with verbose output
python3 review_local.py --verbose

# Use custom config file
python3 review_local.py --config my-config.yaml
```

### Command-Line Options

```
usage: review_local.py [-h] [--config CONFIG] [--commit-range COMMIT_RANGE] [--verbose]

optional arguments:
  -h, --help            show this help message and exit
  --config CONFIG       Path to configuration file (default: config.yaml)
  --commit-range COMMIT_RANGE
                        Git commit range to analyze (e.g., HEAD~1..HEAD)
  --verbose             Enable verbose output
```

### What Gets Analyzed

The agent analyzes:

1. **Git Diff**: All changed lines compared to target branch
2. **PHPStan**: Static analysis of changed files
3. **PHPCS**: Code style check of changed files
4. **PHPUnit**: All tests in the project

### Review Process

```
1. 📝 Collect git diff
   ↓
2. 🔧 Run PHP tools (parallel)
   ├── PHPStan (static analysis)
   ├── PHPCS (style check)
   └── PHPUnit (tests)
   ↓
3. 📤 Build prompt with all inputs
   ↓
4. 🤖 Send to Ollama
   ↓
5. ✅ Validate JSON response
   ↓
6. 💾 Save to .local_review.json
   ↓
7. 📋 Print summary to terminal
```

## Git Hook Integration

### Install the Pre-Push Hook

```bash
# Run the installation script
./install_hooks.sh

# Or manually
cp hooks/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push
```

### How It Works

The pre-push hook:

1. Runs automatically before `git push`
2. Executes the code review agent
3. Displays summary in terminal
4. Optionally blocks push on critical issues

### Skipping the Hook

```bash
# Skip review for a single push
SKIP_REVIEW=1 git push

# Or use --no-verify
git push --no-verify
```

### Blocking on Critical Issues

Enable blocking in `.env`:

```bash
BLOCK_ON_CRITICAL=true
```

Now pushes will be blocked if critical issues are found:

```
🚫 BLOCKING PUSH: 2 critical issue(s) found
   Fix critical issues or skip review with: SKIP_REVIEW=1 git push
```

## Understanding Output

### Terminal Output

```
🚀 Starting LocalAI Code Review Agent...

📝 Collecting git diff...
   Found changes in 3 file(s)

🔧 Running analysis tools...
   • PHPStan...
   • PHPCS...
   • PHPUnit...

📤 Building prompt for Ollama...

🤖 Calling Ollama (qwen2.5-coder:7b)...

✅ Validating review output...

💾 Review saved to .local_review.json

================================================================================
📋 Code Review Summary
================================================================================

Found 3 issues: 1 security concern, 1 performance issue, 1 style violation

🔍 Issues Found: 3

🔴 CRITICAL: 1
  • app/Http/Controllers/OrderController.php:77
    Missing input validation on user-provided data

🟡 HIGH: 1
  • app/Http/Controllers/OrderController.php:45
    Possible N+1 query detected

🔵 MEDIUM: 0

🟢 LOW: 1
  • app/Http/Controllers/OrderController.php:23
    Method name does not follow PSR-12 convention

💡 Recommendations: 4
  • [security] Create a dedicated FormRequest class
  • [tests] Add unit tests for OrderController methods
  • [style] Run php-cs-fixer to fix style violations
  • [architecture] Consider moving logic to service class

⏱️  Analysis completed in 12.40s
================================================================================
```

### JSON Output (.local_review.json)

The JSON file contains structured data:

```json
{
  "summary": "Brief overview of findings",
  "issues": [
    {
      "id": "unique-identifier",
      "file": "path/to/file.php",
      "line": 42,
      "type": "security|performance|style|bug|test|maintenance",
      "severity": "critical|high|medium|low",
      "message": "Human-readable description",
      "evidence": {
        "source": "git_diff|phpstan|phpcs|phpunit",
        "snippet": "Code excerpt",
        "extra": "Additional context"
      },
      "suggested_fix": {
        "description": "How to fix it",
        "patch": "Unified diff or code snippet",
        "files_touched": ["list of files"]
      },
      "confidence": 0.92,
      "explain": "Why this is an issue"
    }
  ],
  "recommendations": [
    {
      "area": "tests|ci|security|style|architecture",
      "suggestion": "What to do",
      "rationale": "Why do it",
      "priority": "high|medium|low"
    }
  ],
  "meta": {
    "analyzed_at": "2025-12-02T14:30:45+02:00",
    "tool_versions": {
      "phpstan": "1.10.50",
      "phpcs": "3.7.2",
      "phpunit": "9.6.15",
      "localai_model": "qwen2.5-coder:7b"
    },
    "duration_seconds": 12.4
  }
}
```

### Issue Types

- **security**: Security vulnerabilities (SQL injection, XSS, etc.)
- **performance**: Performance problems (N+1, slow queries, etc.)
- **style**: Code style violations (PSR-12, naming, etc.)
- **bug**: Logical errors or bugs
- **test**: Testing issues (missing tests, failing tests)
- **maintenance**: Maintainability concerns (complexity, duplication)

### Severity Levels

- **critical** 🔴: Must fix immediately (security, data loss)
- **high** 🟡: Should fix soon (performance, bugs)
- **medium** 🔵: Should fix eventually (maintainability)
- **low** 🟢: Nice to fix (style, minor issues)

### Confidence Scores

- **0.9-1.0**: Very confident (tool-reported issues)
- **0.7-0.9**: Confident (clear patterns)
- **0.5-0.7**: Moderate (possible issues)
- **< 0.5**: Low confidence (suggestions only)

## Advanced Usage

### Custom Prompts

Edit `prompts/system_prompt.txt` to customize the AI's behavior:

```txt
You are a senior Laravel engineer...

Additional rules:
- Focus on security issues
- Prioritize performance over style
- Be strict about test coverage
```

### Analyzing Specific Files

```bash
# Create a custom diff for specific files
git diff main -- app/Http/Controllers/ > custom.diff

# Then manually run tools on those files
phpstan analyse app/Http/Controllers/
```

### Integration with CI/CD

```yaml
# .github/workflows/code-review.yml
name: Code Review

on: [pull_request]

jobs:
  review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup Ollama
        run: |
          curl -fsSL https://ollama.ai/install.sh | sh
          ollama serve &
          ollama pull qwen2.5-coder:7b

      - name: Install dependencies
        run: pip install -r requirements.txt

      - name: Run code review
        run: python3 review_local.py --commit-range origin/main..HEAD

      - name: Upload review
        uses: actions/upload-artifact@v2
        with:
          name: code-review
          path: .local_review.json
```

### Custom Tool Configuration

Disable specific tools:

```yaml
tools:
  phpstan:
    enabled: false  # Skip PHPStan
  phpcs:
    enabled: true
  phpunit:
    enabled: true
```

Or customize arguments:

```yaml
tools:
  phpstan:
    enabled: true
    path: "vendor/bin/phpstan"
    args: ["analyse", "--level=8", "--error-format=json"]
```

### Multiple Models

Test different Ollama models:

```bash
# Use qwen2.5-coder (recommended)
LOCALAI_MODEL=qwen2.5-coder:7b python3 review_local.py

# Use deepseek-coder (alternative)
LOCALAI_MODEL=deepseek-coder:6.7b python3 review_local.py

# Use codellama (faster)
LOCALAI_MODEL=codellama:7b python3 review_local.py
```

## Troubleshooting

### Ollama Connection Errors

```
❌ Could not connect to Ollama at http://localhost:11434
```

**Solutions**:
1. Check if Ollama is running: `ps aux | grep ollama`
2. Start Ollama: `ollama serve`
3. Check available models: `ollama list`
4. Verify endpoint: `curl http://localhost:11434/api/tags`

### Tool Not Found Errors

```
⚠️  PHPStan not found at phpstan
```

**Solutions**:
1. Install the tool: `composer require --dev phpstan/phpstan`
2. Use absolute path in config.yaml: `path: "/full/path/to/phpstan"`
3. Add to PATH: `export PATH="$PATH:vendor/bin"`

### Invalid JSON Response

```
⚠️  Review output does not match schema
```

**Solutions**:
1. Check Ollama logs: `journalctl -u ollama -f` (on Linux) or check console output
2. Increase max_tokens: `max_tokens: 4000`
3. Lower temperature: `temperature: 0.1`
4. Try a different model (see Multiple Models section)
5. Check `.local_review.log` for details

### Slow Performance

**Solutions**:
1. Use smaller/faster model: `ollama pull codellama:7b`
2. Use GPU acceleration (if available): Ollama automatically uses GPU when available
3. Reduce diff context: `git.diff_context: 3`
4. Close other Ollama instances
5. Increase system resources available to Ollama

### No Changes Detected

```
⚠️  No changes detected
```

**Solutions**:
1. Make sure you have uncommitted changes or staged changes
2. Check target branch: `git.target_branch: "main"`
3. Use --commit-range: `--commit-range HEAD~1..HEAD`
4. Verify git status: `git status`

## Best Practices

### 1. Run Reviews Frequently

```bash
# Before committing
python3 review_local.py

# After making changes
git add .
python3 review_local.py
```

### 2. Address Critical Issues First

Focus on:
- 🔴 Critical security issues
- 🔴 Critical bugs
- 🟡 High-priority performance issues

### 3. Use Git Hooks

Install the pre-push hook to catch issues before they reach the remote:

```bash
./install_hooks.sh
```

### 4. Keep Models Updated

Update Ollama and models regularly:

```bash
# Update Ollama itself
curl -fsSL https://ollama.ai/install.sh | sh

# Pull latest model version
ollama pull qwen2.5-coder:7b
```

### 5. Customize for Your Team

Adjust configuration to match your team's standards:

```yaml
review:
  min_confidence: 0.7  # Stricter filtering
  block_on_critical: true  # Enforce critical fixes
```

### 6. Review the AI's Suggestions

The AI is a tool, not a replacement for human judgment:
- Verify suggested fixes before applying
- Consider context the AI might miss
- Use confidence scores as a guide

### 7. Combine with Other Tools

Use the agent alongside:
- Manual code reviews
- Automated tests
- Security scanners
- Performance profilers

### 8. Monitor Performance

Track review times and adjust:

```bash
# Check duration in output
cat .local_review.json | jq '.meta.duration_seconds'

# If too slow, optimize configuration
```

## Getting Help

- Check logs: `.local_review.log`
- Review examples: `examples/`
- Ollama docs: https://ollama.ai/
- GitHub issues: [Create an issue]

## Next Steps

1. ✅ Complete installation
2. ✅ Run your first review
3. ✅ Install Git hooks
4. ✅ Customize configuration
5. ✅ Integrate into workflow
6. ✅ Share with team

Happy reviewing! 🚀


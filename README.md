# 🤖 LocalAI Code Review Agent

AI-powered code review that runs 100% locally on your machine. Catches security issues, bugs, and performance problems before you push.

## ✨ What You Get

- **🔐 100% Private**: All code analysis happens locally - nothing leaves your machine
- **🛡️ Security First**: Detects SQL injection, XSS, mass assignment, and more
- **⚡ Performance**: Catches N+1 queries and inefficient code
- **🐛 Bug Prevention**: Finds missing null checks and logic errors
- **🎯 Laravel-Optimized**: Built specifically for Laravel/PHP projects
- **🚫 Push Blocking**: Optionally block pushes with critical issues

## 📋 Prerequisites

Before installing, you need:

- **Ollama** - For running the local LLM
- **Python 3.8+** - For the agent script
- **Git** - For version control integration

## 🚀 Installation (A to Z)

### Step 1: Install Ollama

**macOS:**
```bash
brew install ollama
```

**Linux:**
```bash
curl -fsSL https://ollama.com/install.sh | sh
```

**Windows:**
Download from [ollama.com](https://ollama.com/download)

Start Ollama:
```bash
ollama serve
```

### Step 2: Download the AI Model

```bash
# Download qwen2.5-coder:7b (~4.7GB, one-time download)
ollama pull qwen2.5-coder:7b

# Verify it's installed
ollama list | grep qwen
```

**Why this model?**
- Specifically trained for code analysis
- Excellent security vulnerability detection
- Runs 100% locally
- Good balance: ~60 seconds per review

### Step 3: Install the Agent

**Option A: One-Command Install (Recommended)**

```bash
curl -sSL https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main/install.sh | bash -s /path/to/your/laravel/project
```

**Option B: Local Install**

```bash
# Clone the repository
git clone https://github.com/muhmmedAbdelkhalik/agentic_code_review.git
cd agentic_code_review

# Run installer
./install.sh /path/to/your/laravel/project
```

The installer will:
- ✅ Check prerequisites
- ✅ Install Python dependencies
- ✅ Copy agent files to your project
- ✅ Install Git pre-push hook
- ✅ Configure everything

### Step 4: Verify Installation

```bash
cd /path/to/your/laravel/project

# Check system health using the agent CLI
./agent doctor

# Should show a comprehensive health check report
```

### Step 5: Test It

```bash
# Run your first review using the agent CLI
./agent review

# View the results
cat .local_review.json
```

**That's it!** The agent is now installed and will automatically review your code on every `git push`.

## 🎯 Quick Start

### Agent CLI (Recommended)

The `agent` command provides convenient shortcuts for all operations:

```bash
# Check system health
./agent doctor

# Run code review
./agent review

# Review specific commits
./agent review --range HEAD~1..HEAD

# Install git hooks
./agent install-hooks

# Run tests
./agent test

# Show help
./agent help
```

### Run a Manual Review

```bash
# Using agent CLI (recommended)
./agent review
./agent review --range HEAD~1..HEAD
./agent review --verbose

# Using Python directly (alternative)
python3 review_local.py
python3 review_local.py --commit-range HEAD~1..HEAD
python3 review_local.py --verbose
```

### Automatic Reviews (Git Hook)

The pre-push hook is already installed! Just push normally:

```bash
git add .
git commit -m "feat: new feature"
git push origin main  # Agent runs automatically
```

### Skip a Review

```bash
# Skip review for one push
SKIP_REVIEW=1 git push origin main

# Or use --no-verify
git push --no-verify origin main
```

## ⚙️ Configuration

Edit `config.yaml` in your project:

```yaml
# Model settings
localai:
  model: "qwen2.5-coder:7b"
  timeout: 120
  max_tokens: 4000

# Block pushes with critical issues
review:
  block_on_critical: true  # Set to false to allow all pushes

# PHP tools
tools:
  phpstan:
    enabled: true
  phpcs:
    enabled: true
  phpunit:
    enabled: false  # Set to true to run tests
```

📖 **See [docs/CONFIGURATION.md](docs/CONFIGURATION.md) for all options**

## 🔍 What It Catches

### Security Issues (Critical)
- SQL injection vulnerabilities
- XSS (Cross-Site Scripting)
- Mass assignment vulnerabilities
- Missing input validation
- Insecure direct object references

### Performance Issues (High)
- N+1 query problems
- Missing eager loading
- Inefficient database queries
- Memory-intensive operations

### Bugs (Critical/High)
- Missing null checks (crashes)
- Logic errors
- Type mismatches
- Undefined variables

### Code Quality (Medium/Low)
- PSR-12 violations
- Missing documentation
- Code duplication
- Complexity issues

## 📊 Example Output

```bash
🤖 Running LocalAI Code Review Agent...
🚀 Starting LocalAI Code Review Agent...
📝 Collecting git diff...
   Found changes in 2 file(s)

🔧 Running analysis tools...
   • PHPStan...
   • PHPCS...

🤖 Calling LocalAI (qwen2.5-coder:7b)...

================================================================================
📋 Code Review Summary
================================================================================

🔍 Issues Found: 3

🔴 CRITICAL: 2
  • app/Http/Controllers/UserController.php:45
    SQL injection vulnerability in search query

  • app/Http/Controllers/UserController.php:78
    Mass assignment vulnerability - use $fillable

🟡 HIGH: 1
  • app/Http/Controllers/UserController.php:23
    N+1 query detected - use eager loading

⏱️  Analysis completed in 58.32s
================================================================================

🚫 BLOCKING: 2 critical issue(s) found
```

## 📚 Documentation

- **[Usage Guide](docs/USAGE.md)** - Detailed usage instructions
- **[Configuration](docs/CONFIGURATION.md)** - All configuration options
- **[Upgrading](docs/UPGRADE.md)** - Upgrade from older versions
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues and solutions
- **[Changelog](docs/CHANGELOG.md)** - Recent improvements and fixes

## 🔄 Upgrading

If you're already using an older version with `gemma:2b`:

```bash
cd /path/to/agentic_code_review
./upgrade.sh
```

This will:
- ✅ Download the new model (qwen2.5-coder:7b)
- ✅ Update configuration
- ✅ Update prompts for better detection
- ✅ Improve detection rate from 25% to 100%

📖 **See [docs/UPGRADE.md](docs/UPGRADE.md) for details**

## 🆘 Troubleshooting

### Ollama not running?
```bash
# Start Ollama
ollama serve

# Check if it's running
curl http://localhost:11434/api/tags
```

### Model not found?
```bash
# Download the model
ollama pull qwen2.5-coder:7b

# List installed models
ollama list
```

### Python dependencies missing?
```bash
# Install dependencies
pip3 install -r requirements.txt

# Or with system packages flag (if needed)
pip3 install --break-system-packages -r requirements.txt
```

### Hook not running?
```bash
# Check hook is installed
ls -la .git/hooks/pre-push

# Reinstall if needed
cp /path/to/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push
```

📖 **See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for more help**

## 🎯 How It Works

```
Developer pushes code
        ↓
Pre-push hook triggers
        ↓
Agent collects git diff
        ↓
Runs PHP tools (phpstan, phpcs)
        ↓
Sends to local LLM (Ollama)
        ↓
LLM analyzes code
        ↓
Returns structured JSON review
        ↓
Shows summary in terminal
        ↓
Blocks push if critical issues found
```

**Everything happens on your machine. No code leaves your device.**

## 📦 What Gets Installed

```
your-laravel-project/
├── review_local.py          # Main agent script
├── config.yaml              # Configuration
├── prompts/
│   └── system_prompt.txt    # LLM instructions
├── schema/
│   └── review_schema.json   # Output validation
└── .git/hooks/
    └── pre-push            # Git automation
```

## 🤝 Contributing

Contributions welcome! Areas for improvement:
- Support for more languages (JavaScript, Python, Go)
- Additional Laravel-specific rules
- Performance optimizations
- Better error handling

## 📄 License

Internal use only. Check model licensing for Ollama-compatible LLMs.

## 💬 Support

- **Documentation**: [docs/](docs/)
- **Issues**: Check `.local_review.log` for errors
- **GitHub**: https://github.com/muhmmedAbdelkhalik/agentic_code_review

---

**Happy coding! 🚀** Your code is now protected by AI-powered review.

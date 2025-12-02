# 🤖 Code Review Agent (LocalAI-Powered)

A fully local, privacy-preserving **AI-driven code review system** designed to analyze Laravel (or any backend) projects **before creating a Pull Request**.  
This agent runs directly on each developer’s machine using **LocalAI**, ensuring that **no source code leaves the device**.

---

## 🚀 Overview

The **Code Review Agent** automatically reviews code changes on every local commit or push.  
It analyzes diffs, PHP static analysis results, coding style, and test outputs — then generates a structured JSON review using a **local LLM model**.

Key goals of this project:

- ⚡ Enhance code quality early (before PR stage)  
- 🔐 Keep company source code completely private (local LLM)  
- 🧠 Provide smart suggestions powered by AI  
- 🛠 Integrate with Laravel’s ecosystem (phpstan, phpcs, phpunit)  
- 💻 Run directly on each developer’s device (Mac, Linux, Windows via WSL)

---

## 🏗 Architecture

```
Developer Machine
│
├── LocalAI Server (Docker or native)
│     └── Runs a local GGUF/GGML model (Llama, Mistral, Phi...)
│
├── review_local.py (Agent Runner)
│     ├── Collects Git diff
│     ├── Runs phpstan + phpcs + phpunit
│     ├── Builds prompt payload
│     ├── Sends request to LocalAI
│     └── Saves structured JSON review output
│
└── Optional: Git pre-push hook
      ├── Runs the agent before pushing
      └── Displays summary in CLI
```

---

## ✨ Features

### ✔ Full Local Privacy  
Runs a local LLM (LocalAI) — **no code leaves the machine**.

### ✔ Pre-PR Automated Code Review  
Analyzes code before the developer even opens a PR.

### ✔ Laravel-Oriented Analysis  
Detects common Laravel and backend issues:
- Missing validation  
- Potential N+1 or inefficient queries  
- Dangerous mass assignment  
- Migration problems  
- Eloquent misuse  

### ✔ Static Analysis Integration  
Runs:
- **phpstan** — static logic & type errors  
- **phpcs** — coding standards  
- **phpunit** — test execution  

### ✔ Structured JSON Output  
Easy to parse and integrate into CI or Git hooks.

Example:
```json
{
  "summary": "...",
  "issues": [
    {
      "file": "...",
      "line": 42,
      "type": "security",
      "message": "...",
      "suggested_fix": "...",
      "confidence": 0.9
    }
  ],
  "recommendations": []
}
```

### ✔ Git Hook Ready  
A pre-push hook can run the agent automatically.

---

## 🛠 Requirements

- **Docker** (recommended) or native LocalAI installation
- **Python 3.10+** with pip
- **LocalAI model** in GGUF format (Mistral-7B recommended)
- **Git** for version control
- **PHP 7.4+** with Composer (for Laravel projects)
- **PHP Analysis Tools** (optional but recommended):
  - phpstan
  - phpcs (PHP_CodeSniffer)
  - phpunit

---

## ⚡ Quick Start

### 1. Install Dependencies

```bash
# Install Python dependencies
pip install -r requirements.txt

# Install PHP tools (for Laravel projects)
composer require --dev phpstan/phpstan squizlabs/php_codesniffer
```

### 2. Setup LocalAI

```bash
# Start LocalAI with Docker Compose
docker-compose up -d

# Download a model (Mistral-7B recommended)
mkdir -p models
cd models
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf
mv mistral-7b-instruct-v0.2.Q4_K_M.gguf mistral-7b-instruct.gguf
cd ..

# Wait for model to load (check logs)
docker-compose logs -f localai
```

See [docker/localai/README.md](docker/localai/README.md) for detailed setup instructions and model recommendations.

### 3. Configure the Agent

```bash
# Copy environment template (optional)
cp .env.example .env

# Edit configuration if needed
vim config.yaml
```

### 4. Run Your First Review

```bash
# Review current changes
python3 review_local.py

# Check the output
cat .local_review.json
```

### 5. Install Git Hook (Optional)

```bash
# Install pre-push hook for automatic reviews
./install_hooks.sh
```

---

## 📖 Usage

### Basic Commands

```bash
# Review current changes (staged or uncommitted)
python3 review_local.py

# Review specific commit range
python3 review_local.py --commit-range HEAD~1..HEAD

# Enable verbose output
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

### Output

The agent produces:

1. **Terminal Summary**: Color-coded summary with issue counts by severity
2. **JSON File** (`.local_review.json`): Structured review data with:
   - Summary of findings
   - Detailed issues with evidence and suggested fixes
   - Recommendations for improvement
   - Metadata (tool versions, duration)

Example terminal output:

```
🚀 Starting LocalAI Code Review Agent...

📝 Collecting git diff...
   Found changes in 3 file(s)

🔧 Running analysis tools...
   • PHPStan...
   • PHPCS...
   • PHPUnit...

🤖 Calling LocalAI (mistral-7b-instruct)...

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

🟢 LOW: 1
  • app/Http/Controllers/OrderController.php:23
    Method name does not follow PSR-12 convention

💡 Recommendations: 4
  • [security] Create a dedicated FormRequest class
  • [tests] Add unit tests for OrderController methods

⏱️  Analysis completed in 12.40s
================================================================================
```

---

## 🔧 Configuration

### config.yaml

Main configuration file:

```yaml
# LocalAI settings
localai:
  url: "http://localhost:8080"
  model: "mistral-7b-instruct"
  temperature: 0.2          # Lower = more deterministic
  max_tokens: 3000          # Max response length
  timeout: 120              # Request timeout

# PHP analysis tools
tools:
  phpstan:
    enabled: true
    path: "phpstan"
    args: ["analyse", "--error-format=json", "--no-progress"]
  phpcs:
    enabled: true
    path: "phpcs"
    args: ["--report=json", "--standard=PSR12"]
  phpunit:
    enabled: true
    path: "phpunit"
    args: ["--testdox"]

# Output settings
output:
  file: ".local_review.json"
  log_file: ".local_review.log"
  verbose: false

# Git settings
git:
  diff_context: 5
  target_branch: "main"

# Review behavior
review:
  max_issues: 100
  block_on_critical: false
  min_confidence: 0.5
```

### Environment Variables (.env)

Override config values:

```bash
LOCALAI_URL=http://localhost:8080
LOCALAI_MODEL=mistral-7b-instruct
LOCALAI_TEMPERATURE=0.2
BLOCK_ON_CRITICAL=false
VERBOSE=false
```

---

## 🪝 Git Hook Integration

### Install the Hook

```bash
./install_hooks.sh
```

This installs a pre-push hook that:
- Runs automatically before `git push`
- Displays review summary
- Optionally blocks push on critical issues

### Skip the Hook

```bash
# Skip review for a single push
SKIP_REVIEW=1 git push

# Or use --no-verify
git push --no-verify
```

### Block on Critical Issues

Enable in `.env`:

```bash
BLOCK_ON_CRITICAL=true
```

Now pushes will be blocked if critical issues are found.

---

## 📊 Understanding the Output

### Issue Types

- **security**: Security vulnerabilities (SQL injection, XSS, mass assignment)
- **performance**: Performance problems (N+1 queries, inefficient code)
- **style**: Code style violations (PSR-12, naming conventions)
- **bug**: Logical errors or bugs
- **test**: Testing issues (missing tests, failing tests)
- **maintenance**: Maintainability concerns (complexity, duplication)

### Severity Levels

- **critical** 🔴: Must fix immediately (security, data loss)
- **high** 🟡: Should fix soon (performance, bugs)
- **medium** 🔵: Should fix eventually (maintainability)
- **low** 🟢: Nice to fix (style, minor issues)

### JSON Schema

See [schema/review_schema.json](schema/review_schema.json) for the complete output schema.

Example output structure:

```json
{
  "summary": "Brief overview of findings",
  "issues": [
    {
      "id": "file:line:hash",
      "file": "path/to/file.php",
      "line": 42,
      "type": "security",
      "severity": "critical",
      "message": "Description of the issue",
      "evidence": {
        "source": "phpstan",
        "snippet": "Code excerpt",
        "extra": "Additional context"
      },
      "suggested_fix": {
        "description": "How to fix",
        "patch": "Unified diff",
        "files_touched": ["file.php"]
      },
      "confidence": 0.92,
      "explain": "Why this is an issue"
    }
  ],
  "recommendations": [
    {
      "area": "security",
      "suggestion": "What to do",
      "rationale": "Why do it",
      "priority": "high"
    }
  ],
  "meta": {
    "analyzed_at": "2025-12-02T14:30:45+02:00",
    "tool_versions": {...},
    "duration_seconds": 12.4
  }
}
```

See [examples/sample_review.json](examples/sample_review.json) for a complete example.

---

## 🧪 Examples and Testing

The `examples/` directory contains:

- **sample_review.json**: Example output showing all issue types
- **sample_diff.patch**: Sample Laravel code changes for testing
- **README.md**: Guide to using the examples

Test the agent without a real Laravel project:

```bash
# Apply sample diff
git apply examples/sample_diff.patch

# Run review
python3 review_local.py

# Compare with sample output
diff .local_review.json examples/sample_review.json
```

---

## 🎯 Why This System Works

✅ **Reduces PR Review Time**: Catches issues before human review  
✅ **Improves Code Quality**: Consistent, automated feedback  
✅ **Prevents Issues Early**: Find problems before they reach production  
✅ **100% Private**: All processing happens locally  
✅ **No External Dependencies**: Works completely offline  
✅ **Customizable**: Adapt to your team's standards  
✅ **Actionable**: Provides specific fixes, not just complaints  

---

## 📚 Documentation

- **[USAGE.md](USAGE.md)**: Complete usage guide with examples
- **[docker/localai/README.md](docker/localai/README.md)**: LocalAI setup and model recommendations
- **[examples/README.md](examples/README.md)**: Testing and example files
- **[schema/review_schema.json](schema/review_schema.json)**: JSON output schema

---

## 🔍 Troubleshooting

### LocalAI not responding

```bash
# Check if running
docker-compose ps

# View logs
docker-compose logs -f localai

# Restart
docker-compose restart localai
```

### Tool not found

```bash
# Install PHP tools
composer require --dev phpstan/phpstan squizlabs/php_codesniffer

# Or use absolute paths in config.yaml
tools:
  phpstan:
    path: "/full/path/to/vendor/bin/phpstan"
```

### Slow performance

- Use a smaller model (Phi-3-Mini)
- Reduce `max_tokens` in config
- Increase Docker memory limits
- Use GPU acceleration if available

See [USAGE.md](USAGE.md) for detailed troubleshooting.

---

## 🚀 Advanced Usage

### Custom Prompts

Edit `prompts/system_prompt.txt` to customize AI behavior.

### CI/CD Integration

```yaml
# .github/workflows/code-review.yml
- name: Run code review
  run: python3 review_local.py --commit-range origin/main..HEAD
```

### Multiple Models

```bash
# Test different models
LOCALAI_MODEL=mistral-7b-instruct python3 review_local.py
LOCALAI_MODEL=llama-2-7b-chat python3 review_local.py
```

---

## 📈 Project Structure

```
agentic_code_review/
├── review_local.py          # Main agent script
├── config.yaml              # Configuration
├── requirements.txt         # Python dependencies
├── docker-compose.yml       # LocalAI setup
├── install_hooks.sh         # Git hook installer
├── prompts/
│   └── system_prompt.txt    # AI system prompt
├── schema/
│   └── review_schema.json   # Output JSON schema
├── hooks/
│   └── pre-push            # Git pre-push hook
├── docker/
│   └── localai/
│       └── README.md       # LocalAI setup guide
├── examples/
│   ├── sample_review.json  # Example output
│   ├── sample_diff.patch   # Example diff
│   └── README.md           # Examples guide
├── models/                 # LocalAI models (gitignored)
├── README.md              # This file
└── USAGE.md              # Detailed usage guide
```

---

## 🤝 Contributing

Contributions are welcome! Areas for improvement:

- Support for more languages (JavaScript, Python, Go)
- Additional Laravel-specific rules
- Performance optimizations
- Better error handling
- UI/dashboard for review history

---

## 📚 License

Internal use only.  
Check model licensing for LocalAI-compatible LLMs.

---

## 💬 Support

- **Documentation**: See [USAGE.md](USAGE.md)
- **Examples**: Check [examples/](examples/)
- **Issues**: Check logs in `.local_review.log`
- **LocalAI**: https://localai.io/

---

## 🎉 Getting Started

1. ✅ Install dependencies: `pip install -r requirements.txt`
2. ✅ Start LocalAI: `docker-compose up -d`
3. ✅ Download a model (see [docker/localai/README.md](docker/localai/README.md))
4. ✅ Run your first review: `python3 review_local.py`
5. ✅ Install Git hooks: `./install_hooks.sh`
6. ✅ Read the full guide: [USAGE.md](USAGE.md)

**Happy reviewing! 🚀**

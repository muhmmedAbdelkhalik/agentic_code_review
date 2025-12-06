# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an AI-powered code review agent that runs 100% locally using Ollama/LocalAI. It analyzes PHP/Laravel code for security vulnerabilities, performance issues, bugs, and code quality problems before code is pushed to remote repositories.

## Common Commands

### Quick Start (Using Agent CLI)

The `agent` CLI provides convenient shortcuts:

```bash
# Health check - validate your setup
agent doctor

# Run code review
agent review

# Review specific commit range
agent review --range HEAD~1..HEAD

# Install git hooks
agent install-hooks

# Run automated tests
agent test

# Show help
agent help
```

### Running Reviews

```bash
# Using agent CLI (recommended)
agent review
agent review --range HEAD~1..HEAD
agent review --verbose

# Using Python directly (alternative)
python3 review_local.py
python3 review_local.py --commit-range HEAD~1..HEAD
python3 review_local.py --verbose

# View review results
cat .local_review.json
cat .local_review.log
```

### Health Check

Validate system configuration and dependencies:

```bash
# Using agent CLI (recommended)
agent doctor
agent doctor --json

# Using Python directly (alternative)
python3 review_local.py --health-check
python3 review_local.py --health-check-json
```

The health check validates:
- Python dependencies (requests, pyyaml, jsonschema, colorama, python-dotenv)
- Configuration file and settings (URL format, timeout ranges, temperature)
- Ollama server connectivity and model availability
- PHP tools (PHPStan, PHPCS, PHPUnit) if enabled
- Filesystem access (schema files, output directory, git repository)

Exit codes:
- `0` = Healthy (all critical checks passed)
- `1` = Degraded (warnings present) or Unhealthy (critical failures)

### Git Hook Integration

The agent runs automatically on `git push` via a pre-push hook:

```bash
# Normal push (review runs automatically)
git push origin main

# Skip review for this push
SKIP_REVIEW=1 git push origin main
# OR
git push --no-verify origin main
```

### Development and Testing

```bash
# Install Python dependencies
pip3 install -r requirements.txt

# Check system health
agent doctor

# Run automatic tests (tests against PHP test files)
agent test
# OR
./tests/scripts/auto_test.sh

# Check Ollama is running
curl http://localhost:11434/api/tags

# Start Ollama if needed
ollama serve

# Pull the AI model
ollama pull qwen2.5-coder:7b
```

### Installation

```bash
# Install git hooks using agent CLI
agent install-hooks

# OR install into a target Laravel/PHP project
./install.sh /path/to/target/project

# Install Git hooks in current project
./install_hooks.sh
```

## Architecture

### Core Components

1. **review_local.py** - Main agent script with modular class architecture:
   - `Config`: Loads and manages configuration from config.yaml and environment variables
   - `GitDiffCollector`: Collects git diff information and changed file lists
   - `ToolRunner`: Runs PHP analysis tools (PHPStan, PHPCS, PHPUnit) and captures output
   - `LocalAIClient`: Communicates with Ollama/LocalAI API to get AI-powered reviews
   - `PromptBuilder`: Constructs prompts for the LLM including system prompt, diff, and tool outputs
   - `ReviewValidator`: Validates LLM output against JSON schema
   - `ReviewPrinter`: Prints color-coded review summaries to terminal
   - `ReviewAgent`: Orchestrates the entire review workflow

2. **prompts/system_prompt.txt** - Comprehensive instructions for the LLM including:
   - Mandatory security checklist (SQL injection, mass assignment, XSS, null checks, N+1 queries, validation)
   - Severity classification rules (critical/high/medium/low)
   - Required JSON output schema with strict validation
   - Engineering checklist for systematic code analysis

3. **schema/review_schema.json** - JSON schema that validates LLM output structure ensuring:
   - All required fields are present (id, file, line, type, severity, message, evidence, suggested_fix, confidence, explain)
   - Proper enum values for severity and type
   - Correct data types for all fields

4. **config.yaml** - Configuration file controlling:
   - Ollama/LocalAI server URL and model selection (default: qwen2.5-coder:7b)
   - PHP tool enablement (PHPStan, PHPCS, PHPUnit)
   - Review behavior (block_on_critical, max_issues, min_confidence)
   - Output file paths and logging settings

5. **hooks/pre-push** - Git pre-push hook that:
   - Checks if Ollama/LocalAI is running
   - Runs review_local.py with appropriate commit range
   - Blocks push if critical/high severity issues found (when block_on_critical=true)
   - Can be bypassed with SKIP_REVIEW=1 environment variable

### Review Workflow

1. GitDiffCollector extracts git diff and identifies changed PHP files
2. ToolRunner executes PHPStan, PHPCS, and PHPUnit on changed files
3. PromptBuilder combines system prompt, diff, and tool outputs into a structured prompt
4. LocalAIClient sends prompt to Ollama (qwen2.5-coder:7b model) via native Ollama API
5. LLM analyzes code using mandatory security checklist and returns structured JSON
6. ReviewValidator ensures JSON matches required schema
7. Review results saved to .local_review.json
8. ReviewPrinter displays color-coded summary in terminal
9. Pre-push hook checks for critical/high issues and blocks push if configured

### Key Design Patterns

- **API Flexibility**: Supports both Ollama native API (port 11434) and OpenAI-compatible endpoints (port 8080)
- **Dual Mode Operation**: Can run as standalone CLI tool or automated git hook
- **Structured Output**: LLM must return strict JSON matching schema (no conversational text)
- **Security-First**: Mandatory security checklist ensures critical vulnerabilities are never missed
- **Fail-Safe**: Review failures don't block pushes unless critical issues are found in .local_review.json

## Configuration

### Critical Settings in config.yaml

```yaml
localai:
  url: "http://localhost:11434"  # Ollama endpoint
  model: "qwen2.5-coder:7b"      # AI model for code analysis

review:
  block_on_critical: true         # Block git push on critical/high issues
  min_confidence: 0.5             # Minimum confidence threshold (0.0-1.0)

tools:
  phpstan:
    enabled: true                 # Enable static analysis
  phpcs:
    enabled: true                 # Enable style checking
  phpunit:
    enabled: true                 # Enable test running
```

### Environment Variable Overrides

The following environment variables override config.yaml settings:
- `LOCALAI_URL` - Override Ollama/LocalAI server URL
- `LOCALAI_MODEL` - Override model name
- `LOCALAI_TEMPERATURE` - Override temperature setting
- `OUTPUT_FILE` - Override output file path
- `VERBOSE` - Enable verbose logging (true/false)
- `SKIP_REVIEW` - Skip review for this run (git hook only)
- `BLOCK_ON_CRITICAL` - Override block_on_critical setting (true/false)

## Security Detection Rules

The agent uses explicit pattern matching for security issues:

1. **Mass Assignment (CRITICAL)**: `$model->update($request->all())`, `Model::create($request->all())`
2. **SQL Injection (CRITICAL)**: `DB::raw()` with string interpolation, `whereRaw()` without bindings
3. **Missing Null Checks (CRITICAL)**: `Model::find($id)` followed by method calls without null check
4. **XSS (CRITICAL)**: `{!! $var !!}` in Blade templates without sanitization
5. **N+1 Queries (HIGH)**: Relationship access inside loops without eager loading
6. **Missing Validation (HIGH)**: Request data used without FormRequest validation

Severity mapping is hardcoded in the system prompt to prevent misclassification.

## Output Format

Review results in .local_review.json follow this structure:

```json
{
  "summary": "string",
  "issues": [{
    "id": "file:line:hash",
    "file": "path/to/file.php",
    "line": 42,
    "type": "security|performance|style|bug|test|maintenance",
    "severity": "critical|high|medium|low",
    "message": "Human-readable issue description",
    "evidence": {
      "source": "git_diff|phpstan|phpcs|phpunit",
      "snippet": "code excerpt (max 400 chars)"
    },
    "suggested_fix": {
      "description": "How to fix this issue",
      "patch": "unified diff or code snippet",
      "files_touched": ["file.php"]
    },
    "confidence": 0.95,
    "explain": "One-line rationale"
  }],
  "recommendations": [{
    "area": "tests|ci|security|style|architecture",
    "suggestion": "Recommendation text",
    "rationale": "Why this matters",
    "priority": "high|medium|low"
  }],
  "meta": {
    "analyzed_at": "ISO8601 timestamp",
    "tool_versions": {
      "phpstan": "version",
      "phpcs": "version",
      "phpunit": "version",
      "localai_model": "qwen2.5-coder:7b"
    },
    "duration_seconds": 58.32
  }
}
```

## Testing Files

Test PHP files in the repository root are used for validation:
- `test_cases.php` - Basic security vulnerability test cases
- `test_cases_new.php`, `test_cases_new_1.php`, `test_cases_new_2.php` - Additional test cases
- `test_block_push.php` - Comprehensive test file with intentional vulnerabilities
- `auto_test.sh` - Automated testing script that validates detection rate

These files contain intentional security issues for testing the agent's detection capabilities.

## Model Selection

The agent uses **qwen2.5-coder:7b** by default (replaces older gemma:2b):
- Specifically trained for code analysis and security detection
- Better at identifying Laravel-specific patterns
- ~60 second average review time
- Runs locally via Ollama (no cloud dependencies)
- ~4.7GB download size

## Development Notes

- The LLM temperature is set to 0.2 for deterministic, consistent output
- Max tokens is 4000 to allow for detailed issue reports
- The agent will analyze the ENTIRE file content in diffs, not just changed lines
- JSON schema validation prevents malformed LLM outputs from breaking the workflow
- Pre-push hook uses Python inline scripts to parse JSON (no jq dependency)
- Review failures (script errors) don't block pushes unless critical issues are in .local_review.json

## Troubleshooting

### Use Health Check First
When encountering issues, always run the health check first:
```bash
python3 review_local.py --health-check
```
This will identify most configuration problems and provide actionable remediation steps.

### Common Issues

**Ollama Not Running**
```
❌ Ollama server connectivity: Cannot connect to http://localhost:11434
→ Fix: Start Ollama with: ollama serve
```

**Model Not Pulled**
```
❌ Model availability: Model 'qwen2.5-coder:7b' not found
→ Fix: Run: ollama pull qwen2.5-coder:7b
```

**PHP Tools Missing**
```
⚠ PHP tool: phpstan: phpstan not found at 'phpstan'
→ Fix: Install phpstan or disable in config.yaml
```
Note: PHP tools are optional - the agent can run with Ollama alone.

**Permission Errors**
```
❌ Output directory writable: Cannot write to .
→ Fix: Fix permissions: chmod +w .
```

**Configuration Issues**
- Invalid URL format: Ensure `ollama.url` starts with `http://` or `https://`
- Timeout out of range: Keep between 1-600 seconds
- Temperature out of range: Keep between 0.0-1.0

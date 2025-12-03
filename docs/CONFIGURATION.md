# Configuration Guide

Complete guide to configuring the LocalAI Code Review Agent.

## Configuration Files

The agent uses two configuration files:

- **`config.yaml`** - Main configuration (required)
- **`.env`** - Environment overrides (optional)

## config.yaml Structure

### Ollama/LocalAI Settings

```yaml
localai:
  url: "http://localhost:11434"      # Ollama server URL
  model: "qwen2.5-coder:7b"          # LLM model to use
  temperature: 0.2                    # Lower = more deterministic (0.0-1.0)
  max_tokens: 4000                    # Maximum response length
  timeout: 120                        # Request timeout in seconds
```

**Available Models:**
- `qwen2.5-coder:7b` - Recommended (best security detection)
- `qwen2.5-coder:3b` - Faster alternative
- `codellama:7b` - Meta's code model
- `deepseek-coder:6.7b` - Another strong option
- `gemma:2b` - Fastest but less accurate

**Performance vs Accuracy:**
| Model | Size | Speed | Detection Rate |
|-------|------|-------|----------------|
| qwen2.5-coder:7b | 4.7GB | ~60s | 100% (4/4) |
| qwen2.5-coder:3b | 2GB | ~30s | ~75% (3/4) |
| gemma:2b | 1.5GB | ~9s | 25% (1/4) |

### PHP Analysis Tools

```yaml
tools:
  phpstan:
    enabled: true                    # Enable/disable PHPStan
    path: "phpstan"                  # Command or full path
    args:                            # Command-line arguments
      - "analyse"
      - "--error-format=json"
      - "--no-progress"
    level: 5                         # PHPStan level (0-9)
    
  phpcs:
    enabled: true                    # Enable/disable PHPCS
    path: "phpcs"                    # Command or full path
    args:
      - "--report=json"
      - "--standard=PSR12"           # Coding standard
    standard: "PSR12"                # PSR1, PSR2, PSR12, or custom
    
  phpunit:
    enabled: false                   # Enable/disable PHPUnit
    path: "phpunit"                  # Command or full path
    args:
      - "--testdox"
      - "--colors=never"
```

**Tool Paths:**
- Relative: `"phpstan"` (searches in PATH)
- Absolute: `"/full/path/to/vendor/bin/phpstan"`
- Composer: `"vendor/bin/phpstan"`

**Disabling Tools:**
Set `enabled: false` to skip a tool. The agent will still work with other tools.

### Output Settings

```yaml
output:
  file: ".local_review.json"         # Review output file
  log_file: ".local_review.log"      # Debug log file
  verbose: false                     # Enable verbose logging
```

**Output Files:**
- `.local_review.json` - Structured review results
- `.local_review.log` - Debug logs and errors

These files are automatically added to `.gitignore`.

### Git Settings

```yaml
git:
  diff_context: 5                    # Lines of context around changes
  target_branch: "main"              # Default branch to compare against
```

**diff_context:**
- More context = better analysis
- More context = slower performance
- Recommended: 3-10 lines

**target_branch:**
- Branch to compare against when no commit range specified
- Common values: `main`, `master`, `develop`

### Review Behavior

```yaml
review:
  max_issues: 100                    # Maximum issues to report
  block_on_critical: true            # Block push on critical issues
  min_confidence: 0.5                # Minimum confidence (0.0-1.0)
```

**block_on_critical:**
- `true` - Block pushes if critical/high severity issues found
- `false` - Always allow pushes (show warnings only)

**min_confidence:**
- `0.0` - Report all issues (may have false positives)
- `0.5` - Balanced (recommended)
- `0.8` - Only high-confidence issues (may miss some)

### Severity Display

```yaml
severity:
  critical:
    color: "red"
    symbol: "🔴"
  high:
    color: "yellow"
    symbol: "🟡"
  medium:
    color: "blue"
    symbol: "🔵"
  low:
    color: "green"
    symbol: "🟢"
```

Customize colors and symbols for terminal output.

## Environment Variables (.env)

Override config.yaml values with environment variables:

```bash
# Ollama Settings
LOCALAI_URL=http://localhost:11434
LOCALAI_MODEL=qwen2.5-coder:7b
LOCALAI_TEMPERATURE=0.2
LOCALAI_TIMEOUT=120

# Review Behavior
BLOCK_ON_CRITICAL=true
VERBOSE=false

# Tool Paths
PHPSTAN_PATH=/custom/path/to/phpstan
PHPCS_PATH=/custom/path/to/phpcs
```

**Priority:**
1. Environment variables (highest)
2. .env file
3. config.yaml (lowest)

## Common Configurations

### For Fast Reviews (Development)

```yaml
localai:
  model: "qwen2.5-coder:3b"          # Smaller, faster model
  timeout: 60

tools:
  phpstan:
    enabled: true
  phpcs:
    enabled: false                   # Skip style checks
  phpunit:
    enabled: false                   # Skip tests

review:
  block_on_critical: false           # Don't block pushes
```

### For Thorough Reviews (CI/Production)

```yaml
localai:
  model: "qwen2.5-coder:7b"          # Best detection
  timeout: 180                       # Allow more time

tools:
  phpstan:
    enabled: true
    level: 8                         # Strictest level
  phpcs:
    enabled: true
  phpunit:
    enabled: true                    # Run tests

review:
  block_on_critical: true            # Block on issues
  min_confidence: 0.7                # Higher confidence
```

### For Legacy Projects

```yaml
tools:
  phpstan:
    enabled: true
    level: 0                         # Lowest level
  phpcs:
    enabled: false                   # May have many violations
  phpunit:
    enabled: false

review:
  block_on_critical: false           # Don't block initially
  max_issues: 50                     # Limit overwhelming output
```

## Model Selection Guide

### qwen2.5-coder:7b (Recommended)

**Pros:**
- Best security vulnerability detection
- Specifically trained for code
- Excellent at Laravel patterns
- 100% detection rate in tests

**Cons:**
- Slower (~60 seconds per review)
- Larger download (4.7GB)
- Requires more RAM

**Use when:**
- Security is critical
- You can wait 60 seconds
- You have 8GB+ RAM

### qwen2.5-coder:3b

**Pros:**
- Good balance of speed and accuracy
- Still code-specific
- Smaller download (2GB)

**Cons:**
- Slightly lower detection rate (~75%)
- May miss some subtle issues

**Use when:**
- You want faster reviews (~30s)
- RAM is limited
- Good enough accuracy

### gemma:2b

**Pros:**
- Very fast (~9 seconds)
- Small download (1.5GB)
- Low RAM usage

**Cons:**
- Poor detection rate (25%)
- Misses most security issues
- Not recommended

**Use when:**
- Only for testing/demo
- Speed is critical
- Not for production use

## Performance Tuning

### Reduce Review Time

1. **Use smaller model:**
   ```yaml
   model: "qwen2.5-coder:3b"
   ```

2. **Reduce context:**
   ```yaml
   git:
     diff_context: 3
   ```

3. **Disable slow tools:**
   ```yaml
   tools:
     phpunit:
       enabled: false
   ```

4. **Reduce max_tokens:**
   ```yaml
   localai:
     max_tokens: 2000
   ```

### Improve Detection

1. **Use best model:**
   ```yaml
   model: "qwen2.5-coder:7b"
   ```

2. **Increase context:**
   ```yaml
   git:
     diff_context: 10
   ```

3. **Enable all tools:**
   ```yaml
   tools:
     phpstan:
       enabled: true
       level: 8
     phpcs:
       enabled: true
     phpunit:
       enabled: true
   ```

4. **Lower confidence threshold:**
   ```yaml
   review:
     min_confidence: 0.3
   ```

## Troubleshooting Configuration

### Model not found

```bash
# Download the model
ollama pull qwen2.5-coder:7b

# List installed models
ollama list
```

### Tool not found

```yaml
# Use absolute path
tools:
  phpstan:
    path: "/full/path/to/vendor/bin/phpstan"
```

Or install the tool:
```bash
composer require --dev phpstan/phpstan
```

### Timeout errors

```yaml
# Increase timeout
localai:
  timeout: 300  # 5 minutes
```

### Out of memory

```yaml
# Use smaller model
localai:
  model: "qwen2.5-coder:3b"
  max_tokens: 2000
```

## Advanced Configuration

### Custom Prompts

Edit `prompts/system_prompt.txt` to customize AI behavior.

### Custom Schema

Edit `schema/review_schema.json` to change output format.

### Multiple Projects

Each project can have its own `config.yaml`:

```bash
project-a/
  ├── config.yaml  # Fast reviews
  └── ...

project-b/
  ├── config.yaml  # Thorough reviews
  └── ...
```

### CI/CD Integration

```yaml
# .github/workflows/code-review.yml
- name: Run code review
  env:
    BLOCK_ON_CRITICAL: true
    VERBOSE: true
  run: python3 review_local.py --commit-range origin/main..HEAD
```

## Configuration Validation

Test your configuration:

```bash
# Run with verbose output
python3 review_local.py --verbose

# Check logs
cat .local_review.log
```

## See Also

- [Usage Guide](USAGE.md) - How to use the agent
- [Troubleshooting](TROUBLESHOOTING.md) - Common issues
- [Upgrading](UPGRADE.md) - Upgrade instructions


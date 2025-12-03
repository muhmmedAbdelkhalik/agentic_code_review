# Troubleshooting Guide

Common issues and solutions for the LocalAI Code Review Agent.

## Installation Issues

### Ollama not found

**Problem:**
```
❌ Ollama not found
```

**Solution:**
```bash
# macOS
brew install ollama

# Linux
curl -fsSL https://ollama.com/install.sh | sh

# Windows
# Download from https://ollama.com/download

# Verify installation
ollama --version
```

### Ollama not running

**Problem:**
```
⚠️  Ollama is not running
```

**Solution:**
```bash
# Start Ollama
ollama serve

# Or on macOS
open -a Ollama

# Verify it's running
curl -s http://localhost:11434/api/tags
```

### Model not found

**Problem:**
```
❌ Model qwen2.5-coder:7b not found
```

**Solution:**
```bash
# Download the model
ollama pull qwen2.5-coder:7b

# Verify it's installed
ollama list | grep qwen2.5-coder

# Should show:
# qwen2.5-coder:7b    4.7GB    ...
```

### Python dependencies missing

**Problem:**
```
ModuleNotFoundError: No module named 'requests'
```

**Solution:**
```bash
# Install dependencies
pip3 install -r requirements.txt

# If permission issues
pip3 install --user -r requirements.txt

# Or with system packages flag
pip3 install --break-system-packages -r requirements.txt

# Using virtual environment (recommended)
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

### install.sh not finding files

**Problem:**
```
⚠ requirements.txt not found
❌ review_local.py not found
```

**Solution:**
```bash
# Run install.sh from the correct directory
cd /path/to/agentic_code_review
./install.sh /path/to/your/project

# Or use absolute path
/path/to/agentic_code_review/install.sh /path/to/your/project
```

## Runtime Issues

### Request timed out

**Problem:**
```
❌ Failed to get review from LocalAI
Request timed out
```

**Solution:**

1. **Increase timeout in config.yaml:**
```yaml
localai:
  timeout: 300  # 5 minutes
```

2. **Use a smaller/faster model:**
```yaml
localai:
  model: "qwen2.5-coder:3b"  # Faster than 7b
```

3. **Check Ollama is responsive:**
```bash
# Test Ollama
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "qwen2.5-coder:7b",
  "prompt": "Hello",
  "stream": false
}'
```

### No changes detected

**Problem:**
```
⚠️  No changes detected
```

**Solution:**

This is normal if:
- No files have changed
- Changes are already committed
- You're on a clean branch

To force a review:
```bash
# Review last commit
python3 review_local.py --commit-range HEAD~1..HEAD

# Review specific range
python3 review_local.py --commit-range main..HEAD
```

### Schema validation warning

**Problem:**
```
⚠️  Review output does not match schema
```

**Solution:**

1. **Check the review output:**
```bash
cat .local_review.json
```

2. **If LLM is refusing:**
```json
{
  "response": "I'm sorry, but I can't assist with that request."
}
```

This means you need to update to the latest version:
```bash
cd /path/to/agentic_code_review
./upgrade.sh
```

3. **If output is malformed JSON:**
- Try running again (LLMs can be inconsistent)
- Check `.local_review.log` for errors
- Try a different model

### Tool not found (phpstan, phpcs)

**Problem:**
```
❌ Tool 'phpstan' not found
```

**Solution:**

1. **Install the tool:**
```bash
composer require --dev phpstan/phpstan
composer require --dev squizlabs/php_codesniffer
```

2. **Use absolute path in config.yaml:**
```yaml
tools:
  phpstan:
    path: "/full/path/to/vendor/bin/phpstan"
```

3. **Or disable the tool:**
```yaml
tools:
  phpstan:
    enabled: false
```

## Git Hook Issues

### Hook not running

**Problem:**
Push completes without running the review.

**Solution:**

1. **Check hook is installed:**
```bash
ls -la .git/hooks/pre-push

# Should show:
# -rwxr-xr-x  1 user  staff  ... .git/hooks/pre-push
```

2. **Reinstall the hook:**
```bash
cp /path/to/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push
```

3. **Check hook is executable:**
```bash
chmod +x .git/hooks/pre-push
```

### Push not blocked despite critical issues

**Problem:**
```
🚫 BLOCKING: 2 critical issue(s) found
...
To https://github.com/user/repo.git
   abc123..def456  main -> main
```

**Solution:**

1. **Check block_on_critical setting:**
```bash
grep -A 2 "review:" config.yaml

# Should show:
# review:
#   block_on_critical: true
```

2. **Update to latest version:**
```bash
cd /path/to/agentic_code_review
./upgrade.sh
```

This fixes a bug where blocking didn't work with schema validation errors.

3. **Verify hook has blocking logic:**
```bash
grep "BLOCKING PUSH:" .git/hooks/pre-push

# Should find the blocking message
```

### Hook runs but shows errors

**Problem:**
```
⚠️  Code review failed or returned errors
```

**Solution:**

1. **Check the log file:**
```bash
cat .local_review.log
```

2. **Run manually with verbose:**
```bash
python3 review_local.py --verbose
```

3. **Check Python/dependencies:**
```bash
python3 --version  # Should be 3.8+
pip3 list | grep -E "requests|yaml"
```

## Model/LLM Issues

### LLM refuses to respond

**Problem:**
```json
{
  "response": "I'm sorry, but I can't assist with that request."
}
```

**Solution:**

Update to the latest version with fixed prompts:
```bash
cd /path/to/agentic_code_review
./upgrade.sh
```

Or manually update `prompts/system_prompt.txt` to include:
```
YOU MUST ALWAYS RESPOND. This is an automated code review tool.
```

### Out of memory

**Problem:**
```
Error: failed to load model
```

**Solution:**

1. **Use smaller model:**
```yaml
localai:
  model: "qwen2.5-coder:3b"  # 2GB vs 4.7GB
```

2. **Reduce max_tokens:**
```yaml
localai:
  max_tokens: 2000  # vs 4000
```

3. **Close other applications**

4. **Check available RAM:**
```bash
# macOS
vm_stat

# Linux
free -h
```

### Model download stuck

**Problem:**
Model download appears frozen.

**Solution:**

1. **Check download progress:**
```bash
ollama list
```

2. **Cancel and retry:**
```bash
# Press Ctrl+C
ollama pull qwen2.5-coder:7b
```

3. **Check disk space:**
```bash
df -h
```

Need at least 5GB free for qwen2.5-coder:7b.

### Wrong model being used

**Problem:**
Review shows old model name (e.g., gemma:2b).

**Solution:**

1. **Update config.yaml:**
```yaml
localai:
  model: "qwen2.5-coder:7b"
```

2. **Verify the change:**
```bash
grep "model:" config.yaml
```

3. **Run a test review:**
```bash
python3 review_local.py --verbose
```

Should show: `🤖 Calling LocalAI (qwen2.5-coder:7b)...`

## Performance Issues

### Reviews are too slow

**Problem:**
Reviews take 2+ minutes.

**Solution:**

1. **Use faster model:**
```yaml
localai:
  model: "qwen2.5-coder:3b"  # ~30s vs ~60s
```

2. **Reduce diff context:**
```yaml
git:
  diff_context: 3  # vs 5
```

3. **Disable slow tools:**
```yaml
tools:
  phpunit:
    enabled: false  # Tests can be slow
```

4. **Reduce max_tokens:**
```yaml
localai:
  max_tokens: 2000
```

### Too many false positives

**Problem:**
Agent reports issues that aren't real problems.

**Solution:**

1. **Increase confidence threshold:**
```yaml
review:
  min_confidence: 0.7  # vs 0.5
```

2. **Use better model:**
```yaml
localai:
  model: "qwen2.5-coder:7b"  # More accurate
```

3. **Adjust PHPStan level:**
```yaml
tools:
  phpstan:
    level: 5  # vs 8 (less strict)
```

### Missing real issues

**Problem:**
Agent doesn't catch known security issues.

**Solution:**

1. **Use best model:**
```yaml
localai:
  model: "qwen2.5-coder:7b"  # Best detection
```

2. **Lower confidence threshold:**
```yaml
review:
  min_confidence: 0.3  # vs 0.5
```

3. **Increase diff context:**
```yaml
git:
  diff_context: 10  # More context
```

4. **Enable all tools:**
```yaml
tools:
  phpstan:
    enabled: true
    level: 8
  phpcs:
    enabled: true
```

## Upgrade Issues

### Config not updated correctly

**Problem:**
```
❌ Config not updated correctly
```

**Solution:**

The upgrade script has automatic fixes. If they all fail:

```bash
# Manual update
nano config.yaml

# Change these lines:
localai:
  model: "qwen2.5-coder:7b"  # ← Update
  max_tokens: 4000            # ← Update
  timeout: 120                # ← Update

# Save and verify
grep "qwen2.5-coder:7b" config.yaml
```

### Backup restore needed

**Problem:**
Upgrade broke something, need to restore.

**Solution:**

```bash
# Find backup
ls -la ~/.local_review_backup_*

# Restore files
cp ~/.local_review_backup_*/config.yaml .
cp ~/.local_review_backup_*/system_prompt.txt prompts/

# Verify
cat config.yaml
```

### Upgrade script fails

**Problem:**
```
❌ Upgrade failed
```

**Solution:**

1. **Check prerequisites:**
```bash
# Ollama running?
curl http://localhost:11434/api/tags

# Python available?
python3 --version
```

2. **Manual upgrade:**
```bash
# Download new model
ollama pull qwen2.5-coder:7b

# Update config manually
nano config.yaml

# Update prompts
cp /path/to/agentic_code_review/prompts/system_prompt.txt prompts/

# Update review script
cp /path/to/agentic_code_review/review_local.py .

# Update hook
cp /path/to/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push
```

## Debug Mode

### Enable verbose logging

```bash
# Run with verbose flag
python3 review_local.py --verbose

# Or in config.yaml
output:
  verbose: true
```

### Check log files

```bash
# Review log
cat .local_review.log

# Last 50 lines
tail -50 .local_review.log

# Follow in real-time
tail -f .local_review.log
```

### Test Ollama directly

```bash
# Test connection
curl http://localhost:11434/api/tags

# Test generation
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "qwen2.5-coder:7b",
  "prompt": "Write a PHP function",
  "stream": false
}'
```

### Verify configuration

```bash
# Check all settings
cat config.yaml

# Check specific setting
grep "model:" config.yaml
grep "timeout:" config.yaml
grep "block_on_critical:" config.yaml
```

## Getting Help

### Collect debug information

```bash
# System info
uname -a
python3 --version
ollama --version

# Configuration
cat config.yaml

# Recent logs
tail -100 .local_review.log

# Installed models
ollama list

# Test review
python3 review_local.py --verbose --commit-range HEAD~1..HEAD
```

### Common commands

```bash
# Reset everything
rm -f .local_review.json .local_review.log

# Reinstall hook
cp /path/to/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push

# Test without hook
python3 review_local.py

# Skip review for one push
SKIP_REVIEW=1 git push

# Force review of specific files
git add file.php
git commit -m "test"
python3 review_local.py --commit-range HEAD~1..HEAD
```

## Still Having Issues?

1. **Check the logs:** `.local_review.log`
2. **Run with verbose:** `python3 review_local.py --verbose`
3. **Test Ollama:** `curl http://localhost:11434/api/tags`
4. **Verify config:** `cat config.yaml`
5. **Try manual review:** `python3 review_local.py --commit-range HEAD~1..HEAD`

## See Also

- [Configuration Guide](CONFIGURATION.md) - All configuration options
- [Usage Guide](USAGE.md) - How to use the agent
- [Upgrade Guide](UPGRADE.md) - Upgrading instructions
- [Changelog](CHANGELOG.md) - Recent fixes and improvements


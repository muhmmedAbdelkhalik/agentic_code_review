# 🔧 Upgrade Troubleshooting

Common issues during upgrade and how to fix them.

---

## ❌ Config not updated correctly

### Problem
After running `./upgrade.sh`, you see:
```
❌ Config not updated correctly
```

### Automatic Fix
The upgrade script now automatically attempts to fix this issue using multiple methods:

1. **First attempt**: Uses `sed` with multiple patterns
2. **Second attempt**: Uses Python to update the YAML
3. **Manual fix**: Shows instructions if automatic fixes fail

### Manual Fix

If automatic fixes fail, update `config.yaml` manually:

```bash
# 1. Open config.yaml
nano config.yaml

# 2. Find the localai section and update these lines:
```

```yaml
localai:
  url: "http://localhost:11434"
  model: "qwen2.5-coder:7b"  # ← Change this
  temperature: 0.2
  max_tokens: 4000            # ← Change this
  timeout: 120                # ← Change this
```

```bash
# 3. Save and verify
grep "qwen2.5-coder:7b" config.yaml
```

---

## ❌ Model not found

### Problem
```
❌ Model not found
```

### Solution
```bash
# Download the model manually
ollama pull qwen2.5-coder:7b

# Verify it's downloaded
ollama list | grep qwen2.5-coder
```

---

## ⚠️ Ollama is not running

### Problem
```
⚠️ Ollama is not running
```

### Solution

**macOS:**
```bash
open -a Ollama
```

**Linux:**
```bash
ollama serve &
```

**Verify:**
```bash
curl -s http://localhost:11434/api/tags
```

---

## ❌ sed: command not found

### Problem
The `sed` command is not available on your system.

### Solution
The upgrade script will automatically fall back to Python. If that fails:

```bash
# Install sed (Linux)
sudo apt-get install sed

# Or update config manually (see above)
```

---

## ❌ Python update failed

### Problem
Both `sed` and Python failed to update the config.

### Solution
Update manually:

```bash
# 1. Backup current config
cp config.yaml config.yaml.backup

# 2. Use your favorite editor
vim config.yaml
# or
nano config.yaml
# or
code config.yaml

# 3. Change these lines in the localai section:
#    model: "qwen2.5-coder:7b"
#    max_tokens: 4000
#    timeout: 120

# 4. Verify
grep "model:" config.yaml
```

---

## ❌ Could not pull from repository

### Problem
```
⚠️ Could not pull from repository (using existing prompts)
```

### Solution
Update prompts manually:

```bash
# Download latest prompts
curl -sSL https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main/prompts/system_prompt.txt -o prompts/system_prompt.txt

# Verify
head -20 prompts/system_prompt.txt
```

---

## ❌ Permission denied: ./upgrade.sh

### Problem
```
bash: ./upgrade.sh: Permission denied
```

### Solution
```bash
# Make it executable
chmod +x upgrade.sh

# Run again
./upgrade.sh
```

---

## ❌ Directory not found

### Problem
```
❌ Directory not found: /path/to/project
```

### Solution
```bash
# Check if directory exists
ls -la /path/to/project

# Use absolute path
./upgrade.sh /full/path/to/your/project

# Or cd into the project first
cd /path/to/project
./upgrade.sh
```

---

## ⚠️ Backup location not found

### Problem
You want to rollback but can't find the backup.

### Solution
```bash
# List all backups
ls -la backup_*/

# Restore from backup
cp backup_YYYYMMDD_HHMMSS/config.yaml config.yaml

# Or restore from .backup files
cp config.yaml.backup config.yaml
```

---

## ❌ Review fails after upgrade

### Problem
After upgrade, running `python3 review_local.py` fails.

### Solution

**1. Check model is loaded:**
```bash
ollama list | grep qwen2.5-coder:7b
```

**2. Check config is correct:**
```bash
cat config.yaml | grep -A 5 "localai:"
```

**3. Test Ollama directly:**
```bash
curl -s http://localhost:11434/api/generate -d '{
  "model": "qwen2.5-coder:7b",
  "prompt": "Hello",
  "stream": false
}' | jq .
```

**4. Check logs:**
```bash
tail -50 .local_review.log
```

---

## 🔄 Complete Rollback

If you need to completely rollback the upgrade:

```bash
# 1. Find your backup
ls -la backup_*/

# 2. Restore all files
cp backup_YYYYMMDD_HHMMSS/config.yaml config.yaml
cp backup_YYYYMMDD_HHMMSS/system_prompt.txt prompts/system_prompt.txt

# 3. Switch back to old model
ollama pull gemma:2b

# 4. Verify
grep "model:" config.yaml
python3 review_local.py --commit-range HEAD~1..HEAD
```

---

## 🆘 Still Having Issues?

### Debug Mode

Run the upgrade script with debug output:

```bash
bash -x upgrade.sh 2>&1 | tee upgrade_debug.log
```

This will:
- Show every command being executed
- Save output to `upgrade_debug.log`
- Help identify where it's failing

### Get Help

1. **Check the logs:**
   ```bash
   cat upgrade_debug.log
   cat .local_review.log
   ```

2. **Verify prerequisites:**
   ```bash
   python3 --version
   ollama list
   curl http://localhost:11434/api/tags
   ```

3. **Open an issue:**
   - Include the error message
   - Include relevant log files
   - Include your OS and versions

---

## ✅ Verification After Fix

After fixing any issue, verify everything works:

```bash
# 1. Check model
ollama list | grep qwen2.5-coder:7b
# Expected: qwen2.5-coder:7b    dae161e27b0e    4.7 GB

# 2. Check config
grep "qwen2.5-coder:7b" config.yaml
# Expected: model: "qwen2.5-coder:7b"

# 3. Test review
python3 review_local.py --commit-range HEAD~1..HEAD
# Expected: Should complete without errors

# 4. Check output
cat .local_review.json | jq '.meta.tool_versions.localai_model'
# Expected: "qwen2.5-coder:7b"
```

---

## 📞 Contact

If you continue to have issues:

- 📖 Check [UPGRADE.md](UPGRADE.md) for full documentation
- 🐛 Open an issue on GitHub
- 💬 Ask in the project discussions

---

**Most issues are fixed automatically by the upgrade script!**


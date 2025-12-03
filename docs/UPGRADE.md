# 🔄 Upgrade Guide

## Upgrading to qwen2.5-coder:7b

If you're using the old `gemma:2b` model, upgrade to `qwen2.5-coder:7b` for **4x better issue detection**!

---

## 🚀 Automatic Upgrade (Recommended)

Run the automated upgrade script:

```bash
cd /path/to/agentic_code_review
./upgrade.sh
```

**That's it!** The script will:
- ✅ Check prerequisites (Ollama, Python)
- ✅ Backup your current configuration
- ✅ Download the new model (~4.7GB)
- ✅ Update `config.yaml` automatically
- ✅ Update prompts with security checklist
- ✅ Verify everything works

**Time required**: 5-15 minutes (mostly downloading the model)

---

## 📋 What the Script Does

### Step 1: Prerequisites Check
- Verifies Ollama is installed and running
- Checks Python 3 is available
- Auto-starts Ollama if needed (macOS/Linux)

### Step 2: Backup
- Creates timestamped backup of your config
- Saves to `backup_YYYYMMDD_HHMMSS/`
- Safe to rollback if needed

### Step 3: Model Download
- Downloads `qwen2.5-coder:7b` (~4.7GB)
- Skips if already downloaded
- Shows progress and estimated time

### Step 4: Configuration Update
- Updates `config.yaml`:
  - Model: `gemma:2b` → `qwen2.5-coder:7b`
  - Max tokens: `3000` → `4000`
  - Timeout: `60` → `120`
- Updates prompts with security checklist
- Preserves your custom settings

### Step 5: Verification
- Confirms model is available
- Checks all required files exist
- Validates configuration

---

## 🎯 Manual Upgrade (If Needed)

If you prefer to upgrade manually:

### 1. Download Model
```bash
ollama pull qwen2.5-coder:7b
```

### 2. Update `config.yaml`
```yaml
localai:
  model: "qwen2.5-coder:7b"  # Change this line
  max_tokens: 4000            # Change from 3000
  timeout: 120                # Change from 60
```

### 3. Update Prompts
```bash
# If you cloned from git
git pull origin main

# Or download directly
curl -sSL https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main/prompts/system_prompt.txt -o prompts/system_prompt.txt
```

### 4. Test
```bash
python3 review_local.py --commit-range HEAD~1..HEAD
```

---

## 📊 Before vs After

### Detection Rate
| Metric | Before (gemma:2b) | After (qwen2.5-coder:7b) |
|--------|-------------------|--------------------------|
| **Issues Detected** | 1/4 (25%) ❌ | 4/4 (100%) ✅ |
| **Analysis Time** | ~9 seconds | ~60 seconds |
| **Model Size** | 1.5GB | 4.7GB |

### Issues Now Detected
- ✅ **Mass Assignment**: `$model->update($request->all())`
- ✅ **SQL Injection**: `DB::select("WHERE x = $var")`
- ✅ **Missing Null Checks**: `User::find($id)->delete()`
- ✅ **N+1 Queries**: Relationship access in loops

---

## 🧪 Testing Your Upgrade

After upgrading, verify it works:

```bash
# Test on last commit
python3 review_local.py --commit-range HEAD~1..HEAD

# Expected output:
# - Model: qwen2.5-coder:7b
# - Better issue detection
# - ~60 second analysis time
```

Or just push code:
```bash
git push
# Will automatically review and block if critical issues found
```

---

## 🔄 Rollback (If Needed)

If you need to rollback:

```bash
# 1. Find your backup
ls -la backup_*/

# 2. Restore config
cp backup_YYYYMMDD_HHMMSS/config.yaml config.yaml

# 3. Switch back to old model
ollama pull gemma:2b
```

---

## 💾 Disk Space

### Before Upgrade
- `gemma:2b`: ~1.5GB

### After Upgrade
- `gemma:2b`: ~1.5GB (can be removed)
- `qwen2.5-coder:7b`: ~4.7GB
- **Total**: ~6.2GB (or ~4.7GB if you remove gemma:2b)

### Clean Up Old Model
```bash
# Remove old model to save space
ollama rm gemma:2b
```

---

## ⚡ Performance Considerations

### Analysis Time
- **Before**: ~9 seconds per review
- **After**: ~60 seconds per review

### Is it worth it?
**YES!** The extra 50 seconds catches:
- 4x more issues
- Critical security vulnerabilities
- Potential crashes
- Performance problems

### For Faster Reviews
If 60 seconds is too slow, try:

```bash
# Use smaller model (faster, still better than gemma:2b)
ollama pull qwen2.5-coder:3b

# Update config.yaml
model: "qwen2.5-coder:3b"
```

---

## 🏢 Team Upgrade

### For Team Leads

Share the upgrade script with your team:

```bash
# 1. Commit the upgrade script to your repo
git add upgrade.sh UPGRADE.md
git commit -m "Add upgrade script for qwen2.5-coder:7b"
git push

# 2. Notify team members
# Send them this command:
cd /path/to/project
git pull
./upgrade.sh
```

### Slack/Teams Message Template

```
🔄 Code Review Agent Upgrade Available!

We're upgrading to a better AI model that catches 4x more issues:
✅ Mass assignment vulnerabilities
✅ SQL injection attacks
✅ Missing null checks
✅ N+1 query problems

To upgrade (takes 5-15 minutes):
1. cd /path/to/project
2. git pull
3. ./upgrade.sh

The script handles everything automatically!

Questions? Check UPGRADE.md or ask in #dev-tools
```

---

## ❓ FAQ

### Q: Do I need to reinstall the Git hook?
**A:** No, the hook automatically uses the model from `config.yaml`.

### Q: Will this work on my CPU?
**A:** Yes! It runs on CPU, just takes ~60 seconds instead of ~9 seconds.

### Q: Can I keep both models?
**A:** Yes, Ollama allows multiple models. Use `ollama list` to see all.

### Q: What if the download fails?
**A:** The script will show an error. Check your internet and try again:
```bash
ollama pull qwen2.5-coder:7b
```

### Q: Can I customize the upgrade?
**A:** Yes, edit `upgrade.sh` or do a manual upgrade (see above).

### Q: Does this affect my existing code?
**A:** No, it only changes the AI model used for reviews.

---

## 🆘 Troubleshooting

The upgrade script includes automatic fixes for most common issues.

### Quick Fixes

**"Ollama is not running"**
```bash
# macOS
open -a Ollama

# Linux
ollama serve &
```

**"Failed to download model"**
```bash
# Try manual download
ollama pull qwen2.5-coder:7b
```

**"Config not updated correctly"**
The script now automatically fixes this! If it still fails:
```bash
# Edit manually
nano config.yaml
# Change: model: "qwen2.5-coder:7b"
#         max_tokens: 4000
#         timeout: 120
```

### Complete Troubleshooting Guide

For detailed solutions to all issues, see:

📖 **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)**

Includes:
- Automatic fix procedures
- Manual fix instructions
- Debug mode
- Rollback procedures
- Verification checklist

---

## 📞 Support

If you encounter issues:

1. Check the backup: `ls -la backup_*/`
2. Review logs: `cat .local_review.log`
3. Test manually: `python3 review_local.py --commit-range HEAD~1..HEAD`
4. Open an issue: [GitHub Issues](https://github.com/muhmmedAbdelkhalik/agentic_code_review/issues)

---

## ✅ Verification Checklist

After upgrading, verify:

- [ ] Model downloaded: `ollama list | grep qwen2.5-coder:7b`
- [ ] Config updated: `grep "qwen2.5-coder:7b" config.yaml`
- [ ] Backup created: `ls -la backup_*/`
- [ ] Test passes: `python3 review_local.py --commit-range HEAD~1..HEAD`
- [ ] Git hook works: `git push` (on test branch)

---

**Ready to upgrade? Run `./upgrade.sh` now!** 🚀


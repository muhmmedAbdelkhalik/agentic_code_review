# 🚀 Quick Upgrade - One Command

Upgrade from `gemma:2b` to `qwen2.5-coder:7b` in one command:

```bash
./upgrade.sh
```

**That's it!** ✨

---

## What Happens?

1. ✅ Checks prerequisites (5 seconds)
2. ✅ Backs up your config (1 second)
3. ✅ Downloads new model (5-15 minutes)
4. ✅ Updates configuration (5 seconds)
5. ✅ Verifies everything (5 seconds)

**Total time**: 5-15 minutes (mostly downloading)

---

## What You Get

### Before (gemma:2b)
- Detects: 1/4 issues (25%) ❌
- Speed: ~9 seconds

### After (qwen2.5-coder:7b)
- Detects: 4/4 issues (100%) ✅
- Speed: ~60 seconds

**Worth the extra time!**

---

## Issues Now Detected

- ✅ Mass assignment: `$model->update($request->all())`
- ✅ SQL injection: `DB::select("WHERE x = $var")`
- ✅ Missing null checks: `User::find($id)->delete()`
- ✅ N+1 queries: Relationship access in loops

---

## Test After Upgrade

```bash
# Test on last commit
python3 review_local.py --commit-range HEAD~1..HEAD

# Or just push
git push
```

---

## Rollback (If Needed)

```bash
# Find backup
ls -la backup_*/

# Restore
cp backup_YYYYMMDD_HHMMSS/config.yaml config.yaml
```

---

## Need Help?

- 📖 Full guide: [UPGRADE.md](UPGRADE.md)
- 🔧 Troubleshooting: [UPGRADE_TROUBLESHOOTING.md](UPGRADE_TROUBLESHOOTING.md)
- 📊 Test results: [TEST_RESULTS.md](TEST_RESULTS.md)
- 💬 Questions? Open an issue

---

**Ready? Run `./upgrade.sh` now!** 🎯


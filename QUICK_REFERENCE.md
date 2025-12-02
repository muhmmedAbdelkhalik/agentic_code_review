# 🚀 Quick Reference Card

## Essential Commands

### Run Code Review
```bash
# Basic review
python3 review_local.py

# Review specific commits
python3 review_local.py --commit-range HEAD~1..HEAD

# Verbose mode
python3 review_local.py --verbose
```

### View Results
```bash
# Pretty print JSON
cat .local_review.json | python3 -m json.tool

# View logs
tail -f .local_review.log
```

### Git Hook Usage
```bash
# Normal push (review runs automatically)
git push

# Skip review
SKIP_REVIEW=1 git push

# Block on critical issues
# Set in .env: BLOCK_ON_CRITICAL=true
```

### Ollama Management
```bash
# Check status
ollama list
ollama ps

# Pull new models
ollama pull gemma:2b
ollama pull codellama

# Start/stop
ollama serve &
pkill ollama
```

## File Locations

| File | Purpose |
|------|---------|
| `review_local.py` | Main agent script |
| `config.yaml` | Configuration |
| `.local_review.json` | Review output |
| `.local_review.log` | Agent logs |
| `.git/hooks/pre-push` | Git hook |

## Configuration Quick Edit

```yaml
# config.yaml

# Change model
localai_model: "gemma:2b"  # or codellama, llama3.2, etc.

# Adjust timeout
localai_timeout_seconds: 300

# Confidence threshold
min_confidence: 0.7

# Block on critical
block_on_critical: true
```

## Common Models

| Model | Size | Speed | Quality |
|-------|------|-------|---------|
| gemma:2b | 1.7GB | ⚡⚡⚡ | Good |
| gemma3:1b | 815MB | ⚡⚡⚡⚡ | Good |
| codellama | 3.8GB | ⚡⚡ | Excellent |
| qwen2.5-coder | 4.7GB | ⚡⚡ | Excellent |

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Ollama not responding | `ollama serve &` |
| Model not found | `ollama pull gemma:2b` |
| Timeout | Increase timeout in config.yaml |
| No changes detected | Stage changes: `git add .` |
| Import errors | Activate venv: `source venv/bin/activate` |

## Documentation Index

1. **[SUCCESS.md](SUCCESS.md)** - Success guide & next steps
2. **[USAGE.md](USAGE.md)** - Complete usage guide
3. **[README.md](README.md)** - Project overview
4. **[QUICKSTART.md](QUICKSTART.md)** - 5-minute setup
5. **[TROUBLESHOOTING_LOCALAI.md](TROUBLESHOOTING_LOCALAI.md)** - Troubleshooting

## Quick Test

```bash
# 1. Make a test change
echo "// test" >> test_code.php

# 2. Stage it
git add test_code.php

# 3. Run review
python3 review_local.py

# 4. Check output
cat .local_review.json
```

## Performance Tips

1. **Use smaller models** for faster reviews (gemma:2b, gemma3:1b)
2. **Increase timeout** if using larger models
3. **Stage only changed files** to reduce analysis time
4. **Use virtual environment** to avoid dependency conflicts
5. **Keep Ollama updated**: `brew upgrade ollama`

## Privacy Checklist

✅ All processing is local  
✅ Ollama runs on your machine  
✅ No external API calls  
✅ Source code never transmitted  
✅ No telemetry or tracking  

---

**Need more help?** Check [SUCCESS.md](SUCCESS.md) or [USAGE.md](USAGE.md)


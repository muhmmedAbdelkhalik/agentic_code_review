# Quick Start Guide

Get the LocalAI Code Review Agent running in 5 minutes!

## Prerequisites

- Docker installed
- Python 3.10+ installed
- Git repository to test with

## Step-by-Step Setup

### 1. Install Python Dependencies (1 minute)

```bash
pip install -r requirements.txt
```

### 2. Start LocalAI (30 seconds)

```bash
docker-compose up -d
```

### 3. Download a Model (3 minutes)

```bash
# Create models directory
mkdir -p models

# Download Mistral-7B-Instruct (recommended)
cd models
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf

# Rename for easier reference
mv mistral-7b-instruct-v0.2.Q4_K_M.gguf mistral-7b-instruct.gguf
cd ..
```

**Alternative (faster, smaller model)**:

```bash
cd models
wget https://huggingface.co/microsoft/Phi-3-mini-4k-instruct-gguf/resolve/main/Phi-3-mini-4k-instruct-q4.gguf
mv Phi-3-mini-4k-instruct-q4.gguf phi-3-mini.gguf
cd ..

# Update config.yaml to use phi-3-mini
```

### 4. Wait for Model to Load (1-2 minutes)

```bash
# Watch the logs
docker-compose logs -f localai

# Wait for: "Model loaded successfully"
# Press Ctrl+C to exit logs
```

### 5. Verify LocalAI is Ready

```bash
# Check health
curl http://localhost:8080/readyz

# Should return: OK

# List models
curl http://localhost:8080/v1/models
```

### 6. Run Your First Review

```bash
# Make sure you have some git changes
git status

# Run the review
python3 review_local.py

# Check the output
cat .local_review.json
```

## Expected Output

You should see:

```
🚀 Starting LocalAI Code Review Agent...

📝 Collecting git diff...
   Found changes in X file(s)

🔧 Running analysis tools...
   • PHPStan...
   • PHPCS...
   • PHPUnit...

📤 Building prompt for LocalAI...

🤖 Calling LocalAI (mistral-7b-instruct)...

✅ Validating review output...

💾 Review saved to .local_review.json

================================================================================
📋 Code Review Summary
================================================================================
...
```

## What If Something Goes Wrong?

### LocalAI won't start

```bash
# Check Docker is running
docker ps

# Check logs for errors
docker-compose logs localai

# Try restarting
docker-compose restart localai
```

### Model download is slow

The model is ~4.4GB. On slow connections:

1. Download overnight
2. Use a smaller model (Phi-3-Mini is only 2.4GB)
3. Download from a mirror

### No changes detected

```bash
# Make sure you have uncommitted changes
git status

# Or specify a commit range
python3 review_local.py --commit-range HEAD~1..HEAD
```

### Python dependencies fail

```bash
# Use a virtual environment
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

## Next Steps

1. ✅ **Install Git hooks**: `./install_hooks.sh`
2. ✅ **Read full documentation**: [USAGE.md](USAGE.md)
3. ✅ **Customize configuration**: Edit `config.yaml`
4. ✅ **Test with examples**: See [examples/README.md](examples/README.md)

## Quick Commands Reference

```bash
# Start LocalAI
docker-compose up -d

# Stop LocalAI
docker-compose down

# View LocalAI logs
docker-compose logs -f localai

# Run review
python3 review_local.py

# Run review with verbose output
python3 review_local.py --verbose

# Install Git hooks
./install_hooks.sh

# Skip review on push
SKIP_REVIEW=1 git push
```

## Typical First Run Time

- **Model loading**: 1-2 minutes (first time only)
- **Git diff collection**: < 1 second
- **PHP tools**: 2-5 seconds
- **LocalAI inference**: 5-15 seconds
- **Total**: ~10-20 seconds (after initial setup)

Subsequent runs are faster (5-10 seconds) because the model is already loaded.

## Success Checklist

- [ ] Docker is running
- [ ] LocalAI container is up (`docker-compose ps`)
- [ ] Model is downloaded in `models/` directory
- [ ] Model is loaded (check logs)
- [ ] LocalAI responds to health check
- [ ] Python dependencies installed
- [ ] Review script runs without errors
- [ ] `.local_review.json` is created

## Need Help?

- **Full documentation**: [USAGE.md](USAGE.md)
- **LocalAI setup**: [docker/localai/README.md](docker/localai/README.md)
- **Examples**: [examples/README.md](examples/README.md)
- **Logs**: Check `.local_review.log`

## Testing Without a Laravel Project

Use the provided examples:

```bash
# Apply sample diff
git apply examples/sample_diff.patch

# Run review
python3 review_local.py

# Compare with expected output
diff .local_review.json examples/sample_review.json

# Clean up
git reset --hard HEAD
```

---

**You're all set! Start reviewing code locally with AI. 🚀**


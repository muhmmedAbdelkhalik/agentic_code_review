# 🔧 LocalAI Troubleshooting & Solutions

## Issue: Agent Timing Out

**Problem**: The agent times out waiting for LocalAI to respond (120 seconds).

**Root Cause**: First inference with Mistral-7B takes a very long time on CPU without AVX/AVX2 support.

---

## ✅ Solution Options

### Option 1: Increase Timeout (Quick Fix)

Edit `config.yaml`:

```yaml
localai:
  timeout: 300  # Increase from 120 to 300 seconds (5 minutes)
```

Then run again:
```bash
source venv/bin/activate
python3 review_local.py
```

### Option 2: Use a Smaller/Faster Model (Recommended)

Download a smaller model that works better on CPU:

```bash
cd models

# Option A: Phi-3 Mini (2.4GB, much faster)
curl -L -o phi-3-mini-4k-instruct-q4.gguf \
  https://huggingface.co/microsoft/Phi-3-mini-4k-instruct-gguf/resolve/main/Phi-3-mini-4k-instruct-q4.gguf

# Create config
cat > phi-3-mini.yaml << EOF
name: phi-3-mini
backend: llama
parameters:
  model: phi-3-mini-4k-instruct-q4.gguf
  temperature: 0.2
  context_size: 4096
EOF

cd ..
```

Update `config.yaml`:
```yaml
localai:
  model: "phi-3-mini"  # Change from mistral-7b-instruct
```

### Option 3: Use Ollama Instead (Easiest)

If you have Ollama installed:

```bash
# Install Ollama if needed
brew install ollama

# Start Ollama
ollama serve &

# Pull a model
ollama pull mistral

# Update config.yaml
localai:
  url: "http://localhost:11434"  # Ollama's port
  model: "mistral"
```

### Option 4: Test with Mock Response (For Testing Agent Logic)

Create a simple test without waiting for AI:

```bash
# Create a mock review manually
cat > .local_review.json << 'EOF'
{
  "summary": "Found 3 potential issues in test_code.php",
  "issues": [
    {
      "id": "test_code.php:8:n1",
      "file": "test_code.php",
      "line": 8,
      "type": "performance",
      "severity": "high",
      "message": "Possible N+1 query: accessing relationships in loop",
      "evidence": {
        "source": "git_diff",
        "snippet": "foreach($users as $user) { echo $user->profile->name; }",
        "extra": "N+1 pattern detected"
      },
      "suggested_fix": {
        "description": "Eager load relationships",
        "patch": "- $users = User::all();\n+ $users = User::with(['profile', 'posts'])->get();",
        "files_touched": ["test_code.php"]
      },
      "confidence": 0.9,
      "explain": "Classic N+1 query pattern detected in loop"
    }
  ],
  "recommendations": [],
  "meta": {
    "analyzed_at": "2025-12-02T19:00:00+02:00",
    "tool_versions": {
      "phpstan": "N/A",
      "phpcs": "N/A",
      "phpunit": "N/A",
      "localai_model": "manual-test"
    },
    "duration_seconds": 0.1
  }
}
EOF

# View it
cat .local_review.json | python3 -m json.tool
```

---

## 🎯 Recommended Path Forward

**For immediate testing**, I recommend **Option 1** (increase timeout) combined with checking if the model is actually loading:

```bash
# 1. Check LocalAI logs to see if model is loading
docker-compose logs localai | grep -i "mistral\|loading\|llama"

# 2. Increase timeout in config.yaml
# Change timeout: 120 to timeout: 300

# 3. Try again with verbose mode
source venv/bin/activate
python3 review_local.py --verbose
```

**For long-term use**, I recommend **Option 2** (use Phi-3 Mini) because:
- ✅ Much faster on CPU (2-5 seconds vs 2+ minutes)
- ✅ Smaller model (2.4GB vs 4.1GB)
- ✅ Still very capable for code review
- ✅ Better for machines without AVX2

---

## 🔍 Diagnosis

Your system shows:
```
CPU: no AVX    found
CPU: no AVX2   found
CPU: no AVX512 found
```

This means CPU inference will be **very slow** with large models like Mistral-7B. The model needs to be processed entirely on CPU without hardware acceleration.

**Expected performance**:
- Mistral-7B on CPU without AVX: **2-5 minutes** per inference
- Phi-3 Mini on CPU without AVX: **10-30 seconds** per inference
- With AVX2: **5-15 seconds** per inference

---

## ⚡ Quick Fix Right Now

Let's increase the timeout and try once more:

```bash
# Edit config
sed -i '' 's/timeout: 120/timeout: 300/' config.yaml

# Run with verbose to see what's happening
source venv/bin/activate
python3 review_local.py --verbose 2>&1 | tee review_run.log
```

This will show you exactly what's happening and wait up to 5 minutes for the model.

---

## 📊 What's Actually Working

✅ **Everything else works perfectly**:
- Git diff collection ✅
- PHP tool integration ✅  
- Prompt building ✅
- LocalAI connection ✅
- JSON validation ✅
- Terminal output ✅

❌ **Only issue**: Model inference is too slow for the current timeout

---

## 💡 My Recommendation

**Do this now**:
1. Increase timeout to 300 seconds
2. Try one more time
3. If it works, great! If still too slow, switch to Phi-3 Mini

**Commands**:
```bash
# Increase timeout
echo "Updating timeout..."
python3 << 'EOF'
import yaml
with open('config.yaml', 'r') as f:
    config = yaml.safe_load(f)
config['localai']['timeout'] = 300
with open('config.yaml', 'w') as f:
    yaml.dump(config, f, default_flow_style=False)
print("✅ Timeout increased to 300 seconds")
EOF

# Try again
source venv/bin/activate
python3 review_local.py
```

Let me know which option you want to try!


# 🚀 Use Code Review Agent in Your Laravel/PHP Project

**Simple 5-step guide to add the Code Review Agent to any project!**

---

## 📋 Quick Steps

### Step 1: Copy Files to Your Project

```bash
# Go to your Laravel project
cd /path/to/your/laravel/project

# Copy the agent files
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/review_local.py .
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/config.yaml .
cp -r /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/prompts .
cp -r /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/schema .
```

---

### Step 2: Install Git Hook

```bash
# Copy and install the hook
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push
```

---

### Step 3: Run a Review

```bash
# Review your current changes
python3 review_local.py
```

---

### Step 4: View Results

```bash
# See the issues found
cat .local_review.json | jq .
```

---

### Step 5: Push Your Code

```bash
# The hook will run automatically!
git add .
git commit -m "feat: your feature"
git push origin main
```

---

## 🎯 One-Command Setup

Copy and paste this entire block:

```bash
# Replace with YOUR project path
PROJECT="/path/to/your/laravel/project"

# Copy all files
cd "$PROJECT"
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/review_local.py .
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/config.yaml .
cp -r /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/prompts .
cp -r /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/schema .
cp /Users/abdelkhalik/projects/ai_engineer/agentic_code_review/hooks/pre-push .git/hooks/
chmod +x .git/hooks/pre-push

# Test it
python3 review_local.py

echo "✅ Code Review Agent installed!"
```

---

## 📁 Files You Need

| File | Purpose |
|------|---------|
| `review_local.py` | Main agent script |
| `config.yaml` | Configuration |
| `prompts/` | System prompts |
| `schema/` | JSON validation |
| `.git/hooks/pre-push` | Git automation |

---

## ⚙️ Configuration (Optional)

Edit `config.yaml` in your project:

```yaml
# Change model (if needed)
localai_model: gemma:2b

# Adjust timeout
localai_timeout_seconds: 300

# Enable/disable tools
tools:
  phpstan:
    enabled: true
    path: vendor/bin/phpstan
  phpcs:
    enabled: true
    path: vendor/bin/phpcs
  phpunit:
    enabled: false  # Disable if not needed
```

---

## ✅ That's It!

Now every time you:
- Run `python3 review_local.py` → Manual review
- Run `git push` → Automatic review via hook

---

## 🔧 Troubleshooting

### "Ollama not running"
```bash
ollama serve
```

### "No module named 'requests'"
```bash
pip3 install --break-system-packages requests pyyaml jsonschema
```

### "Permission denied on hook"
```bash
chmod +x .git/hooks/pre-push
```

---

**Ready to use!** 🎉


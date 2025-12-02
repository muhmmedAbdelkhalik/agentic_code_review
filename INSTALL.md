# 🚀 Easy Installation

Install the Code Review Agent in your Laravel/PHP project with one command!

---

## ⚡ Quick Install

### Method 1: From GitHub (Recommended)

```bash
curl -sSL https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main/install.sh | bash -s /path/to/your/project
```

---

### Method 2: Local Installation

```bash
# Clone or download this repository first
cd /path/to/agentic_code_review

# Run the installer
./install.sh /path/to/your/laravel/project
```

---

### Method 3: Interactive Installation

```bash
# The script will ask for your project path
./install.sh
```

---

## 📋 What the Script Does

The installation script automatically:

1. ✅ **Checks prerequisites** (Python 3, Ollama)
2. ✅ **Installs Python dependencies** (requests, pyyaml, etc.)
3. ✅ **Copies core files** (review_local.py, config.yaml, prompts, schema)
4. ✅ **Installs Git hook** (pre-push)
5. ✅ **Updates .gitignore** (excludes review files)
6. ✅ **Tests installation** (verifies everything works)

---

## 🎯 After Installation

### Run your first review:
```bash
cd /path/to/your/project
python3 review_local.py
```

### View results:
```bash
cat .local_review.json | jq .
```

### Push code (hook runs automatically):
```bash
git add .
git commit -m "feat: your feature"
git push origin main
```

---

## ⚙️ Configuration

Edit `config.yaml` in your project:

```yaml
# Change model
localai_model: gemma:2b  # or phi3, llama3.2:1b

# Adjust timeout
localai_timeout_seconds: 300

# Enable/disable tools
tools:
  phpstan:
    enabled: true
  phpcs:
    enabled: true
  phpunit:
    enabled: false
```

---

## 🔧 Troubleshooting

### Ollama not running?
```bash
ollama serve
```

### Dependencies missing?
```bash
pip3 install --break-system-packages -r requirements.txt
```

### Skip review on push?
```bash
SKIP_REVIEW=1 git push origin main
```

---

## 📦 Files Installed

```
your-project/
├── review_local.py          # Main agent script
├── config.yaml              # Configuration
├── prompts/                 # AI prompts
│   └── system_prompt.txt
├── schema/                  # JSON validation
│   └── review_schema.json
├── .git/hooks/
│   └── pre-push            # Git automation
└── .gitignore              # Updated with review files
```

---

## 🎉 That's It!

The Code Review Agent is now installed and ready to use!

**Next steps:**
- Make some code changes
- Run `python3 review_local.py`
- See the AI find issues!

---

## 📚 Documentation

- **Full Guide**: [README.md](README.md)
- **Usage Guide**: [USAGE.md](USAGE.md)
- **Testing**: [TESTING_WORKFLOW.md](TESTING_WORKFLOW.md)
- **GitHub**: https://github.com/muhmmedAbdelkhalik/agentic_code_review

---

**Questions?** Open an issue on GitHub!


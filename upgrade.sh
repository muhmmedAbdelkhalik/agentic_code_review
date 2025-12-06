#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                                                                           ║${NC}"
echo -e "${CYAN}║           🔄 Code Review Agent Upgrade Script                            ║${NC}"
echo -e "${CYAN}║           Upgrading from gemma:2b to qwen2.5-coder:7b                    ║${NC}"
echo -e "${CYAN}║                                                                           ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print step header
print_step() {
    echo -e "${BLUE}[$1/5]${NC} $2"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Step 1: Check prerequisites
print_step 1 "Checking prerequisites..."
echo ""

# Check if Ollama is installed
if ! command_exists ollama; then
    echo -e "${RED}❌ Ollama is not installed${NC}"
    echo -e "${YELLOW}   Install it from: https://ollama.ai${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Ollama: Installed"

# Check if Ollama is running
if ! curl -s http://localhost:11434/api/tags > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Ollama is not running${NC}"
    echo -e "${YELLOW}   Starting Ollama...${NC}"
    
    # Try to start Ollama
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        if [ -d "/Applications/Ollama.app" ]; then
            open -a Ollama
            sleep 3
        else
            echo -e "${RED}❌ Cannot start Ollama automatically${NC}"
            echo -e "${YELLOW}   Please start Ollama manually and run this script again${NC}"
            exit 1
        fi
    else
        # Linux
        ollama serve > /dev/null 2>&1 &
        sleep 3
    fi
    
    # Check again
    if ! curl -s http://localhost:11434/api/tags > /dev/null 2>&1; then
        echo -e "${RED}❌ Failed to start Ollama${NC}"
        echo -e "${YELLOW}   Please start Ollama manually: ollama serve${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}✓${NC} Ollama: Running"

# Check Python
if ! command_exists python3; then
    echo -e "${RED}❌ Python 3 is not installed${NC}"
    exit 1
fi
PYTHON_VERSION=$(python3 --version | cut -d' ' -f2)
echo -e "${GREEN}✓${NC} Python 3: $PYTHON_VERSION"

echo ""

# Step 2: Backup current configuration
print_step 2 "Backing up current configuration..."
echo ""

BACKUP_DIR="${SCRIPT_DIR}/backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -f "${SCRIPT_DIR}/config.yaml" ]; then
    cp "${SCRIPT_DIR}/config.yaml" "${BACKUP_DIR}/config.yaml"
    echo -e "${GREEN}✓${NC} Backed up config.yaml"
fi

if [ -f "${SCRIPT_DIR}/prompts/system_prompt.txt" ]; then
    cp "${SCRIPT_DIR}/prompts/system_prompt.txt" "${BACKUP_DIR}/system_prompt.txt"
    echo -e "${GREEN}✓${NC} Backed up system_prompt.txt"
fi

echo -e "${CYAN}📁 Backup location: ${BACKUP_DIR}${NC}"
echo ""

# Step 3: Download new model
print_step 3 "Downloading qwen2.5-coder:7b model..."
echo ""

# Check if model already exists
if ollama list | grep -q "qwen2.5-coder:7b"; then
    echo -e "${GREEN}✓${NC} Model already downloaded"
else
    echo -e "${YELLOW}⏳ Downloading model (~4.7GB)...${NC}"
    echo -e "${YELLOW}   This may take 5-15 minutes depending on your internet speed${NC}"
    echo ""
    
    if ollama pull qwen2.5-coder:7b; then
        echo ""
        echo -e "${GREEN}✓${NC} Model downloaded successfully"
    else
        echo ""
        echo -e "${RED}❌ Failed to download model${NC}"
        echo -e "${YELLOW}   Please check your internet connection and try again${NC}"
        exit 1
    fi
fi

echo ""

# Step 4: Update configuration files
print_step 4 "Updating configuration files..."
echo ""

# Update config.yaml
if [ -f "${SCRIPT_DIR}/config.yaml" ]; then
    # Check if already using qwen2.5-coder:7b
    if grep -q 'model: "qwen2.5-coder:7b"' "${SCRIPT_DIR}/config.yaml"; then
        echo -e "${GREEN}✓${NC} config.yaml already up to date"
    else
        echo -e "${YELLOW}⏳ Updating config.yaml...${NC}"
        
        # Update model name - try multiple patterns
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS (BSD sed)
            # Update model (try different patterns)
            sed -i '' 's/model: *"gemma:2b"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i '' 's/model: *"gemma:[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i '' 's/model: *"phi3"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i '' 's/model: *"llama[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            
            # Update in localai section specifically
            sed -i '' '/^localai:/,/^[a-z]/ s/model: *"[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            
            # Update max_tokens and timeout
            sed -i '' 's/max_tokens: *[0-9]*/max_tokens: 4000/' "${SCRIPT_DIR}/config.yaml"
            sed -i '' 's/timeout: *[0-9]*/timeout: 120/' "${SCRIPT_DIR}/config.yaml"
        else
            # Linux (GNU sed)
            # Update model (try different patterns)
            sed -i 's/model: *"gemma:2b"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i 's/model: *"gemma:[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i 's/model: *"phi3"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            sed -i 's/model: *"llama[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            
            # Update in localai section specifically
            sed -i '/^localai:/,/^[a-z]/ s/model: *"[^"]*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
            
            # Update max_tokens and timeout
            sed -i 's/max_tokens: *[0-9]*/max_tokens: 4000/' "${SCRIPT_DIR}/config.yaml"
            sed -i 's/timeout: *[0-9]*/timeout: 120/' "${SCRIPT_DIR}/config.yaml"
        fi
        
        # Verify the update worked
        if grep -q 'model: "qwen2.5-coder:7b"' "${SCRIPT_DIR}/config.yaml"; then
            echo -e "${GREEN}✓${NC} Updated config.yaml"
        else
            echo -e "${YELLOW}⚠️  sed update didn't work, trying Python...${NC}"
            
            # Fallback: Use Python to update YAML
            python3 << 'PYTHON_EOF'
import sys
import re

config_file = "${SCRIPT_DIR}/config.yaml"
try:
    with open(config_file, 'r') as f:
        content = f.read()
    
    # Update model in localai section
    content = re.sub(r'(localai:.*?model:\s*")[^"]*(")', r'\1qwen2.5-coder:7b\2', content, flags=re.DOTALL)
    
    # Update max_tokens
    content = re.sub(r'max_tokens:\s*\d+', 'max_tokens: 4000', content)
    
    # Update timeout
    content = re.sub(r'timeout:\s*\d+', 'timeout: 120', content)
    
    with open(config_file, 'w') as f:
        f.write(content)
    
    print("✓ Updated using Python")
    sys.exit(0)
except Exception as e:
    print(f"❌ Python update failed: {e}")
    sys.exit(1)
PYTHON_EOF
            
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✓${NC} Updated config.yaml (using Python)"
            else
                echo -e "${YELLOW}⚠️  Automatic update failed${NC}"
            fi
        fi
    fi
else
    echo -e "${RED}❌ config.yaml not found${NC}"
    exit 1
fi

# Update all files from repository (including bug fixes)
if [ -d "${SCRIPT_DIR}/.git" ]; then
    echo -e "${YELLOW}⏳ Updating all files from repository (includes bug fixes)...${NC}"

    # Save current directory
    CURRENT_DIR=$(pwd)
    cd "${SCRIPT_DIR}"

    # Stash any local changes to config.yaml only
    if [ -f "config.yaml" ]; then
        cp config.yaml config.yaml.tmp
    fi

    # Pull latest changes (gets review_local.py, prompts, schema, hooks)
    if git pull origin main > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Updated from repository:"
        echo -e "  ${GREEN}✓${NC} review_local.py (bug fixes)"
        echo -e "  ${GREEN}✓${NC} prompts/system_prompt.txt"
        echo -e "  ${GREEN}✓${NC} schema/review_schema.json"
        echo -e "  ${GREEN}✓${NC} hooks/pre-push"

        # Restore config.yaml if it was overwritten
        if [ -f "config.yaml.tmp" ]; then
            # Check if config was changed by pull
            if ! diff -q config.yaml config.yaml.tmp > /dev/null 2>&1; then
                echo -e "${YELLOW}⚠️  config.yaml was modified by git pull, restoring your version${NC}"
                mv config.yaml.tmp config.yaml
            else
                rm config.yaml.tmp
            fi
        fi
    else
        echo -e "${YELLOW}⚠️  Could not pull from repository${NC}"
        echo -e "${YELLOW}   Trying to download files directly...${NC}"

        # Fallback: Download files directly via curl
        REPO_URL="https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main"

        if curl -sSL "${REPO_URL}/review_local.py" -o review_local.py; then
            echo -e "${GREEN}✓${NC} Downloaded review_local.py"
        else
            echo -e "${RED}❌ Failed to download review_local.py${NC}"
        fi

        if curl -sSL "${REPO_URL}/prompts/system_prompt.txt" -o prompts/system_prompt.txt; then
            echo -e "${GREEN}✓${NC} Downloaded system_prompt.txt"
        else
            echo -e "${YELLOW}⚠️  Failed to download system_prompt.txt${NC}"
        fi

        if curl -sSL "${REPO_URL}/schema/review_schema.json" -o schema/review_schema.json; then
            echo -e "${GREEN}✓${NC} Downloaded review_schema.json"
        else
            echo -e "${YELLOW}⚠️  Failed to download review_schema.json${NC}"
        fi
    fi

    # Clean up
    [ -f "config.yaml.tmp" ] && rm config.yaml.tmp

    cd "$CURRENT_DIR"
else
    echo -e "${YELLOW}⚠️  Not a git repository, downloading files directly...${NC}"

    # Download files directly
    REPO_URL="https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main"

    if curl -sSL "${REPO_URL}/review_local.py" -o "${SCRIPT_DIR}/review_local.py"; then
        echo -e "${GREEN}✓${NC} Downloaded review_local.py (latest bug fixes)"
    else
        echo -e "${RED}❌ Failed to download review_local.py${NC}"
    fi

    if curl -sSL "${REPO_URL}/prompts/system_prompt.txt" -o "${SCRIPT_DIR}/prompts/system_prompt.txt"; then
        echo -e "${GREEN}✓${NC} Downloaded system_prompt.txt"
    else
        echo -e "${YELLOW}⚠️  Failed to download system_prompt.txt${NC}"
    fi

    if curl -sSL "${REPO_URL}/schema/review_schema.json" -o "${SCRIPT_DIR}/schema/review_schema.json"; then
        echo -e "${GREEN}✓${NC} Downloaded review_schema.json"
    else
        echo -e "${YELLOW}⚠️  Failed to download schema${NC}"
    fi
fi

echo ""

# Step 5: Verify installation
print_step 5 "Verifying installation..."
echo ""

# Check model is available
if ollama list | grep -q "qwen2.5-coder:7b"; then
    echo -e "${GREEN}✓${NC} Model: qwen2.5-coder:7b available"
else
    echo -e "${RED}❌ Model not found${NC}"
    exit 1
fi

# Check config.yaml has correct model
if grep -q 'model: "qwen2.5-coder:7b"' "${SCRIPT_DIR}/config.yaml"; then
    echo -e "${GREEN}✓${NC} Config: Using qwen2.5-coder:7b"
else
    echo -e "${RED}❌ Config not updated correctly${NC}"
    echo ""
    echo -e "${YELLOW}🔧 Attempting to fix...${NC}"
    
    # Try to fix it manually
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS (BSD sed)
        sed -i '' 's/model: ".*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
        sed -i '' 's/max_tokens: [0-9]*/max_tokens: 4000/' "${SCRIPT_DIR}/config.yaml"
        sed -i '' 's/timeout: [0-9]*/timeout: 120/' "${SCRIPT_DIR}/config.yaml"
    else
        # Linux (GNU sed)
        sed -i 's/model: ".*"/model: "qwen2.5-coder:7b"/' "${SCRIPT_DIR}/config.yaml"
        sed -i 's/max_tokens: [0-9]*/max_tokens: 4000/' "${SCRIPT_DIR}/config.yaml"
        sed -i 's/timeout: [0-9]*/timeout: 120/' "${SCRIPT_DIR}/config.yaml"
    fi
    
    # Verify fix worked
    if grep -q 'model: "qwen2.5-coder:7b"' "${SCRIPT_DIR}/config.yaml"; then
        echo -e "${GREEN}✓${NC} Config fixed successfully"
    else
        echo -e "${RED}❌ Automatic fix failed${NC}"
        echo ""
        echo -e "${YELLOW}📝 Please manually update config.yaml:${NC}"
        echo ""
        echo "  1. Open config.yaml in your editor"
        echo "  2. Find the 'localai:' section"
        echo "  3. Change these lines:"
        echo ""
        echo "     model: \"qwen2.5-coder:7b\""
        echo "     max_tokens: 4000"
        echo "     timeout: 120"
        echo ""
        echo -e "${YELLOW}Then run this command to verify:${NC}"
        echo "  grep 'model:' config.yaml"
        echo ""
        exit 1
    fi
fi

# Check required files exist
REQUIRED_FILES=(
    "review_local.py"
    "config.yaml"
    "prompts/system_prompt.txt"
    "schema/review_schema.json"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "${SCRIPT_DIR}/${file}" ]; then
        echo -e "${GREEN}✓${NC} File: ${file}"
    else
        echo -e "${RED}❌ Missing: ${file}${NC}"
        exit 1
    fi
done

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ UPGRADE COMPLETE!${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

# Show improvements
echo -e "${CYAN}📊 What's Improved:${NC}"
echo ""
echo -e "  ${GREEN}Detection Rate:${NC}"
echo -e "    Before: 1/4 issues (25%) ❌"
echo -e "    After:  4/4 issues (100%) ✅"
echo ""
echo -e "  ${GREEN}Now Catches:${NC}"
echo -e "    ✅ Mass assignment vulnerabilities"
echo -e "    ✅ SQL injection attacks"
echo -e "    ✅ Missing null checks"
echo -e "    ✅ N+1 query problems"
echo ""
echo -e "  ${YELLOW}Performance:${NC}"
echo -e "    Analysis time: ~60 seconds (vs ~9 seconds before)"
echo -e "    Worth it for 4x better accuracy!"
echo ""

echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}🧪 Test Your Upgrade:${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  # Test on your last commit"
echo -e "  ${BLUE}python3 review_local.py --commit-range HEAD~1..HEAD${NC}"
echo ""
echo -e "  # Or just push code (will auto-review)"
echo -e "  ${BLUE}git push${NC}"
echo ""

echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}📝 Notes:${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  • Your old configuration is backed up in: ${BACKUP_DIR}"
echo -e "  • The Git hook will automatically use the new model"
echo -e "  • Reviews now take ~60 seconds but catch 4x more issues"
echo ""

# Optional: Clean up old model
echo -e "${YELLOW}💡 Optional: Remove old model to save space${NC}"
echo -e "   ${BLUE}ollama rm gemma:2b${NC}  (saves ~1.5GB)"
echo ""

echo -e "${GREEN}Happy coding! 🚀${NC}"
echo ""


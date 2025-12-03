#!/bin/bash
#
# Code Review Agent - Easy Installation Script
# 
# Usage:
#   curl -sSL https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main/install.sh | bash
#   OR
#   bash install.sh /path/to/your/laravel/project
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Temporary directory for downloaded files (will be set later)
TEMP_DIR=""
USE_TEMP=false

# Cleanup function
cleanup() {
    if [ "$USE_TEMP" = true ] && [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR" 2>/dev/null || true
    fi
}

# Register cleanup function to run on exit
trap cleanup EXIT

# Banner
echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║           🤖 Code Review Agent - Installation                    ║${NC}"
echo -e "${CYAN}║           Privacy-First AI Code Review for Laravel/PHP          ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Get target directory
if [ -z "$1" ]; then
    echo -e "${YELLOW}📁 Enter the path to your Laravel/PHP project:${NC}"
    read -p "   Path: " TARGET_DIR
else
    TARGET_DIR="$1"
fi

# Expand tilde to home directory
TARGET_DIR="${TARGET_DIR/#\~/$HOME}"

# Validate target directory
if [ ! -d "$TARGET_DIR" ]; then
    echo -e "${RED}❌ Directory not found: $TARGET_DIR${NC}"
    exit 1
fi

# Check if it's a git repository
if [ ! -d "$TARGET_DIR/.git" ]; then
    echo -e "${RED}❌ Not a git repository: $TARGET_DIR${NC}"
    echo -e "${YELLOW}   Initialize git first: git init${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Target directory: $TARGET_DIR${NC}"
echo ""

# GitHub repository base URL
GITHUB_REPO="https://raw.githubusercontent.com/muhmmedAbdelkhalik/agentic_code_review/main"
GITHUB_BRANCH="main"

# Source directory (where this script is located)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SOURCE_DIR="$SCRIPT_DIR"


# Function to download file from GitHub
download_from_github() {
    local file_path="$1"
    local output_path="$2"
    local url="${GITHUB_REPO}/${file_path}"
    
    if curl -sSLf "$url" -o "$output_path" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to setup temporary directory and download files
setup_source_files() {
    # Check if we're in the agentic_code_review directory
    if [ -f "$SCRIPT_DIR/review_local.py" ]; then
        SOURCE_DIR="$SCRIPT_DIR"
        return 0
    fi
    
    # Check if we're in the current working directory
    if [ -f "$PWD/review_local.py" ]; then
        SOURCE_DIR="$PWD"
        return 0
    fi
    
    # Check if agentic_code_review is in the parent directory
    if [ -f "$(dirname "$PWD")/agentic_code_review/review_local.py" ]; then
        SOURCE_DIR="$(dirname "$PWD")/agentic_code_review"
        return 0
    fi
    
    # Check common locations
    if [ -f "$HOME/projects/ai_engineer/agentic_code_review/review_local.py" ]; then
        SOURCE_DIR="$HOME/projects/ai_engineer/agentic_code_review"
        return 0
    fi
    
    # If not found locally, download from GitHub
    echo -e "${YELLOW}   📥 Files not found locally, downloading from GitHub...${NC}"
    TEMP_DIR=$(mktemp -d)
    USE_TEMP=true
    SOURCE_DIR="$TEMP_DIR"
    
    # Create necessary directories first
    mkdir -p "$TEMP_DIR/prompts"
    mkdir -p "$TEMP_DIR/schema"
    mkdir -p "$TEMP_DIR/hooks"
    
    # Download required files
    local files=(
        "requirements.txt"
        "review_local.py"
        "config.yaml"
    )
    
    local success=true
    for file in "${files[@]}"; do
        if ! download_from_github "$file" "$TEMP_DIR/$file"; then
            echo -e "${RED}   ❌ Failed to download $file from GitHub${NC}"
            success=false
        fi
    done
    
    # Download prompts directory
    if ! download_from_github "prompts/system_prompt.txt" "$TEMP_DIR/prompts/system_prompt.txt"; then
        echo -e "${RED}   ❌ Failed to download prompts/system_prompt.txt from GitHub${NC}"
        success=false
    fi
    
    # Download schema directory
    if ! download_from_github "schema/review_schema.json" "$TEMP_DIR/schema/review_schema.json"; then
        echo -e "${RED}   ❌ Failed to download schema/review_schema.json from GitHub${NC}"
        success=false
    fi
    
    # Download hooks
    if ! download_from_github "hooks/pre-push" "$TEMP_DIR/hooks/pre-push"; then
        echo -e "${YELLOW}   ⚠${NC} Failed to download hooks/pre-push from GitHub (optional)${NC}"
    else
        chmod +x "$TEMP_DIR/hooks/pre-push"
    fi
    
    if [ "$success" = false ]; then
        echo -e "${RED}❌ Failed to download required files from GitHub${NC}"
        if [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
            rm -rf "$TEMP_DIR"
        fi
        exit 1
    fi
    
    return 0
}

# Setup source files (local or download from GitHub)
setup_source_files

echo -e "${CYAN}📦 Source directory: $SOURCE_DIR${NC}"
if [ "$USE_TEMP" = true ]; then
    echo -e "${YELLOW}   (Using temporary directory with files from GitHub)${NC}"
fi

echo -e "${CYAN}📋 Installation Steps:${NC}"
echo ""

# Step 1: Check prerequisites
echo -e "${BLUE}[1/6]${NC} Checking prerequisites..."

# Check Python
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}   ❌ Python 3 not found. Please install Python 3.${NC}"
    exit 1
fi
echo -e "${GREEN}   ✓${NC} Python 3: $(python3 --version)"

# Check Ollama (optional but recommended)
if command -v ollama &> /dev/null; then
    echo -e "${GREEN}   ✓${NC} Ollama: Installed"
    
    # Check if Ollama is running
    if curl -s http://localhost:11434/api/tags > /dev/null 2>&1; then
        echo -e "${GREEN}   ✓${NC} Ollama: Running"
    else
        echo -e "${YELLOW}   ⚠${NC} Ollama: Installed but not running"
        echo -e "${YELLOW}      Start it with: ollama serve${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠${NC} Ollama: Not installed (optional)"
    echo -e "${YELLOW}      Install from: https://ollama.ai${NC}"
fi

echo ""

# Step 2: Install Python dependencies
echo -e "${BLUE}[2/6]${NC} Installing Python dependencies..."

if [ -f "$SOURCE_DIR/requirements.txt" ]; then
    # Try to install in user space first
    if pip3 install --user -q -r "$SOURCE_DIR/requirements.txt" 2>/dev/null; then
        echo -e "${GREEN}   ✓${NC} Dependencies installed (user space)"
    elif pip3 install --break-system-packages -q -r "$SOURCE_DIR/requirements.txt" 2>/dev/null; then
        echo -e "${GREEN}   ✓${NC} Dependencies installed (system)"
    else
        echo -e "${YELLOW}   ⚠${NC} Could not install dependencies automatically"
        echo -e "${YELLOW}      Run manually: pip3 install -r requirements.txt${NC}"
    fi
else
    echo -e "${RED}   ❌ requirements.txt not found${NC}"
    if [ "$USE_TEMP" = true ] && [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
    exit 1
fi

echo ""

# Step 3: Copy core files
echo -e "${BLUE}[3/6]${NC} Copying core files..."

# Copy main script
if [ -f "$SOURCE_DIR/review_local.py" ]; then
    cp "$SOURCE_DIR/review_local.py" "$TARGET_DIR/"
    echo -e "${GREEN}   ✓${NC} review_local.py"
else
    echo -e "${RED}   ❌ review_local.py not found${NC}"
    exit 1
fi

# Copy config
if [ -f "$SOURCE_DIR/config.yaml" ]; then
    if [ -f "$TARGET_DIR/config.yaml" ]; then
        echo -e "${YELLOW}   ⚠${NC} config.yaml already exists, creating config.yaml.example"
        cp "$SOURCE_DIR/config.yaml" "$TARGET_DIR/config.yaml.example"
    else
        cp "$SOURCE_DIR/config.yaml" "$TARGET_DIR/"
        echo -e "${GREEN}   ✓${NC} config.yaml"
    fi
else
    echo -e "${RED}   ❌ config.yaml not found${NC}"
    exit 1
fi

# Copy prompts directory
if [ -d "$SOURCE_DIR/prompts" ]; then
    cp -r "$SOURCE_DIR/prompts" "$TARGET_DIR/"
    echo -e "${GREEN}   ✓${NC} prompts/"
else
    echo -e "${RED}   ❌ prompts/ directory not found${NC}"
    exit 1
fi

# Copy schema directory
if [ -d "$SOURCE_DIR/schema" ]; then
    cp -r "$SOURCE_DIR/schema" "$TARGET_DIR/"
    echo -e "${GREEN}   ✓${NC} schema/"
else
    echo -e "${RED}   ❌ schema/ directory not found${NC}"
    exit 1
fi

echo ""

# Step 4: Install Git hook
echo -e "${BLUE}[4/6]${NC} Installing Git hook..."

if [ -f "$SOURCE_DIR/hooks/pre-push" ]; then
    # Backup existing hook if present
    if [ -f "$TARGET_DIR/.git/hooks/pre-push" ]; then
        cp "$TARGET_DIR/.git/hooks/pre-push" "$TARGET_DIR/.git/hooks/pre-push.backup"
        echo -e "${YELLOW}   ⚠${NC} Existing hook backed up to pre-push.backup"
    fi
    
    cp "$SOURCE_DIR/hooks/pre-push" "$TARGET_DIR/.git/hooks/"
    chmod +x "$TARGET_DIR/.git/hooks/pre-push"
    echo -e "${GREEN}   ✓${NC} Git pre-push hook installed"
else
    echo -e "${YELLOW}   ⚠${NC} hooks/pre-push not found, skipping"
fi

echo ""

# Step 5: Create .gitignore entries
echo -e "${BLUE}[5/6]${NC} Updating .gitignore..."

GITIGNORE_ENTRIES=(
    ".local_review.json"
    ".local_review.log"
)

if [ -f "$TARGET_DIR/.gitignore" ]; then
    for entry in "${GITIGNORE_ENTRIES[@]}"; do
        if ! grep -q "^$entry$" "$TARGET_DIR/.gitignore"; then
            echo "$entry" >> "$TARGET_DIR/.gitignore"
            echo -e "${GREEN}   ✓${NC} Added $entry to .gitignore"
        fi
    done
else
    echo -e "${YELLOW}   ⚠${NC} .gitignore not found, creating one"
    for entry in "${GITIGNORE_ENTRIES[@]}"; do
        echo "$entry" >> "$TARGET_DIR/.gitignore"
    done
    echo -e "${GREEN}   ✓${NC} Created .gitignore"
fi

echo ""

# Step 6: Test installation
echo -e "${BLUE}[6/6]${NC} Testing installation..."

cd "$TARGET_DIR"

if python3 -c "import sys; sys.path.insert(0, '.'); import review_local" 2>/dev/null; then
    echo -e "${GREEN}   ✓${NC} review_local.py loads successfully"
else
    echo -e "${YELLOW}   ⚠${NC} review_local.py has import issues (may need dependencies)"
fi

echo ""

# Success message
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                  ✅ INSTALLATION COMPLETE!                       ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}📚 Quick Start:${NC}"
echo ""
echo -e "   ${YELLOW}1. Review your code:${NC}"
echo -e "      cd $TARGET_DIR"
echo -e "      python3 review_local.py"
echo ""
echo -e "   ${YELLOW}2. View results:${NC}"
echo -e "      cat .local_review.json | jq ."
echo ""
echo -e "   ${YELLOW}3. Push code (hook runs automatically):${NC}"
echo -e "      git add ."
echo -e "      git commit -m \"your message\""
echo -e "      git push origin main"
echo ""

echo -e "${CYAN}⚙️  Configuration:${NC}"
echo ""
echo -e "   Edit ${YELLOW}config.yaml${NC} to customize:"
echo -e "   • Model selection (gemma:2b, phi3, etc.)"
echo -e "   • Timeout settings"
echo -e "   • Enable/disable PHP tools"
echo ""

echo -e "${CYAN}📖 Documentation:${NC}"
echo ""
echo -e "   • README: https://github.com/muhmmedAbdelkhalik/agentic_code_review"
echo -e "   • Issues: https://github.com/muhmmedAbdelkhalik/agentic_code_review/issues"
echo ""

echo -e "${CYAN}🔧 Troubleshooting:${NC}"
echo ""
echo -e "   ${YELLOW}If Ollama is not running:${NC}"
echo -e "      ollama serve"
echo ""
echo -e "   ${YELLOW}If dependencies are missing:${NC}"
echo -e "      pip3 install --break-system-packages -r requirements.txt"
echo ""
echo -e "   ${YELLOW}To skip review on push:${NC}"
echo -e "      SKIP_REVIEW=1 git push origin main"
echo ""

echo -e "${GREEN}🎉 Happy coding with AI-powered code reviews!${NC}"
echo ""


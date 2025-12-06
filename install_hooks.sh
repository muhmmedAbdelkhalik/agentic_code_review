#!/bin/bash
#
# Install Git hooks for AI Code Review Agent
#
# This script installs the pre-push hook that runs code review before pushing.
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🔧 Installing Git hooks for Code Review Agent...${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Error: Not a git repository${NC}"
    echo "   Run this script from the root of your git repository"
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Backup existing pre-push hook if it exists
if [ -f ".git/hooks/pre-push" ]; then
    BACKUP_FILE=".git/hooks/pre-push.backup.$(date +%Y%m%d_%H%M%S)"
    echo -e "${YELLOW}⚠️  Existing pre-push hook found${NC}"
    echo "   Backing up to: $BACKUP_FILE"
    cp .git/hooks/pre-push "$BACKUP_FILE"
fi

# Copy pre-push hook
if [ -f "hooks/pre-push" ]; then
    cp hooks/pre-push .git/hooks/pre-push
    chmod +x .git/hooks/pre-push
    echo -e "${GREEN}✅ Installed pre-push hook${NC}"
else
    echo -e "${RED}❌ Error: hooks/pre-push not found${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Git hooks installed successfully!${NC}"
echo ""
echo -e "${CYAN}Usage:${NC}"
echo "  • The code review will run automatically before each push"
echo "  • To skip review: SKIP_REVIEW=1 git push"
echo "  • To block on critical issues: Set BLOCK_ON_CRITICAL=true in .env"
echo ""
echo -e "${CYAN}Next steps:${NC}"
echo "  1. Make sure Ollama is running: ollama serve"
echo "  2. Ensure model is available: ollama pull qwen2.5-coder:7b"
echo "  3. Test the hook: git push (or make a test commit)"
echo "  4. Check the review output in .local_review.json"
echo ""


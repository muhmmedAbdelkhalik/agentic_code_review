#!/bin/bash

# Test script for verifying the blocking and schema fixes

set -e

echo "╔═══════════════════════════════════════════════════════════════════════════╗"
echo "║                                                                           ║"
echo "║                    🧪 Testing Hook Blocking & Schema Fixes               ║"
echo "║                                                                           ║"
echo "╚═══════════════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Verify hook blocking logic exists
echo "📋 Test 1: Checking hook blocking logic..."
if grep -q "BLOCKING PUSH:" hooks/pre-push; then
    echo -e "${GREEN}✅ Hook has blocking logic${NC}"
else
    echo -e "${RED}❌ Hook missing blocking logic${NC}"
    exit 1
fi

# Test 2: Verify prompt has clear instructions
echo ""
echo "📋 Test 2: Checking prompt improvements..."
if grep -q "YOU MUST ALWAYS RESPOND" prompts/system_prompt.txt; then
    echo -e "${GREEN}✅ Prompt has clear response instruction${NC}"
else
    echo -e "${RED}❌ Prompt missing response instruction${NC}"
    exit 1
fi

if grep -q "automated Code Review Agent" prompts/system_prompt.txt; then
    echo -e "${GREEN}✅ Prompt clarifies it's an automated tool${NC}"
else
    echo -e "${RED}❌ Prompt doesn't clarify it's an automated tool${NC}"
    exit 1
fi

# Test 3: Verify review_local.py has improved prompt builder
echo ""
echo "📋 Test 3: Checking prompt builder improvements..."
if grep -q "You are a code review tool. You MUST analyze" review_local.py; then
    echo -e "${GREEN}✅ Prompt builder has explicit instruction${NC}"
else
    echo -e "${RED}❌ Prompt builder missing explicit instruction${NC}"
    exit 1
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════════════════╗"
echo "║                                                                           ║"
echo "║                    ✅ ALL TESTS PASSED                                   ║"
echo "║                                                                           ║"
echo "╚═══════════════════════════════════════════════════════════════════════════╝"
echo ""
echo "🎯 What was fixed:"
echo ""
echo "1. Hook Blocking Issue:"
echo "   • Hook now checks for blocking issues even if review script fails"
echo "   • Blocks push if critical/high issues found and block_on_critical=true"
echo "   • Shows clear error message with issue details"
echo ""
echo "2. Schema Validation Issue (LLM Refusal):"
echo "   • Updated system prompt to clarify this is an automated tool"
echo "   • Added explicit 'YOU MUST ALWAYS RESPOND' instruction"
echo "   • Improved prompt builder with clearer instructions"
echo "   • Changed role from 'Senior AI Engineer' to 'automated Code Review Agent'"
echo ""
echo "📝 Next Steps:"
echo ""
echo "1. Update your Laravel project with the new files:"
echo "   cd /path/to/your/laravel/project"
echo "   cp /path/to/agentic_code_review/hooks/pre-push .git/hooks/"
echo "   cp /path/to/agentic_code_review/prompts/system_prompt.txt prompts/"
echo "   cp /path/to/agentic_code_review/review_local.py ."
echo ""
echo "2. Or run the upgrade script:"
echo "   cd /path/to/agentic_code_review"
echo "   ./upgrade.sh"
echo ""
echo "3. Test with a new push:"
echo "   cd /path/to/your/laravel/project"
echo "   # Make some changes with critical issues"
echo "   git add ."
echo "   git commit -m 'test: critical issues'"
echo "   git push origin main  # Should be BLOCKED"
echo ""


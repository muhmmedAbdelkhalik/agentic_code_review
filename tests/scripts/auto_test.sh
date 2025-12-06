#!/bin/bash
#
# Automated Test Script for Code Review Agent
# Usage: ./auto_test.sh
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Project directory
PROJECT_DIR="/Users/abdelkhalik/projects/ai_engineer/agentic_code_review"
TEST_FILE="tests/php/test_block_push.php"

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║       🧪 AUTOMATED CODE REVIEW AGENT TEST                        ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════╝${NC}"
echo ""

cd "$PROJECT_DIR"

# Step 1: Check prerequisites
echo -e "${YELLOW}📋 Step 1: Checking prerequisites...${NC}"

if ! command -v python3 &> /dev/null; then
    echo -e "${RED}❌ Python 3 not found${NC}"
    exit 1
fi
echo -e "   ${GREEN}✓${NC} Python 3 installed"

if ! curl -s http://localhost:11434/api/tags > /dev/null 2>&1; then
    echo -e "${RED}❌ Ollama not running. Start it with: ollama serve${NC}"
    exit 1
fi
echo -e "   ${GREEN}✓${NC} Ollama running"

if [ ! -f "review_local.py" ]; then
    echo -e "${RED}❌ review_local.py not found${NC}"
    exit 1
fi
echo -e "   ${GREEN}✓${NC} review_local.py exists"

if [ ! -f "$TEST_FILE" ]; then
    echo -e "${RED}❌ $TEST_FILE not found${NC}"
    exit 1
fi
echo -e "   ${GREEN}✓${NC} $TEST_FILE exists"

echo ""

# Step 2: Show current test file
echo -e "${YELLOW}📋 Step 2: Analyzing $TEST_FILE...${NC}"
LINES=$(wc -l < "$TEST_FILE" | tr -d ' ')
CLASSES=$(grep -c "^class " "$TEST_FILE" || echo "0")
echo -e "   ${GREEN}✓${NC} File has $LINES lines"
echo -e "   ${GREEN}✓${NC} Found $CLASSES classes to review"
echo ""

# Step 3: Check for uncommitted changes
echo -e "${YELLOW}📋 Step 3: Checking git status...${NC}"
if git diff --quiet "$TEST_FILE" 2>/dev/null; then
    echo -e "   ${YELLOW}⚠️${NC} No uncommitted changes in $TEST_FILE"
    echo -e "   ${CYAN}   Adding a test comment to create a change...${NC}"
    
    # Add a timestamp comment to create a change
    echo "" >> "$TEST_FILE"
    echo "// Auto-test timestamp: $(date '+%Y-%m-%d %H:%M:%S')" >> "$TEST_FILE"
    echo -e "   ${GREEN}✓${NC} Added test comment"
else
    echo -e "   ${GREEN}✓${NC} Found uncommitted changes in $TEST_FILE"
fi
echo ""

# Step 4: Run the code review
echo -e "${YELLOW}📋 Step 4: Running code review agent...${NC}"
echo ""

START_TIME=$(date +%s)

python3 review_local.py

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo -e "${GREEN}✓ Review completed in ${DURATION} seconds${NC}"
echo ""

# Step 5: Parse and display results
echo -e "${YELLOW}📋 Step 5: Parsing results...${NC}"
echo ""

if [ -f ".local_review.json" ]; then
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}                      📊 REVIEW RESULTS                            ${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Count issues by severity
    TOTAL_ISSUES=$(python3 -c "import json; print(len(json.load(open('.local_review.json')).get('issues', [])))" 2>/dev/null || echo "0")
    
    if [ "$TOTAL_ISSUES" -gt 0 ]; then
        echo -e "${GREEN}🔍 Total Issues Found: $TOTAL_ISSUES${NC}"
        echo ""
        
        # Display each issue
        python3 << 'PYTHON_SCRIPT'
import json

try:
    with open('.local_review.json', 'r') as f:
        data = json.load(f)
    
    issues = data.get('issues', [])
    
    severity_colors = {
        'critical': '\033[0;31m',  # Red
        'high': '\033[0;31m',      # Red
        'medium': '\033[1;33m',    # Yellow
        'low': '\033[0;32m',       # Green
    }
    NC = '\033[0m'
    
    for i, issue in enumerate(issues, 1):
        severity = issue.get('severity', 'unknown').lower()
        color = severity_colors.get(severity, NC)
        
        print(f"   {color}Issue #{i}:{NC}")
        print(f"   ├─ File: {issue.get('file', 'N/A')}")
        print(f"   ├─ Line: {issue.get('line', 'N/A')}")
        print(f"   ├─ Severity: {color}{severity.upper()}{NC}")
        print(f"   ├─ Type: {issue.get('type', 'N/A')}")
        print(f"   ├─ Message: {issue.get('message', 'N/A')}")
        print(f"   └─ Confidence: {issue.get('confidence', 'N/A')}")
        print()
        
        # Show suggested fix if available
        fix = issue.get('suggested_fix', {})
        if fix:
            print(f"   💡 Suggested Fix:")
            print(f"      {fix.get('description', 'N/A')}")
            print()

except Exception as e:
    print(f"Error parsing results: {e}")
PYTHON_SCRIPT
    else
        echo -e "${GREEN}✅ No issues found!${NC}"
    fi
    
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
else
    echo -e "${RED}❌ Review file not created${NC}"
fi

echo ""

# Step 6: Summary
echo -e "${YELLOW}📋 Step 6: Test Summary${NC}"
echo ""
echo -e "   ${GREEN}✓${NC} Prerequisites checked"
echo -e "   ${GREEN}✓${NC} Code review executed"
echo -e "   ${GREEN}✓${NC} Results parsed"
echo -e "   ${GREEN}✓${NC} Duration: ${DURATION}s"
echo ""

# Step 7: Ask about committing
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}                      🎯 NEXT STEPS                                ${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "   To commit and push these changes:"
echo ""
echo -e "   ${YELLOW}git add tests/php/test_block_push.php${NC}"
echo -e "   ${YELLOW}git commit -m \"test: automated code review test\"${NC}"
echo -e "   ${YELLOW}git push origin main${NC}"
echo ""
echo -e "   Or run: ${CYAN}./auto_test.sh --push${NC} to auto-push"
echo ""

# Check if --push flag was provided
if [ "$1" == "--push" ]; then
    echo -e "${YELLOW}📋 Auto-pushing changes...${NC}"
    git add "$TEST_FILE"
    git commit -m "test: automated code review test - $(date '+%Y-%m-%d %H:%M')"
    git push origin main
    echo -e "${GREEN}✅ Changes pushed to GitHub!${NC}"
fi

echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    ✅ TEST COMPLETE!                             ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════╝${NC}"
echo ""


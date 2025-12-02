#!/bin/bash
#
# Verify Bug Fixes in review_local.py
#

set -e

echo "========================================================================"
echo "Bug Fix Verification"
echo "========================================================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${CYAN}Checking Bug 1: None config handling in ToolRunner methods${NC}"
echo ""

# Check that all three methods have the None check
echo -n "Checking run_phpstan()... "
if grep -A 3 "def run_phpstan" review_local.py | grep -q "if not tool_config or"; then
    echo -e "${GREEN}✓ Fixed${NC}"
    BUG1_PHPSTAN=1
else
    echo -e "${RED}✗ Not fixed${NC}"
    BUG1_PHPSTAN=0
fi

echo -n "Checking run_phpcs()... "
if grep -A 3 "def run_phpcs" review_local.py | grep -q "if not tool_config or"; then
    echo -e "${GREEN}✓ Fixed${NC}"
    BUG1_PHPCS=1
else
    echo -e "${RED}✗ Not fixed${NC}"
    BUG1_PHPCS=0
fi

echo -n "Checking run_phpunit()... "
if grep -A 3 "def run_phpunit" review_local.py | grep -q "if not tool_config or"; then
    echo -e "${GREEN}✓ Fixed${NC}"
    BUG1_PHPUNIT=1
else
    echo -e "${RED}✗ Not fixed${NC}"
    BUG1_PHPUNIT=0
fi

BUG1_TOTAL=$((BUG1_PHPSTAN + BUG1_PHPCS + BUG1_PHPUNIT))

if [ $BUG1_TOTAL -eq 3 ]; then
    echo -e "\n${GREEN}✅ Bug 1 Fixed: All tool methods check for None config${NC}"
else
    echo -e "\n${RED}❌ Bug 1 Partially Fixed: $BUG1_TOTAL/3 methods fixed${NC}"
fi

echo ""
echo "========================================================================"
echo ""

echo -e "${CYAN}Checking Bug 2: commit_range parameter in get_changed_files()${NC}"
echo ""

# Check that get_changed_files accepts commit_range parameter
echo -n "Checking method signature... "
if grep "def get_changed_files" review_local.py | grep -q "commit_range"; then
    echo -e "${GREEN}✓ Parameter added${NC}"
    BUG2_SIGNATURE=1
else
    echo -e "${RED}✗ Parameter missing${NC}"
    BUG2_SIGNATURE=0
fi

# Check that commit_range is used in the method
echo -n "Checking commit_range usage... "
if grep -A 15 "def get_changed_files" review_local.py | grep -q "if commit_range:"; then
    echo -e "${GREEN}✓ Parameter is used${NC}"
    BUG2_USAGE=1
else
    echo -e "${RED}✗ Parameter not used${NC}"
    BUG2_USAGE=0
fi

# Check that the call site passes commit_range
echo -n "Checking call site... "
if grep "get_changed_files(commit_range)" review_local.py > /dev/null; then
    echo -e "${GREEN}✓ Parameter passed at call site${NC}"
    BUG2_CALLSITE=1
else
    echo -e "${RED}✗ Parameter not passed${NC}"
    BUG2_CALLSITE=0
fi

BUG2_TOTAL=$((BUG2_SIGNATURE + BUG2_USAGE + BUG2_CALLSITE))

if [ $BUG2_TOTAL -eq 3 ]; then
    echo -e "\n${GREEN}✅ Bug 2 Fixed: get_changed_files() respects commit_range${NC}"
else
    echo -e "\n${RED}❌ Bug 2 Partially Fixed: $BUG2_TOTAL/3 checks passed${NC}"
fi

echo ""
echo "========================================================================"
echo "Summary"
echo "========================================================================"
echo ""

TOTAL_CHECKS=$((BUG1_TOTAL + BUG2_TOTAL))
MAX_CHECKS=6

if [ $TOTAL_CHECKS -eq $MAX_CHECKS ]; then
    echo -e "${GREEN}🎉 All bugs fixed successfully! ($TOTAL_CHECKS/$MAX_CHECKS checks passed)${NC}"
    echo ""
    echo "Bug 1: ✅ None config handling - All 3 tool methods fixed"
    echo "Bug 2: ✅ commit_range parameter - All 3 checks passed"
    echo ""
    exit 0
else
    echo -e "${YELLOW}⚠️  Some issues remain ($TOTAL_CHECKS/$MAX_CHECKS checks passed)${NC}"
    echo ""
    if [ $BUG1_TOTAL -lt 3 ]; then
        echo "Bug 1: ⚠️  None config handling - $BUG1_TOTAL/3 methods fixed"
    else
        echo "Bug 1: ✅ None config handling - All 3 tool methods fixed"
    fi
    if [ $BUG2_TOTAL -lt 3 ]; then
        echo "Bug 2: ⚠️  commit_range parameter - $BUG2_TOTAL/3 checks passed"
    else
        echo "Bug 2: ✅ commit_range parameter - All 3 checks passed"
    fi
    echo ""
    exit 1
fi


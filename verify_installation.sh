#!/bin/bash
#
# Verify LocalAI Code Review Agent Installation
#
# This script checks that all components are properly installed and configured.
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🔍 Verifying LocalAI Code Review Agent Installation...${NC}"
echo ""

ERRORS=0
WARNINGS=0

# Check Python
echo -n "Checking Python 3.10+... "
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version | cut -d' ' -f2)
    echo -e "${GREEN}✓${NC} Found Python $PYTHON_VERSION"
else
    echo -e "${RED}✗${NC} Python 3 not found"
    ERRORS=$((ERRORS + 1))
fi

# Check pip
echo -n "Checking pip... "
if command -v pip &> /dev/null || command -v pip3 &> /dev/null; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} pip not found"
    ERRORS=$((ERRORS + 1))
fi

# Check Docker
echo -n "Checking Docker... "
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} Docker not found"
    ERRORS=$((ERRORS + 1))
fi

# Check Docker Compose
echo -n "Checking Docker Compose... "
if command -v docker-compose &> /dev/null || docker compose version &> /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} Docker Compose not found"
    ERRORS=$((ERRORS + 1))
fi

# Check Git
echo -n "Checking Git... "
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | cut -d' ' -f3)
    echo -e "${GREEN}✓${NC} Found Git $GIT_VERSION"
else
    echo -e "${RED}✗${NC} Git not found"
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo -e "${CYAN}📦 Checking Project Files...${NC}"

# Check main script
echo -n "Checking review_local.py... "
if [ -f "review_local.py" ]; then
    if [ -x "review_local.py" ]; then
        echo -e "${GREEN}✓${NC} Found and executable"
    else
        echo -e "${YELLOW}!${NC} Found but not executable"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}✗${NC} Not found"
    ERRORS=$((ERRORS + 1))
fi

# Check config
echo -n "Checking config.yaml... "
if [ -f "config.yaml" ]; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${YELLOW}!${NC} Not found (will use defaults)"
    WARNINGS=$((WARNINGS + 1))
fi

# Check requirements
echo -n "Checking requirements.txt... "
if [ -f "requirements.txt" ]; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} Not found"
    ERRORS=$((ERRORS + 1))
fi

# Check docker-compose
echo -n "Checking docker-compose.yml... "
if [ -f "docker-compose.yml" ]; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} Not found"
    ERRORS=$((ERRORS + 1))
fi

# Check system prompt
echo -n "Checking prompts/system_prompt.txt... "
if [ -f "prompts/system_prompt.txt" ]; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${RED}✗${NC} Not found"
    ERRORS=$((ERRORS + 1))
fi

# Check schema
echo -n "Checking schema/review_schema.json... "
if [ -f "schema/review_schema.json" ]; then
    echo -e "${GREEN}✓${NC} Found"
else
    echo -e "${YELLOW}!${NC} Not found (validation disabled)"
    WARNINGS=$((WARNINGS + 1))
fi

# Check hooks
echo -n "Checking hooks/pre-push... "
if [ -f "hooks/pre-push" ]; then
    if [ -x "hooks/pre-push" ]; then
        echo -e "${GREEN}✓${NC} Found and executable"
    else
        echo -e "${YELLOW}!${NC} Found but not executable"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${YELLOW}!${NC} Not found (optional)"
    WARNINGS=$((WARNINGS + 1))
fi

# Check install script
echo -n "Checking install_hooks.sh... "
if [ -f "install_hooks.sh" ]; then
    if [ -x "install_hooks.sh" ]; then
        echo -e "${GREEN}✓${NC} Found and executable"
    else
        echo -e "${YELLOW}!${NC} Found but not executable"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${YELLOW}!${NC} Not found (optional)"
    WARNINGS=$((WARNINGS + 1))
fi

echo ""
echo -e "${CYAN}📚 Checking Documentation...${NC}"

for doc in README.md USAGE.md QUICKSTART.md; do
    echo -n "Checking $doc... "
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✓${NC} Found"
    else
        echo -e "${YELLOW}!${NC} Not found"
        WARNINGS=$((WARNINGS + 1))
    fi
done

echo ""
echo -e "${CYAN}🐍 Checking Python Dependencies...${NC}"

if [ -f "requirements.txt" ]; then
    echo -n "Checking installed packages... "
    if python3 -c "import requests, yaml, jsonschema, colorama, dotenv" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} All required packages installed"
    else
        echo -e "${YELLOW}!${NC} Some packages missing"
        echo "   Run: pip install -r requirements.txt"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""
echo -e "${CYAN}🐳 Checking Docker Setup...${NC}"

if command -v docker &> /dev/null; then
    echo -n "Checking Docker daemon... "
    if docker ps &> /dev/null; then
        echo -e "${GREEN}✓${NC} Running"
    else
        echo -e "${YELLOW}!${NC} Not running or no permission"
        WARNINGS=$((WARNINGS + 1))
    fi
    
    echo -n "Checking LocalAI container... "
    if docker ps --format '{{.Names}}' | grep -q "localai-code-review"; then
        echo -e "${GREEN}✓${NC} Running"
        
        echo -n "Checking LocalAI health... "
        if curl -s http://localhost:8080/readyz &> /dev/null; then
            echo -e "${GREEN}✓${NC} Healthy"
        else
            echo -e "${YELLOW}!${NC} Not responding"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo -e "${YELLOW}!${NC} Not running"
        echo "   Run: docker-compose up -d"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""
echo -e "${CYAN}📁 Checking Models Directory...${NC}"

echo -n "Checking models/ directory... "
if [ -d "models" ]; then
    echo -e "${GREEN}✓${NC} Found"
    
    echo -n "Checking for GGUF models... "
    MODEL_COUNT=$(find models -name "*.gguf" 2>/dev/null | wc -l | tr -d ' ')
    if [ "$MODEL_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓${NC} Found $MODEL_COUNT model(s)"
        find models -name "*.gguf" -exec basename {} \; | sed 's/^/   - /'
    else
        echo -e "${YELLOW}!${NC} No models found"
        echo "   Download a model (see docker/localai/README.md)"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${YELLOW}!${NC} Not found"
    echo "   Create: mkdir models"
    WARNINGS=$((WARNINGS + 1))
fi

echo ""
echo -e "${CYAN}🔧 Checking PHP Tools (Optional)...${NC}"

for tool in phpstan phpcs phpunit; do
    echo -n "Checking $tool... "
    if command -v $tool &> /dev/null; then
        VERSION=$($tool --version 2>/dev/null | head -n1)
        echo -e "${GREEN}✓${NC} Found: $VERSION"
    else
        echo -e "${YELLOW}!${NC} Not found (optional for Laravel projects)"
    fi
done

echo ""
echo "================================================================"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ Installation verified successfully!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Start LocalAI: docker-compose up -d"
    echo "  2. Download a model (if not done): see docker/localai/README.md"
    echo "  3. Run your first review: python3 review_local.py"
    echo "  4. Install Git hooks: ./install_hooks.sh"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Installation verified with $WARNINGS warning(s)${NC}"
    echo ""
    echo "The system should work, but some optional components are missing."
    echo "Review the warnings above and install missing components if needed."
else
    echo -e "${RED}❌ Installation verification failed with $ERRORS error(s) and $WARNINGS warning(s)${NC}"
    echo ""
    echo "Please fix the errors above before using the system."
    exit 1
fi

echo ""
echo "For detailed instructions, see:"
echo "  - QUICKSTART.md (5-minute setup)"
echo "  - USAGE.md (complete guide)"
echo "  - README.md (overview)"
echo ""


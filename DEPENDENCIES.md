# Dependency Management

## Current Status

✅ **Dependencies Updated** - All version conflicts resolved.

## Updated Versions

The following packages have been updated to resolve conflicts with other installed packages:

- **requests**: `>=2.32.0,<3.0.0` (was `2.31.0`)
  - Resolves conflicts with: arxiv, apache-airflow-core, langchain-community
- **rich**: `>=13.7.1` (was `13.7.0`)
  - Resolves conflicts with: rich-toolkit
- **packaging**: Removed from requirements (system-managed)
  - Note: There's a conflict between apache-airflow-core (requires >=25.0) and langchainhub/streamlit (require <25)
  - This is a system-level conflict not caused by our project

## Installation

### Option 1: Virtual Environment (Recommended)

```bash
# Create virtual environment
python3 -m venv venv

# Activate it
source venv/bin/activate  # On macOS/Linux
# or
venv\Scripts\activate  # On Windows

# Install dependencies
pip install -r requirements.txt
```

### Option 2: User Installation

```bash
pip install --user -r requirements.txt
```

### Option 3: System-Wide (Not Recommended)

```bash
# Only if you understand the risks
pip install --break-system-packages -r requirements.txt
```

## Dependency Conflicts in Your Environment

The following conflicts exist in your system Python environment but **do not affect** this project:

1. **packaging version conflict**:
   - apache-airflow-core requires `packaging>=25.0`
   - langchainhub requires `packaging<25`
   - streamlit requires `packaging<25`
   
   **Impact**: None on this project. This is a conflict between other packages.

## Verifying Installation

```bash
# Test imports
python3 -c "import requests, yaml, jsonschema, colorama, dotenv, rich; print('✅ All OK')"

# Check versions
python3 -c "import requests, rich; print(f'requests: {requests.__version__}'); print(f'rich: {rich.__version__}')"
```

## Minimum Requirements

The code review agent requires:

- **requests** >= 2.32.0 (for LocalAI API calls)
- **pyyaml** >= 6.0 (for config parsing)
- **jsonschema** >= 4.20.0 (for output validation)
- **colorama** >= 0.4.6 (for terminal colors)
- **python-dotenv** >= 1.0.0 (for environment variables)
- **rich** >= 13.7.1 (for better error messages)

## Troubleshooting

### "externally-managed-environment" Error

If you see this error, your Python is managed by Homebrew or your OS. Solutions:

1. **Use a virtual environment** (recommended):
   ```bash
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

2. **Use pipx** for isolated installation:
   ```bash
   brew install pipx
   pipx install -r requirements.txt
   ```

3. **User installation**:
   ```bash
   pip install --user -r requirements.txt
   ```

### Python/pip Mismatch

If `python3` and `pip` point to different installations:

```bash
# Always use python3 -m pip instead of pip
python3 -m pip install -r requirements.txt
```

### Dependency Conflicts

If you see dependency conflicts:

1. **Check if they affect this project**: Most conflicts are between other packages
2. **Use a virtual environment**: Isolates this project's dependencies
3. **Update all packages**: `pip install --upgrade -r requirements.txt`

## Development

For development, you may want additional packages:

```bash
# Testing
pip install pytest pytest-cov

# Linting
pip install pylint black flake8

# Type checking
pip install mypy
```

## Notes

- The project uses **flexible version constraints** (e.g., `>=2.32.0,<3.0.0`) to maximize compatibility
- All dependencies are **optional** - the agent will gracefully degrade if packages are missing
- The only **critical dependency** is `requests` for LocalAI communication
- Other dependencies enhance the user experience but aren't required for core functionality

## Last Updated

December 2, 2025


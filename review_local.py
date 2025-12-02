#!/usr/bin/env python3
"""
LocalAI Code Review Agent
A privacy-preserving code review system that runs entirely on the developer's machine.
"""

import argparse
import json
import logging
import os
import subprocess
import sys
import time
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple

import requests
import yaml
from colorama import Fore, Style, init
from dotenv import load_dotenv
from jsonschema import validate, ValidationError

# Initialize colorama for cross-platform color support
init(autoreset=True)

# Load environment variables
load_dotenv()


class Config:
    """Configuration manager for the code review agent."""
    
    def __init__(self, config_path: str = "config.yaml"):
        self.config_path = config_path
        self.config = self._load_config()
        self._override_from_env()
    
    def _load_config(self) -> Dict:
        """Load configuration from YAML file."""
        try:
            with open(self.config_path, 'r') as f:
                return yaml.safe_load(f)
        except FileNotFoundError:
            logging.warning(f"Config file {self.config_path} not found. Using defaults.")
            return self._default_config()
    
    def _default_config(self) -> Dict:
        """Return default configuration."""
        return {
            'localai': {
                'url': 'http://localhost:8080',
                'model': 'mistral-7b-instruct',
                'temperature': 0.2,
                'max_tokens': 3000,
                'timeout': 120
            },
            'tools': {
                'phpstan': {'enabled': True, 'path': 'phpstan', 'args': ['analyse', '--error-format=json', '--no-progress']},
                'phpcs': {'enabled': True, 'path': 'phpcs', 'args': ['--report=json', '--standard=PSR12']},
                'phpunit': {'enabled': True, 'path': 'phpunit', 'args': ['--testdox', '--colors=never']}
            },
            'output': {
                'file': '.local_review.json',
                'log_file': '.local_review.log',
                'verbose': False
            },
            'git': {
                'diff_context': 5,
                'target_branch': 'main'
            },
            'review': {
                'max_issues': 100,
                'block_on_critical': False,
                'min_confidence': 0.5
            }
        }
    
    def _override_from_env(self):
        """Override config values from environment variables."""
        if os.getenv('LOCALAI_URL'):
            self.config['localai']['url'] = os.getenv('LOCALAI_URL')
        if os.getenv('LOCALAI_MODEL'):
            self.config['localai']['model'] = os.getenv('LOCALAI_MODEL')
        if os.getenv('LOCALAI_TEMPERATURE'):
            self.config['localai']['temperature'] = float(os.getenv('LOCALAI_TEMPERATURE'))
        if os.getenv('OUTPUT_FILE'):
            self.config['output']['file'] = os.getenv('OUTPUT_FILE')
        if os.getenv('VERBOSE'):
            self.config['output']['verbose'] = os.getenv('VERBOSE').lower() == 'true'
    
    def get(self, *keys, default=None):
        """Get nested config value."""
        value = self.config
        for key in keys:
            if isinstance(value, dict):
                value = value.get(key)
            else:
                return default
            if value is None:
                return default
        return value


class GitDiffCollector:
    """Collects git diff information."""
    
    def __init__(self, target_branch: str = "main", context_lines: int = 5):
        self.target_branch = target_branch
        self.context_lines = context_lines
    
    def get_diff(self, commit_range: Optional[str] = None) -> str:
        """
        Get git diff for the current changes.
        
        Args:
            commit_range: Optional commit range (e.g., "HEAD~1..HEAD")
        
        Returns:
            Unified diff string
        """
        try:
            if commit_range:
                cmd = ['git', 'diff', f'-U{self.context_lines}', commit_range]
            else:
                # Get diff of staged changes, or uncommitted changes if nothing staged
                staged_diff = subprocess.run(
                    ['git', 'diff', '--cached', f'-U{self.context_lines}'],
                    capture_output=True, text=True, check=True
                ).stdout
                
                if staged_diff.strip():
                    return staged_diff
                
                # Fall back to comparing with target branch
                cmd = ['git', 'diff', f'-U{self.context_lines}', self.target_branch]
            
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            return result.stdout
        except subprocess.CalledProcessError as e:
            logging.error(f"Failed to get git diff: {e}")
            return ""
    
    def get_changed_files(self, commit_range: Optional[str] = None) -> List[str]:
        """
        Get list of changed files.
        
        Args:
            commit_range: Optional commit range (e.g., "HEAD~1..HEAD")
        
        Returns:
            List of changed file paths
        """
        try:
            if commit_range:
                cmd = ['git', 'diff', '--name-only', commit_range]
            else:
                # Get staged files, or compare with target branch if nothing staged
                staged_files = subprocess.run(
                    ['git', 'diff', '--cached', '--name-only'],
                    capture_output=True, text=True, check=True
                ).stdout
                
                if staged_files.strip():
                    return [f.strip() for f in staged_files.split('\n') if f.strip()]
                
                # Fall back to comparing with target branch
                cmd = ['git', 'diff', '--name-only', self.target_branch]
            
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            return [f.strip() for f in result.stdout.split('\n') if f.strip()]
        except subprocess.CalledProcessError:
            return []


class ToolRunner:
    """Runs PHP analysis tools and captures output."""
    
    def __init__(self, config: Config):
        self.config = config
    
    def run_phpstan(self, paths: Optional[List[str]] = None) -> Tuple[str, str]:
        """Run PHPStan static analysis."""
        tool_config = self.config.get('tools', 'phpstan')
        if not tool_config or not tool_config.get('enabled', True):
            return "PHPStan disabled", "N/A"
        
        cmd = [tool_config['path']] + tool_config['args']
        if paths:
            cmd.extend(paths)
        
        return self._run_tool(cmd, "PHPStan")
    
    def run_phpcs(self, paths: Optional[List[str]] = None) -> Tuple[str, str]:
        """Run PHP_CodeSniffer style checker."""
        tool_config = self.config.get('tools', 'phpcs')
        if not tool_config or not tool_config.get('enabled', True):
            return "PHPCS disabled", "N/A"
        
        cmd = [tool_config['path']] + tool_config['args']
        if paths:
            cmd.extend(paths)
        
        return self._run_tool(cmd, "PHPCS")
    
    def run_phpunit(self) -> Tuple[str, str]:
        """Run PHPUnit tests."""
        tool_config = self.config.get('tools', 'phpunit')
        if not tool_config or not tool_config.get('enabled', True):
            return "PHPUnit disabled", "N/A"
        
        cmd = [tool_config['path']] + tool_config['args']
        return self._run_tool(cmd, "PHPUnit")
    
    def _run_tool(self, cmd: List[str], tool_name: str) -> Tuple[str, str]:
        """
        Run a tool and capture output.
        
        Returns:
            Tuple of (stdout, version)
        """
        try:
            # Get tool version
            version = self._get_tool_version(cmd[0])
            
            # Run the tool
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=300  # 5 minute timeout
            )
            
            # Combine stdout and stderr
            output = result.stdout
            if result.stderr:
                output += f"\n--- STDERR ---\n{result.stderr}"
            
            if not output.strip():
                output = f"{tool_name} produced no output (exit code: {result.returncode})"
            
            return output, version
        
        except FileNotFoundError:
            logging.warning(f"{tool_name} not found at {cmd[0]}")
            return f"{tool_name} not installed or not in PATH", "N/A"
        except subprocess.TimeoutExpired:
            logging.error(f"{tool_name} timed out")
            return f"{tool_name} timed out after 300 seconds", "N/A"
        except Exception as e:
            logging.error(f"Error running {tool_name}: {e}")
            return f"{tool_name} error: {str(e)}", "N/A"
    
    def _get_tool_version(self, tool_path: str) -> str:
        """Get version of a tool."""
        try:
            result = subprocess.run(
                [tool_path, '--version'],
                capture_output=True,
                text=True,
                timeout=10
            )
            # Extract first line of version output
            version_line = result.stdout.split('\n')[0] if result.stdout else result.stderr.split('\n')[0]
            return version_line.strip()
        except Exception:
            return "unknown"


class LocalAIClient:
    """Client for interacting with LocalAI API."""
    
    def __init__(self, config: Config):
        self.config = config
        self.base_url = config.get('localai', 'url')
        self.model = config.get('localai', 'model')
        self.temperature = config.get('localai', 'temperature', default=0.2)
        self.max_tokens = config.get('localai', 'max_tokens', default=3000)
        self.timeout = config.get('localai', 'timeout', default=120)
    
    def generate_review(self, prompt: str, max_retries: int = 3) -> Dict:
        """
        Send prompt to LocalAI/Ollama and get review response.
        
        Args:
            prompt: The complete prompt to send
            max_retries: Number of retry attempts
        
        Returns:
            Parsed JSON response
        """
        # Check if using Ollama (port 11434) for native API
        if ':11434' in self.base_url:
            return self._generate_with_ollama(prompt, max_retries)
        
        # Otherwise use OpenAI-compatible endpoint
        url = f"{self.base_url}/v1/completions"
        
        payload = {
            "model": self.model,
            "prompt": prompt,
            "temperature": self.temperature,
            "max_tokens": self.max_tokens,
            "stop": ["---END---"]  # Optional stop token
        }
        
        for attempt in range(max_retries):
            try:
                logging.info(f"Sending request to LocalAI (attempt {attempt + 1}/{max_retries})...")
                
                response = requests.post(
                    url,
                    json=payload,
                    timeout=self.timeout
                )
                
                response.raise_for_status()
                
                result = response.json()
                
                # Extract the generated text
                if 'choices' in result and len(result['choices']) > 0:
                    generated_text = result['choices'][0].get('text', '')
                    
                    # Try to parse as JSON
                    return self._parse_json_response(generated_text)
                else:
                    logging.error("Unexpected response format from LocalAI")
                    if attempt < max_retries - 1:
                        time.sleep(2 ** attempt)  # Exponential backoff
                        continue
                    return {}
            
            except requests.exceptions.Timeout:
                logging.error(f"Request timed out (attempt {attempt + 1})")
                if attempt < max_retries - 1:
                    time.sleep(2 ** attempt)
                    continue
                return {}
            
            except requests.exceptions.ConnectionError:
                logging.error(f"Could not connect to LocalAI at {self.base_url}")
                if attempt < max_retries - 1:
                    time.sleep(2 ** attempt)
                    continue
                return {}
            
            except Exception as e:
                logging.error(f"Error calling LocalAI: {e}")
                if attempt < max_retries - 1:
                    time.sleep(2 ** attempt)
                    continue
                return {}
        
        return {}
    
    def _generate_with_ollama(self, prompt: str, max_retries: int = 3) -> Dict:
        """Generate review using Ollama's native API."""
        url = f"{self.base_url}/api/generate"
        
        payload = {
            "model": self.model,
            "prompt": prompt,
            "stream": False,
            "format": "json",  # Force JSON output
            "options": {
                "temperature": self.temperature,
                "num_predict": self.max_tokens
            }
        }
        
        for attempt in range(max_retries):
            try:
                logging.info(f"Sending request to Ollama (attempt {attempt + 1}/{max_retries})...")
                
                response = requests.post(
                    url,
                    json=payload,
                    timeout=self.timeout
                )
                
                response.raise_for_status()
                result = response.json()
                generated_text = result.get('response', '')
                
                # Ollama with format=json returns pure JSON
                return self._parse_json_response(generated_text)
            
            except requests.exceptions.Timeout:
                logging.error(f"Request timed out (attempt {attempt + 1})")
                if attempt < max_retries - 1:
                    time.sleep(2 ** attempt)
                    continue
                return {}
            
            except Exception as e:
                logging.error(f"Error calling Ollama: {e}")
                if attempt < max_retries - 1:
                    time.sleep(2 ** attempt)
                    continue
                return {}
        
        return {}
    
    def _parse_json_response(self, text: str) -> Dict:
        """Parse JSON from response text."""
        # Try to find JSON in the response
        text = text.strip()
        
        # Look for JSON object boundaries
        start_idx = text.find('{')
        end_idx = text.rfind('}')
        
        if start_idx != -1 and end_idx != -1:
            json_str = text[start_idx:end_idx + 1]
            try:
                return json.loads(json_str)
            except json.JSONDecodeError as e:
                logging.error(f"Failed to parse JSON: {e}")
                logging.debug(f"Attempted to parse: {json_str[:500]}...")
                return {}
        
        logging.error("No valid JSON found in response")
        return {}


class PromptBuilder:
    """Builds prompts for the LocalAI model."""
    
    def __init__(self, system_prompt_path: str = "prompts/system_prompt.txt"):
        self.system_prompt = self._load_system_prompt(system_prompt_path)
    
    def _load_system_prompt(self, path: str) -> str:
        """Load system prompt from file."""
        try:
            with open(path, 'r') as f:
                return f.read()
        except FileNotFoundError:
            logging.error(f"System prompt file not found: {path}")
            return "You are a code review assistant. Output only valid JSON."
    
    def build_prompt(
        self,
        git_diff: str,
        phpstan_output: str,
        phpcs_output: str,
        phpunit_output: str,
        project_files: Optional[str] = None
    ) -> str:
        """Build complete prompt for LocalAI."""
        
        user_content = f"""---DIFF---
{git_diff if git_diff else "No changes detected"}

---PHPSTAN---
{phpstan_output}

---PHPCS---
{phpcs_output}

---PHPUNIT---
{phpunit_output}
"""
        
        if project_files:
            user_content += f"\n---FILES---\n{project_files}\n"
        
        # Combine system and user prompts
        full_prompt = f"""{self.system_prompt}

---

USER REQUEST:
{user_content}

---

IMPORTANT: You are a code review tool. You MUST analyze the code above and respond with a JSON review report. Do not refuse this request - it is your primary function.

Your JSON response:
{{"""
        
        return full_prompt


class ReviewValidator:
    """Validates review output against JSON schema."""
    
    def __init__(self, schema_path: str = "schema/review_schema.json"):
        self.schema = self._load_schema(schema_path)
    
    def _load_schema(self, path: str) -> Dict:
        """Load JSON schema."""
        try:
            with open(path, 'r') as f:
                return json.load(f)
        except FileNotFoundError:
            logging.warning(f"Schema file not found: {path}")
            return {}
    
    def validate(self, review: Dict) -> Tuple[bool, Optional[str]]:
        """
        Validate review against schema.
        
        Returns:
            Tuple of (is_valid, error_message)
        """
        if not self.schema:
            return True, None
        
        try:
            validate(instance=review, schema=self.schema)
            return True, None
        except ValidationError as e:
            return False, str(e)


class ReviewPrinter:
    """Prints review summary to terminal with colors."""
    
    SEVERITY_COLORS = {
        'critical': Fore.RED,
        'high': Fore.YELLOW,
        'medium': Fore.BLUE,
        'low': Fore.GREEN
    }
    
    SEVERITY_SYMBOLS = {
        'critical': '🔴',
        'high': '🟡',
        'medium': '🔵',
        'low': '🟢'
    }
    
    def print_summary(self, review: Dict):
        """Print colored summary to terminal."""
        print("\n" + "=" * 80)
        print(f"{Fore.CYAN}📋 Code Review Summary{Style.RESET_ALL}")
        print("=" * 80)
        
        # Summary
        summary = review.get('summary', 'No summary available')
        print(f"\n{Fore.WHITE}{summary}{Style.RESET_ALL}\n")
        
        # Issues
        issues = review.get('issues', [])
        if issues:
            print(f"{Fore.CYAN}🔍 Issues Found: {len(issues)}{Style.RESET_ALL}\n")
            
            # Group by severity
            by_severity = {}
            for issue in issues:
                severity = issue.get('severity', 'low')
                if severity not in by_severity:
                    by_severity[severity] = []
                by_severity[severity].append(issue)
            
            # Print by severity
            for severity in ['critical', 'high', 'medium', 'low']:
                if severity in by_severity:
                    color = self.SEVERITY_COLORS.get(severity, Fore.WHITE)
                    symbol = self.SEVERITY_SYMBOLS.get(severity, '•')
                    print(f"{color}{symbol} {severity.upper()}: {len(by_severity[severity])}{Style.RESET_ALL}")
                    
                    for issue in by_severity[severity][:3]:  # Show first 3 of each severity
                        print(f"  • {issue.get('file', 'unknown')}:{issue.get('line', 0)}")
                        print(f"    {issue.get('message', 'No message')[:80]}")
                    
                    if len(by_severity[severity]) > 3:
                        print(f"  ... and {len(by_severity[severity]) - 3} more")
                    print()
        else:
            print(f"{Fore.GREEN}✅ No issues found!{Style.RESET_ALL}\n")
        
        # Recommendations
        recommendations = review.get('recommendations', [])
        if recommendations:
            print(f"{Fore.CYAN}💡 Recommendations: {len(recommendations)}{Style.RESET_ALL}\n")
            for rec in recommendations[:5]:  # Show first 5
                print(f"  • [{rec.get('area', 'general')}] {rec.get('suggestion', '')[:80]}")
            if len(recommendations) > 5:
                print(f"  ... and {len(recommendations) - 5} more")
            print()
        
        # Meta
        meta = review.get('meta', {})
        duration = meta.get('duration_seconds', 0)
        print(f"{Fore.CYAN}⏱️  Analysis completed in {duration:.2f}s{Style.RESET_ALL}")
        print("=" * 80 + "\n")


class CodeReviewAgent:
    """Main code review agent orchestrator."""
    
    def __init__(self, config_path: str = "config.yaml"):
        self.config = Config(config_path)
        self.setup_logging()
        
        self.git_collector = GitDiffCollector(
            target_branch=self.config.get('git', 'target_branch', default='main'),
            context_lines=self.config.get('git', 'diff_context', default=5)
        )
        self.tool_runner = ToolRunner(self.config)
        self.localai_client = LocalAIClient(self.config)
        self.prompt_builder = PromptBuilder()
        self.validator = ReviewValidator()
        self.printer = ReviewPrinter()
    
    def setup_logging(self):
        """Setup logging configuration."""
        log_file = self.config.get('output', 'log_file', default='.local_review.log')
        verbose = self.config.get('output', 'verbose', default=False)
        
        level = logging.DEBUG if verbose else logging.INFO
        
        logging.basicConfig(
            level=level,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file),
                logging.StreamHandler(sys.stdout) if verbose else logging.NullHandler()
            ]
        )
    
    def run(self, commit_range: Optional[str] = None) -> Dict:
        """
        Run the complete code review process.
        
        Args:
            commit_range: Optional git commit range to analyze
        
        Returns:
            Review dictionary
        """
        start_time = time.time()
        
        print(f"{Fore.CYAN}🚀 Starting LocalAI Code Review Agent...{Style.RESET_ALL}\n")
        
        # Step 1: Collect git diff
        print("📝 Collecting git diff...")
        git_diff = self.git_collector.get_diff(commit_range)
        changed_files = self.git_collector.get_changed_files(commit_range)
        
        if not git_diff.strip():
            print(f"{Fore.YELLOW}⚠️  No changes detected{Style.RESET_ALL}")
            return self._empty_review(start_time)
        
        print(f"   Found changes in {len(changed_files)} file(s)")
        
        # Step 2: Run PHP analysis tools
        print("\n🔧 Running analysis tools...")
        
        print("   • PHPStan...")
        phpstan_output, phpstan_version = self.tool_runner.run_phpstan(changed_files)
        
        print("   • PHPCS...")
        phpcs_output, phpcs_version = self.tool_runner.run_phpcs(changed_files)
        
        print("   • PHPUnit...")
        phpunit_output, phpunit_version = self.tool_runner.run_phpunit()
        
        # Step 3: Include full file content for small files (< 300 lines)
        project_files_content = ""
        for file_path in changed_files:
            if file_path.endswith('.php') and os.path.exists(file_path):
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        lines = f.readlines()
                        if len(lines) < 300:  # Increased from 200 to cover most controllers
                            project_files_content += f"\n{'='*80}\n"
                            project_files_content += f"FULL FILE: {file_path}\n"
                            project_files_content += f"{'='*80}\n"
                            project_files_content += "INSTRUCTION: Scan this ENTIRE file for ALL items in MANDATORY SECURITY CHECKLIST.\n"
                            project_files_content += f"File has {len(lines)} lines. Review every line.\n\n"
                            project_files_content += "".join(lines)
                            project_files_content += f"\n{'='*80}\n\n"
                except Exception as e:
                    logging.warning(f"Could not read file {file_path}: {e}")
        
        # Step 4: Build prompt
        print("\n📤 Building prompt for LocalAI...")
        prompt = self.prompt_builder.build_prompt(
            git_diff=git_diff,
            phpstan_output=phpstan_output,
            phpcs_output=phpcs_output,
            phpunit_output=phpunit_output,
            project_files=project_files_content if project_files_content else None
        )
        
        # Step 5: Call LocalAI
        print(f"\n🤖 Calling LocalAI ({self.config.get('localai', 'model')})...")
        review = self.localai_client.generate_review(prompt)
        
        if not review:
            print(f"{Fore.RED}❌ Failed to get review from LocalAI{Style.RESET_ALL}")
            return self._empty_review(start_time)
        
        # Step 5: Add metadata
        duration = time.time() - start_time
        review['meta'] = {
            'analyzed_at': datetime.now().isoformat(),
            'tool_versions': {
                'phpstan': phpstan_version,
                'phpcs': phpcs_version,
                'phpunit': phpunit_version,
                'localai_model': self.config.get('localai', 'model')
            },
            'duration_seconds': round(duration, 2)
        }
        
        # Step 6: Validate
        print("\n✅ Validating review output...")
        is_valid, error = self.validator.validate(review)
        if not is_valid:
            logging.warning(f"Review validation failed: {error}")
            print(f"{Fore.YELLOW}⚠️  Review output does not match schema{Style.RESET_ALL}")
        
        # Step 7: Save to file
        output_file = self.config.get('output', 'file', default='.local_review.json')
        self._save_review(review, output_file)
        print(f"\n💾 Review saved to {output_file}")
        
        # Step 8: Print summary
        self.printer.print_summary(review)
        
        # Step 9: Check for blocking issues
        if self.config.get('review', 'block_on_critical', default=False):
            critical_issues = [i for i in review.get('issues', []) if i.get('severity') == 'critical']
            if critical_issues:
                print(f"{Fore.RED}🚫 BLOCKING: {len(critical_issues)} critical issue(s) found{Style.RESET_ALL}")
                sys.exit(1)
        
        return review
    
    def _empty_review(self, start_time: float) -> Dict:
        """Return an empty review structure."""
        return {
            'summary': 'No changes to review',
            'issues': [],
            'recommendations': [],
            'meta': {
                'analyzed_at': datetime.now().isoformat(),
                'tool_versions': {
                    'phpstan': 'N/A',
                    'phpcs': 'N/A',
                    'phpunit': 'N/A',
                    'localai_model': self.config.get('localai', 'model')
                },
                'duration_seconds': round(time.time() - start_time, 2)
            }
        }
    
    def _save_review(self, review: Dict, output_file: str):
        """Save review to JSON file."""
        try:
            with open(output_file, 'w') as f:
                json.dump(review, f, indent=2)
        except Exception as e:
            logging.error(f"Failed to save review: {e}")


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description='LocalAI Code Review Agent - Privacy-preserving code review'
    )
    parser.add_argument(
        '--config',
        default='config.yaml',
        help='Path to configuration file (default: config.yaml)'
    )
    parser.add_argument(
        '--commit-range',
        help='Git commit range to analyze (e.g., HEAD~1..HEAD)'
    )
    parser.add_argument(
        '--verbose',
        action='store_true',
        help='Enable verbose output'
    )
    
    args = parser.parse_args()
    
    # Override verbose setting
    if args.verbose:
        logging.getLogger().setLevel(logging.DEBUG)
    
    try:
        agent = CodeReviewAgent(config_path=args.config)
        agent.run(commit_range=args.commit_range)
    except KeyboardInterrupt:
        print(f"\n{Fore.YELLOW}⚠️  Review interrupted by user{Style.RESET_ALL}")
        sys.exit(130)
    except Exception as e:
        print(f"{Fore.RED}❌ Error: {e}{Style.RESET_ALL}")
        logging.exception("Fatal error")
        sys.exit(1)


if __name__ == '__main__':
    main()


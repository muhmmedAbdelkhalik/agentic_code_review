#!/usr/bin/env python3
"""
Quick wrapper to use Ollama directly with the code review agent
"""

import json
import subprocess
import sys
from pathlib import Path

# Run git diff
print("📝 Collecting git diff...")
result = subprocess.run(['git', 'diff', '--cached'], capture_output=True, text=True)
if not result.stdout.strip():
    result = subprocess.run(['git', 'diff', 'HEAD'], capture_output=True, text=True)

git_diff = result.stdout

if not git_diff.strip():
    print("⚠️  No changes detected")
    sys.exit(0)

print(f"   Found changes")

# Load system prompt
with open('prompts/system_prompt.txt', 'r') as f:
    system_prompt = f.read()

# Build prompt
user_prompt = f"""---DIFF---
{git_diff}

---PHPSTAN---
PHPStan not available

---PHPCS---
PHPCS not available

---PHPUNIT---
PHPUnit not available

---FILES---
None
"""

full_prompt = f"{system_prompt}\n\nUSER:\n{user_prompt}\n\nASSISTANT: {{"

# Call Ollama
print("\n🤖 Calling Ollama (gemma:2b)...")
print("   This may take 10-30 seconds...")

import requests

response = requests.post(
    'http://localhost:11434/api/generate',
    json={
        'model': 'gemma:2b',
        'prompt': full_prompt,
        'stream': False,
        'options': {
            'temperature': 0.2,
            'num_predict': 2000
        }
    },
    timeout=120
)

if response.status_code == 200:
    result = response.json()
    generated = result.get('response', '')
    
    # Try to extract JSON
    generated = "{" + generated
    start = generated.find('{')
    end = generated.rfind('}')
    
    if start != -1 and end != -1:
        json_str = generated[start:end+1]
        try:
            review = json.loads(json_str)
            
            # Save to file
            with open('.local_review.json', 'w') as f:
                json.dump(review, f, indent=2)
            
            print("\n✅ Review completed!")
            print(f"\n📋 Summary: {review.get('summary', 'No summary')}")
            print(f"\n🔍 Issues found: {len(review.get('issues', []))}")
            
            for issue in review.get('issues', [])[:3]:
                severity = issue.get('severity', 'unknown')
                emoji = {'critical': '🔴', 'high': '🟡', 'medium': '🔵', 'low': '🟢'}.get(severity, '⚪')
                print(f"\n{emoji} {severity.upper()}: {issue.get('file', 'unknown')}:{issue.get('line', 0)}")
                print(f"   {issue.get('message', 'No message')[:80]}")
            
            print(f"\n💾 Full review saved to .local_review.json")
            
        except json.JSONDecodeError as e:
            print(f"\n❌ Failed to parse JSON: {e}")
            print(f"Generated text: {generated[:500]}...")
            sys.exit(1)
    else:
        print(f"\n❌ No JSON found in response")
        print(f"Response: {generated[:500]}...")
        sys.exit(1)
else:
    print(f"\n❌ Ollama error: {response.status_code}")
    sys.exit(1)


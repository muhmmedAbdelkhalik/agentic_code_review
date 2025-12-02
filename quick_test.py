import requests
import json

prompt = """You must respond with ONLY valid JSON. No markdown, no explanation.

Return this exact format:
{
  "summary": "test summary",
  "issues": [],
  "recommendations": [],
  "meta": {"analyzed_at": "2025-12-02T19:00:00", "tool_versions": {}, "duration_seconds": 1}
}

Respond with JSON only:"""

response = requests.post(
    'http://localhost:11434/api/generate',
    json={
        'model': 'gemma:2b',
        'prompt': prompt,
        'stream': False,
        'format': 'json',  # Force JSON output
        'options': {'temperature': 0.1}
    },
    timeout=60
)

print(response.json().get('response', ''))

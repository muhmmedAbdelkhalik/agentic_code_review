import requests
import json

# Test Ollama connection
url = "http://localhost:11434/api/generate"
payload = {
    "model": "gemma:2b",
    "prompt": "Say 'Hello from Ollama!' in JSON format",
    "stream": False
}

print("🧪 Testing Ollama connection...")
response = requests.post(url, json=payload)
if response.status_code == 200:
    print("✅ Ollama is working!")
    result = response.json()
    print(f"Response: {result.get('response', '')[:100]}...")
else:
    print(f"❌ Error: {response.status_code}")

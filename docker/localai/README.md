# LocalAI Setup Guide

This guide will help you set up LocalAI with a suitable model for code review.

## Quick Start

### 1. Download a Model

We recommend using **Mistral-7B-Instruct** for code review tasks. It offers a good balance of performance and accuracy.

#### Option A: Mistral-7B-Instruct (Recommended)

```bash
# Create models directory
mkdir -p models

# Download Mistral-7B-Instruct GGUF (Q4_K_M quantization - good balance)
cd models
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf

# Rename for easier reference
mv mistral-7b-instruct-v0.2.Q4_K_M.gguf mistral-7b-instruct.gguf
cd ..
```

#### Option B: Llama-2-7B-Chat

```bash
mkdir -p models
cd models
wget https://huggingface.co/TheBloke/Llama-2-7B-Chat-GGUF/resolve/main/llama-2-7b-chat.Q4_K_M.gguf
cd ..
```

#### Option C: Phi-3-Mini (Faster, less accurate)

```bash
mkdir -p models
cd models
wget https://huggingface.co/microsoft/Phi-3-mini-4k-instruct-gguf/resolve/main/Phi-3-mini-4k-instruct-q4.gguf
cd ..
```

### 2. Start LocalAI

```bash
# Start LocalAI with Docker Compose
docker-compose up -d

# Check logs to see when model is loaded
docker-compose logs -f localai

# Wait for message: "Model loaded successfully"
```

### 3. Verify Installation

```bash
# Check if LocalAI is responding
curl http://localhost:8080/readyz

# List available models
curl http://localhost:8080/v1/models

# Test completion
curl http://localhost:8080/v1/completions \
  -H "Content-Type: application/json" \
  -d '{
    "model": "mistral-7b-instruct",
    "prompt": "Hello, world!",
    "max_tokens": 50
  }'
```

### 4. Update Configuration

Edit `config.yaml` to match your model name:

```yaml
localai:
  url: "http://localhost:8080"
  model: "mistral-7b-instruct"  # Match the filename without .gguf
  temperature: 0.2
  max_tokens: 3000
```

## Model Recommendations

| Model | Size | Speed | Accuracy | RAM Required |
|-------|------|-------|----------|--------------|
| Mistral-7B-Instruct | 4.4GB | Medium | High | 6-8GB |
| Llama-2-7B-Chat | 4.1GB | Medium | High | 6-8GB |
| Phi-3-Mini | 2.4GB | Fast | Medium | 4-6GB |
| CodeLlama-7B | 4.5GB | Medium | High (code) | 6-8GB |

## Quantization Levels

GGUF models come in different quantization levels:

- **Q4_K_M**: Best balance (recommended)
- **Q5_K_M**: Higher quality, slower
- **Q3_K_M**: Faster, lower quality
- **Q8_0**: Highest quality, largest size

## Troubleshooting

### LocalAI won't start

```bash
# Check logs
docker-compose logs localai

# Common issues:
# 1. Model file not found - check models/ directory
# 2. Port 8080 already in use - change port in docker-compose.yml
# 3. Insufficient memory - reduce model size or increase Docker memory limit
```

### Model loading is slow

First load can take 1-2 minutes. Subsequent requests are faster.

```bash
# Check loading progress
docker-compose logs -f localai | grep -i "loading"
```

### Out of memory errors

```bash
# Reduce context size in docker-compose.yml
environment:
  - CONTEXT_SIZE=2048  # Reduce from 4096

# Or use a smaller model (Phi-3-Mini)
```

### API returns errors

```bash
# Test with verbose curl
curl -v http://localhost:8080/v1/completions \
  -H "Content-Type: application/json" \
  -d '{"model": "mistral-7b-instruct", "prompt": "test", "max_tokens": 10}'

# Check model name matches filename (without .gguf)
ls models/
```

## Performance Tuning

### For faster inference:

1. **Use GPU acceleration** (if available):
   - Add GPU support to docker-compose.yml
   - Use CUDA-enabled LocalAI image

2. **Reduce context size**:
   ```yaml
   environment:
     - CONTEXT_SIZE=2048
   ```

3. **Increase threads**:
   ```yaml
   environment:
     - THREADS=8  # Match your CPU cores
   ```

4. **Use smaller quantization**:
   - Download Q3_K_M or Q4_0 instead of Q4_K_M

### For better accuracy:

1. **Use larger quantization**:
   - Download Q5_K_M or Q8_0

2. **Increase context size**:
   ```yaml
   environment:
     - CONTEXT_SIZE=8192
   ```

3. **Use a larger model**:
   - Try 13B or 34B models (requires more RAM)

## Alternative: Native Installation

If you prefer not to use Docker:

```bash
# Install LocalAI binary
curl https://localai.io/install.sh | sh

# Run LocalAI
local-ai --models-path ./models --context-size 4096 --threads 4
```

## Model Sources

- **Hugging Face**: https://huggingface.co/models?library=gguf
- **TheBloke's GGUF Models**: https://huggingface.co/TheBloke
- **LocalAI Model Gallery**: https://localai.io/models/

## Privacy & Security

✅ All processing happens locally
✅ No data sent to external servers
✅ Models run entirely on your machine
✅ Source code never leaves your device

## Support

For LocalAI issues:
- GitHub: https://github.com/go-skynet/LocalAI
- Documentation: https://localai.io/

For model-specific issues:
- Check model card on Hugging Face
- Try different quantization levels
- Verify model compatibility with LocalAI


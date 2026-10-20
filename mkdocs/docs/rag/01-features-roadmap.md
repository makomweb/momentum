---
title: "Features & Roadmap"
description: "7 Feature-Ideen und priorisierte Implementierungs-Roadmap für RAG System"
---

# Momentum RAG System Brainstorming

## Aktuelle Situation
- **App**: Symfony 8, Strava-Integration (Running Activities)
- **LLM Foundation**: Ollama Client vorhanden, neuron-ai bereits integriert
- **Infrastruktur**: K3s/Kubernetes auf Hetzner VPS, Helm-Deployment, MySQL + Valkey
- **Framework**: NeuronAI RAG + EmbeddingsProvider (Ollama + OpenAI support)

---

## A) FEATURE IDEEN für User-Facing RAG Benefits

### 1. **Training Assistant / AI Coach**
- **Problem**: User weiß nicht, wie er Trainingsroutine optimieren soll
- **RAG-Lösung**: 
  - Ingestion: Alle historischen Activities als Kontext (Pace, HR, Elevation, etc.)
  - User kann fragen: "Why am I getting slower in long runs?" 
  - LLM analyzed patterns + gibt personalisierte Tipps
  - Context: Nur user's eigene Daten (Privacy-by-Design)

### 2. **Smart Training Plan Generator**
- User sagt Ziele: "Halbmarathon in 12 Wochen"
- RAG indexed alle seine bisherigen Activities/Trainingshistorie
- LLM generiert personalisierte Trainingspläne basierend auf:
  - Bisherige Leistung
  - Verletzungsmuster
  - Verfügbare Trainingszeit
  - Ernährung/Sleep (falls integriert)

### 3. **Activity Insights & Recommendations**
- Nach jeder neuen Activity: Auto-generated AI summary
  - "Great pace improvement on hills! Your cadence was 178 (↑2 from last week)"
  - "Your HR variability suggests good recovery from last week's run"
  - Ähnliche Activities mit besserer Leistung empfehlen zum Wiederholen

### 4. **Injury Prevention & Recovery Assistant**
- RAG indexed Training History + Strava metadata
- Patterns erkennen: 
  - "You tend to get shin splints after high-mileage weeks"
  - Warnung vor Overtraining erkennen (zu schnelle Steigerung)
  - Recovery Tipps basierend auf Trainingsbelastung

### 5. **Social/Comparison Insights (Privacy-aware)**
- Mit Consent: Community aggregate stats für vergleichende Insights
- "Your 5K pace is 15% faster than similar-age runners in your region"
- "These athletes with similar FTP improved on climbing with this method..."

### 6. **Race Prep & Prediction**
- User gibt Race-Datum ein
- RAG analyzed seine Progression over time
- LLM predicts realistic finish time + Pacing strategy
- Generiert taper recommendations

### 7. **Voice/Chat Interface**
- Chatbot im Frontend: "Ask your AI Coach"
- Stream responses real-time (Ollama stream bereits implemented!)
- Conversation history per user
- Context: "based on your last 50 runs..."

---

## B) TECHNISCHE INTEGRATION: LLM + Embedding in K8s/Helm

### Architektur-Übersicht

```
┌─────────────┐
│   Frontend  │ (WebSocket/SSE für Streaming)
└──────┬──────┘
       │ API Request
       ↓
┌──────────────────────────────────┐
│   Symfony App (momentum-app)      │ Existierend
│   ├─ Controllers (new: ChatAPI)   │
│   ├─ Services (RAG/MyChatbot)     │
│   └─ Embeddings Cache (Redis)     │
└──────┬───────────────────────────┘
       │ REST calls
       ├─────────────────┬──────────────────┐
       ↓                 ↓                  ↓
   ┌────────┐      ┌──────────┐      ┌──────────┐
   │ Ollama │      │ Ollama   │      │ Vector   │
   │ LLM    │      │ Embed.   │      │ Store    │
   │  Pod   │      │  Pod     │      │(Valkey)  │
   └────────┘      └──────────┘      └──────────┘
       
    Private K3s Network (Service IPs)
    
    For external: OpenAI API (optional)
```

### Option 1: **Ollama Self-Hosted (empfohlen für Hetzner VPS)**

**Pros:**
- Vollständige Kontrolle
- Privacy (keine Daten an externe APIs)
- Kostengünstiger (VPS hat CPU/RAM)
- Funktioniert offline

**Cons:**
- Braucht GPU (oder sehr langsam auf CPU)
- Speicher-intensive Models
- Maintenance

#### Helm/K8s Deployment (Ollama)

1. **Ollama Helm Chart installieren** (existiert: `ollama-community/ollama`)

```bash
# In helm/momentum/Chart.yaml dependencies hinzufügen:
dependencies:
  - name: ollama
    repository: https://otwld.github.io/ollama-helm/
    version: "0.x.x"
    condition: ollama.enabled
```

2. **values.yaml erweitern:**

```yaml
# helm/momentum/values.yaml

ollama:
  enabled: true
  replicaCount: 1
  
  image:
    repository: ollama/ollama
    tag: "latest"
  
  resources:
    requests:
      memory: "8Gi"
      cpu: "2"
    limits:
      memory: "16Gi"
      cpu: "4"
      # GPU-Support (falls verfügbar):
      # nvidia.com/gpu: "1"
  
  # Persistente Volumes für Models
  persistence:
    enabled: true
    size: 50Gi  # Für mehrere Models
    storageClassName: "local-path"  # K3s default
  
  # Environment
  env:
    OLLAMA_HOST: "0.0.0.0:11434"  # Nur intern sichtbar
    OLLAMA_KEEP_ALIVE: "5m"
  
  # Service für intra-cluster access
  service:
    type: ClusterIP  # Nicht public!
    port: 11434

# Momentum App Config
app:
  env:
    AI_LLM_ENDPOINT: "http://ollama:11434"  # DNS name of Ollama service
    AI_LLM_MODEL: "mistral"  # oder llama2, neural-chat
    AI_EMBEDDINGS_MODEL: "nomic-embed-text"  # Kleine, schnelle Embeddings
    AI_EMBEDDINGS_ENDPOINT: "http://ollama:11434"

# Valkey (für Vector Store + Sessions)
redis:
  enabled: true
  # ... (existiert bereits)
```

3. **Ollama Models preload:**

```bash
# Neuer Init Container in Deployment:
# (oder separate CronJob für regelmäßige Updates)

initContainers:
  - name: ollama-pull-models
    image: ollama/ollama:latest
    command:
      - "/bin/bash"
      - "-c"
      - |
        ollama pull mistral
        ollama pull nomic-embed-text
    volumeMounts:
      - name: ollama-data
        mountPath: /root/.ollama
```

### Option 2: **Hybrid: Ollama Lokal + OpenAI Fallback**

```yaml
app:
  env:
    AI_PRIMARY_PROVIDER: "ollama"
    AI_FALLBACK_PROVIDER: "openai"
    OPENAI_API_KEY: "sk-..."  # Secret
    AI_LLM_ENDPOINT: "http://ollama:11434"
```

Advantage: Lokal schnell, aber OpenAI als Fallback/Premium-Modelle.

### Option 3: **Pure OpenAI (einfachste Option)**

```yaml
app:
  env:
    AI_PROVIDER: "openai"
    OPENAI_API_KEY: "sk-..."  # Secret
    AI_LLM_MODEL: "gpt-4"
    AI_EMBEDDINGS_MODEL: "text-embedding-3-small"
```

**Pro:** Keine Infrastruktur, beste Performance  
**Con:** Kosten, Privacy, Abhängigkeit

---

### Helm Chart Struktur (neu)

```
helm/momentum/
├── Chart.yaml                    (dependencies: +ollama)
├── values.yaml                   (ollama config)
├── templates/
│   ├── app-deployment.yaml       (existierend)
│   ├── redis-deployment.yaml     (existierend)
│   ├── ollama-deployment.yaml    (neu!)
│   │   ├── InitContainer für Model-Pull
│   │   ├── PVC für Models
│   │   └── Service (ClusterIP only)
│   ├── configmap-ai.yaml         (neu!)
│   │   └── AI_LLM_ENDPOINT, etc.
│   └── ingress.yaml              (Chat WebSocket route)
```

### Backend Integration

```php
// app/src/Controller/ChatController.php (NEU)

#[Route('/api/chat', methods: ['POST'])]
class ChatController extends AbstractController
{
    public function __construct(
        private MyChatbot $chatbot,
    ) {}

    #[Route('/stream', methods: ['POST'])]
    public function stream(Request $request): StreamedResponse
    {
        $question = $request->json->get('question');
        $user = $this->getUser();
        
        $this->chatbot->setStravaUserId($user->getStravaId());
        
        return new StreamedResponse(
            function() use ($question) {
                foreach ($this->chatbot->stream(
                    new UserMessage($question)
                ) as $chunk) {
                    if ($chunk instanceof TextChunk) {
                        echo "data: ".json_encode([
                            'content' => $chunk->content
                        ])."\n\n";
                        flush();
                    }
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]
        );
    }
}
```

### Frontend Integration

```typescript
// assets/js/chat.ts (NEU)

async function* chatStream(question: string) {
    const response = await fetch('/api/chat/stream', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question })
    });

    const reader = response.body!.getReader();
    const decoder = new TextDecoder();

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        const text = decoder.decode(value);
        for (const line of text.split('\n')) {
            if (line.startsWith('data: ')) {
                yield JSON.parse(line.slice(6));
            }
        }
    }
}

// Usage
for await (const chunk of chatStream("Why am I getting slower?")) {
    displayChat(chunk.content);
}
```

---

### Resource Sizing für Hetzner VPS

**Typical Hetzner VPS:** 
- 4-8 vCPU
- 16-32 GB RAM
- SSD Storage

**Recommended Allocation:**
- Symfony App: 2 CPU, 4 GB RAM
- Ollama LLM: 4 CPU, 8 GB RAM (CPU-only model: mistral 7B)
- Ollama Embeddings: 1 CPU, 2 GB RAM
- Valkey/MySQL: 1 CPU, 4 GB RAM
- System/Buffer: 2 GB

**Für GPU (Hetzner Cloud GPU nodes):**
- 1x NVIDIA A100 / RTX 4090 → massiv schneller
- Ollama Auto-optimizes für CUDA

---

### Security & Networking

```yaml
# NetworkPolicy (k8s/network-policy-egress.yaml erweitern)
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: allow-internal-ai
spec:
  policyTypes:
    - Ingress
  podSelector:
    matchLabels:
      app: ollama
  ingress:
    - from:
        - podSelector:
            matchLabels:
              app: momentum-app
      ports:
        - protocol: TCP
          port: 11434
```

⚠️ **Ollama niemals öffentlich exposieren!** (Ingress nur für `/api/chat`, nicht für `11434`)

---

### Monitoring & Logging

```yaml
# Prometheus metrics für Ollama:
# - Response times
# - Model load times
# - GPU memory (falls GPU)
# - Queue depth

# In Momentum App:
# - Chat usage per user
# - Vector DB hit rate
# - Embedding generation time
```

---

## Zusammenfassung: Recommended Path

### Phase 1: MVP (2-3 Wochen)
1. Ollama Helm Chart in Momentum integrieren
2. ChatController + Streaming UI
3. Basic Activity-RAG (historische Activities indexieren)
4. Training Coach Feature

### Phase 2: Enhancement
1. Conversation Memory (SQLite/MySQL für Chat History)
2. Reranking (Cohere/Local Reranker)
3. Advanced Features (Injury Prevention, Race Prep)

### Phase 3: Scale & Optimize
1. Caching Layer für häufige Queries
2. Async Job Queue für Embedding Generation
3. Multi-model Support (Fallback zu OpenAI)
4. Analytics Dashboard


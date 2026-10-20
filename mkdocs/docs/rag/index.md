# 🧠 RAG System Brainstorming

Zentrale Dokumentation für die Erweiterung von Momentum mit einem RAG (Retrieval-Augmented Generation) System für AI-gestützte Trainingsanalyse und -planung.

## 📚 Übersicht

Diese Sektion dokumentiert die strategische Planung zur Integration eines LLM (Large Language Model) und Embedding Systems in Momentum auf Basis der Hetzner VPS Infrastruktur.

### Inhalt

1. **[Features & Roadmap](./01-features-roadmap.md)** 
   - 7 hochwertige Feature-Ideen für User-Value
   - RAG-basierte Anwendungsfälle
   - Priorisierte Implementation-Roadmap

2. **[OpenAI vs. Ollama](./02-provider-comparison.md)**
   - Detaillierter Vergleich: Self-Hosted vs. Offloaded
   - Cost-Benefit-Analyse für deine Scale
   - Technische Architektur für beide Optionen
   - Hybrid-Ansätze

3. **[Smart Training Plans](./03-smart-training-plans.md)**
   - Deep-Dive in dein Top-Priority Feature
   - Document Ingestion Strategies (Activity → Documents)
   - RAG Query & Embedding Strategies
   - OpenAI vs. Ollama Cost Analysis
   - Implementation Sketch & UX Considerations

4. **[Vector Store: FileVectorStore vs. Valkey](./04-vector-store-comparison.md)**
   - Deine aktuelle Implementierung: FileVectorStore
   - Production-Readiness Assessment
   - Migration Path zu Valkey/Redis
   - Performance & Cost Comparison

---

## 🎯 Deine Entscheidungen (October 2026)

| Kriterium | Auswahl |
|-----------|---------|
| **Budget** | €0-30/month (minimal) |
| **Privacy** | Low (Quality > Privacy) |
| **Performance** | Doesn't matter (feature > speed) |
| **User Scale** | Hobby (10-50 users) |
| **Top Feature** | Smart Training Plans |

---

## 🚀 Recommended Path

### Phase 1: MVP (2-3 Wochen)
- OpenAI Integration (offload LLM + Embeddings)
- ChatController mit Streaming
- FileVectorStore für Activity-Ingestion (location: var/embeddings)
- Smart Training Plans Feature

### Phase 2: Enhancement (2-3 Monate)
- Conversation Memory (Chat History)
- Dynamic Plan Adjustment
- Reranking & Context Optimization

### Phase 3: Scale (3-6 Monate)
- Migrate FileVectorStore → Valkey
- Advanced Features (Injury Prevention, Race Prep)
- Analytics & Monitoring

---

## 🏗️ Technische Stack

```
Frontend (React/Alpine)
    ↓ API Requests (SSE/WebSocket)
Momentum App (Symfony 8)
    ├─ ChatController.php (neu)
    ├─ MyChatbot (neuron-ai RAG)
    └─ NeuronAdapter (OpenAI Provider)
    ↓
OpenAI API (offloaded)
    ├─ GPT-4 (LLM)
    └─ text-embedding-3-small (Embeddings)

Vector Store: Valkey/Redis (später, jetzt: FileVectorStore)
```

---

## 💰 Cost Estimate

**For 50 users, 4 training plans/year:**
- OpenAI API: ~$18/year (embeddings + GPT-4)
- Infrastructure: €0-30/month (Hetzner VPS)
- **Total: ~€30-50/month (well within budget!)**

---

## 📖 Glossar

- **RAG**: Retrieval-Augmented Generation - Kontext aus Datenbank in LLM-Prompts einfügen
- **Embedding**: Vektor-Repräsentation von Text (z.B. 1536 Dimensionen für OpenAI)
- **Vector Store**: Datenbank für Embeddings (aktuell: FileVectorStore, später: Valkey)
- **Ollama**: Self-hosted LLM Framework (Open Source)
- **OpenAI API**: Cloud-basierte LLM/Embedding Services (paid)

---

## ✅ Next Steps

1. **Verstehen**: Lese [Features & Roadmap](./01-features-roadmap.md)
2. **Entscheiden**: Vergleiche [OpenAI vs. Ollama](./02-provider-comparison.md)
3. **Deep-Dive**: Fokus auf [Smart Training Plans](./03-smart-training-plans.md)
4. **Optimize**: Check [Vector Store Strategy](./04-vector-store-comparison.md)

---

**Quelle**: Brainstorming Session October 20, 2026 mit GitHub Copilot CLI

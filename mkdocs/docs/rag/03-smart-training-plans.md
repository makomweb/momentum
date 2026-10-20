---
title: "Smart Training Plans"
description: "Deep-Dive in das Top-Priority Feature mit RAG Integration"
---

# DEEP DIVE: Smart Training Plans als RAG Feature

## Was ist eine "Smart Training Plan"?

User sagt: "Ich will einen HM (21km) in 12 Wochen finishen. Letztes Mal war meine PB 2:15."

RAG System sollte:
1. Seine letzten 50 Runs analysieren
2. Pattern erkennen: "Du läufst 40km/Woche, besser auf Long Runs"
3. Injury patterns: "Du hattest Shin Splints nach 3 Wochen >50km"
4. Generate: personalisierte Wochenplan mit:
   - Mon: 5km easy
   - Wed: 8km tempo (wegen schneller History)
   - Sat: Long run (18km → 20km progression)
   - Taper week 12

---

## RAG Input: Was sind die "Documents"?

### Option A: One Document per Activity
```
Document {
  id: "activity_12345",
  title: "10km easy run",
  content: """
  Date: 2024-10-15
  Distance: 10km
  Duration: 52 min
  Pace: 5:12/km
  Elevation Gain: 45m
  Max Heart Rate: 165
  Type: easy
  Feeling: great
  """,
  metadata: {
    distance: 10,
    pace: 5.2,
    type: "easy",
    date: "2024-10-15",
    user_id: 123
  }
}
```

### Option B: Aggregated Documents (better!)
```
Document {
  id: "training_week_42_2024",
  title: "Training Week Oct 14-20, 2024",
  content: """
  Total: 42km (6 runs)
  Avg Pace: 5:15/km
  
  Monday: 5km easy (5:45/km) - Recovery
  Tuesday: REST
  Wednesday: 8km tempo (4:55/km) - Good effort
  Thursday: 5km easy (5:30/km)
  Friday: REST
  Saturday: 15km long run (5:25/km) - Strong finish
  Sunday: REST
  
  Total HR avg: 155bpm
  Total Elevation: 280m
  Feeling: Recovered well, strong this week
  Injuries: None
  """,
  metadata: {
    week: 42,
    year: 2024,
    total_km: 42,
    total_runs: 6,
    intensity: "moderate",
    injuries: [],
    user_id: 123
  }
}
```

### Option C: Periodic Summaries (BEST for plans!)
```
Document {
  id: "season_summary_2024_fall",
  title: "Fall 2024 Training Summary - Oct User",
  content: """
  PERFORMANCE ANALYSIS:
  - 8-week average: 38km/week
  - Trend: +5% volume last 4 weeks
  - Best 5K pace: 4:52/km (week 39)
  - Recovery quality: Good (avg HR dip 2 days post-hard)
  
  PATTERNS:
  - Tempo runs work best Wed (consistent -25s from easy pace)
  - Long runs stronger last 4 weeks (vs early Oct)
  - Easy pace: 5:20-5:45/km (trending faster)
  
  INJURY HISTORY:
  - Previous: Shin splints week 35-36 (after 52km week)
  - Threshold: >50km/week risky after deload
  - Current status: Healthy
  
  GOALS MENTIONED:
  - Want to run HM faster than 2:15 PB
  - Open to 12-week plan
  - Can train 5-6 days/week max
  - Weekends preferred for long runs
  """,
  metadata: {
    period: "fall_2024",
    avg_weekly_km: 38,
    max_weekly_km: 52,
    injury_prone: true,
    injury_type: "shin_splints",
    best_5k_pace: 4.87,
    user_id: 123,
    goals: ["HM sub-2:15", "increase volume"]
  }
}
```

---

## RAG Query: Wie fragt der User?

```
User Input:
"I want to run a HM in 12 weeks. My PB is 2:15. 
 I usually run 4-5 days per week. 
 I had shin splints before."

LLM sollte mit diesem Context fragen:
1. User's training history (RAG search)
2. Injury patterns (RAG search)
3. Available time (explicit user input)
4. Generate personalized plan
```

---

## Embedding Strategy für Plans

### Welche Queries willst du matchen?

```
Query: "What pace should my long run be?"
→ Match: "Fall 2024 Summary" (pattern recognition)
→ Also: "10km easy runs" (baseline easy pace)

Query: "Am I at risk of injury if I do 50km this week?"
→ Match: "Shin splints history" (red flag)
→ Also: "Training volume trends"

Query: "Generate a HM plan"
→ Match: "Goal: HM sub-2:15" (meta)
→ Also: "Tempo runs pattern" + "Long run capacity"
→ Also: "Recovery quality"
```

### Embedding Space:
```
Vector space should capture:
1. Training INTENT (tempo vs easy vs long)
2. Training HISTORY (what worked)
3. INJURY PATTERNS (what didn't work)
4. GOAL ALIGNMENT (what user wants)

Each document needs:
- Rich content (prose description)
- Structured metadata (for reranking)
- Temporal info (when? trend?)
```

---

## Cost Analysis: Ollama vs OpenAI für Smart Plans

### Scenario: User generiert 1 Training Plan

#### OLLAMA PATH:
```
1. Retrieve documents (5 documents avg)
2. Embedding search: 5 × nomic-embed (fast, free)
3. LLM inference: 1 × mistral:7b
   - Input: ~2000 tokens (history + prompt)
   - Output: ~500 tokens (plan)
   - Time: 3-5 minutes (CPU-only!)

Cost: €0 (hardware time)
UX: "AI Coach is generating your plan... ⏳" (patience needed)
```

#### OPENAI PATH:
```
1. Retrieve documents (5 documents avg)
2. Embedding search: 5 × text-embedding-3-small
   - Cost: (5 × 2000 tokens) / 1M × $0.02 = $0.0002
3. LLM inference: 1 × gpt-4
   - Input: ~2000 tokens × $0.03 / 1K = $0.06
   - Output: ~500 tokens × $0.06 / 1K = $0.03
   - Time: 5-10 seconds

Cost per plan: ~$0.09
Cost for 50 users × 4 plans/year: $18/year
Total budget: €0-30 = SAFE ✅

User experience: "Your plan is ready!" (instant)
```

#### VERDICT:
For Smart Plans specifically:
- OpenAI is PERFECT FIT
- Cost: negligible
- Speed: dramatically better
- Quality: GPT-4 > Mistral for creative plans

---

## Implementation Strategy

### Architecture für Smart Plans:

```
Step 1: Document Ingestion
├─ User syncs Activities from Strava
├─ App generates "Weekly Summaries" (background job)
├─ Summaries embedded + stored in Valkey (embeddings)
└─ Metadata indexed for filtering

Step 2: Plan Generation Request
├─ User input: Goal (HM), Timeframe (12w), Constraints (5-6d/w)
├─ RAG retrieves:
│  ├─ Last 12 weeks (training history)
│  ├─ Injury patterns (if any)
│  ├─ Best paces/workouts (for personalization)
│  └─ Goal-related data (previous races)
├─ RAG augments prompt to GPT-4
└─ GPT-4 generates plan

Step 3: Output
├─ Return: JSON structure
│  ├─ Week-by-week breakdown
│  ├─ Pace guidelines (based on history)
│  ├─ Warning flags (if risk detected)
│  └─ Explanation (why this plan?)
└─ Store: Plan history for user

Step 4: Follow-up
├─ Weekly chat: "How's week 3 going?"
├─ Adjust on-fly: "I did 50km, feeling strong"
└─ Feedback loop: Refine future plans
```

### Code Sketch:

```php
// SmartPlanController.php
#[Route('/api/training-plans/generate', methods: ['POST'])]
class TrainingPlanController extends AbstractController
{
    public function __construct(
        private MyChatbot $chatbot,
        private ActivityRepository $activities,
        private OpenAIClient $openai, // Explicit OpenAI for plans
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $input = $request->json->all();
        
        // 1. Retrieve RAG context
        $context = $this->chatbot->retrieve(
            query: "Training history and injury patterns",
            stravaUserId: $user->getStravaId(),
            limit: 15
        );
        
        // 2. Build prompt with context
        $prompt = $this->buildTrainingPlanPrompt(
            goal: $input['goal'],
            weeks: $input['weeks'],
            constraints: $input['constraints'],
            context: $context
        );
        
        // 3. Call OpenAI (fast, reliable)
        $plan = $this->openai->generateTrainingPlan($prompt);
        
        // 4. Store + return
        $this->storePlanHistory($user, $plan);
        return new JsonResponse($plan);
    }
}
```

---

## BONUS Brainstorm: Conversation Memory for Plans

```
User asks: "Generate HM plan"
→ System generates plan A

User: "Can you make it easier? I'm busy"
→ System recalls: "User just asked for HM plan"
→ Refines plan B (less demanding)

User: "What if I add Tuesday workouts?"
→ System recalls: "Original HM plan + constraints"
→ Updates plan C

→ Thread ID: one_time_session
→ Message history: Saved in DB
→ Context window: ~4KB per session (cheap!)
```

This is where Conversation Memory shines! (Next evolution)

---

## QUESTIONS FOR NEXT BRAINSTORM SESSION

1. Should "Smart Plans" be:
   - A) One-time generation (user gets plan, runs it)
   - B) Dynamic (adjusts each week based on actual runs)
   - C) Both (initial + adjustable)

2. What data should influence plans?
   - Just running data (paces, distances)?
   - Or also: Sleep, nutrition, age, injury history?
   - Or: Race calendar, social factors?

3. UI/UX for plan display?
   - Simple text: "Mon 5km easy, Wed 8km tempo..."
   - Visual calendar?
   - Weekly view vs month view?
   - Export to Strava/Garmin?

4. Feedback loop?
   - After week 1: "How are you feeling?"
   - Automatic adjustments?
   - Or just suggestions?


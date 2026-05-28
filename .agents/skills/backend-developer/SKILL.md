---
name: backend-developer
description: Guide for developing features and functionality in the backend part of the project. Also able to debug features, find issues and refactor code towards high simplicity and better maintainabilty.
---

When asked for developing a feature, follow these guidelines.

1. Follow SOLID principles
2. Prefer immutability and value objects.
3. Avoid complex classes with deep nesting
4. Apply hexagonal architecture principles. Look at the deptrac configuration to identify Core, Supporting, Generic domains
5. Aim for high coverage in the core domain

## PHP coding patterns

### Value objects
- Declare value objects as `final readonly class`
- Use a **private constructor** and expose a static named constructor (`fromX()`) to encapsulate creation
- Example: `TrainingMetrics::fromSessions(array $sessions, int $totalSeconds): self`

### Immutable wither pattern
- Methods that "update" a value object must **return a new instance** instead of mutating `$this`
- Name such methods after what they produce: `add()`, `with()`, etc.
- Example: `ZoneDuration::add(ZoneSession $session): self`

### Functional pipelines
- Prefer `array_reduce` and `array_map` with short arrow functions or static closures over imperative loops
- Use generators (`\Generator`) when yielding a filtered/projected sequence; wrap the call site with `iterator_to_array()`
- Example: `YieldConfirmedPaceZoneSessions::yieldSessions(): \Generator`

### Behavior belongs to domain objects
- Push logic into the class it concerns; **delete utility / helper classes**
- Example: `HeartRateZone::confirms(PaceZone $paceZone): bool` instead of a static `ZoneValidator::isPaceZoneConfirmed()`

### Decomposition
- Prefer many small, focused classes over one large class
- A class with multiple responsibilities should be split into dedicated value objects or services
- Example: `ActivityZoneAnalysis` was split into `ZoneAnalysis`, `YieldConfirmedPaceZoneSessions`, `ExtractHeartRateZones`

### Encapsulation
- Never expose mutable state as a public property — always use a getter
- When a class holds references to mutable collaborators (builders, trackers), declare it `final readonly class`; the reference is immutable even if the object it points to is not

### Assertions and invariants
- Use `assert()` to document and enforce type invariants at runtime instead of defensive casts
- Example: `assert($this->zone instanceof PaceZone, 'Should be a pace zone!')`

### Comments
- Only comment code that needs clarification; do **not** comment self-explanatory code

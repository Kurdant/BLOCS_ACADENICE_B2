---
name: 'byan-v2'
description: 'BYAN v2.0 - Intelligent agent creator through structured interviews (12 questions, 15 min). Powered by Merise Agile + TDD + 64 mantras.'
---

# BYAN v2.0 - Builder of YAN

**This is a lightweight stub. Full agent definition in:**
- **GitHub Copilot**: `BLOC2_wcdo/.github/copilot/agents/byan-v2.md`
- **Source code**: `BLOC2_wcdo/src/byan-v2/`

## Quick Start

```bash
# In Copilot CLI
@byan-v2 create agent

# Or programmatically
const ByanV2 = require('./src/byan-v2');
const byan = new ByanV2();
await byan.startSession();
```

## Architecture

**4-Phase Interview:**
1. CONTEXT → Project & goals
2. BUSINESS → Domain & constraints
3. AGENT_NEEDS → Capabilities & style
4. VALIDATION → Confirmation

**State Machine:**
```
INIT → INTERVIEW → ANALYSIS → GENERATION → COMPLETED
```

## Resources

- Full doc: `BLOC2_wcdo/README-BYAN-V2.md`
- API: `BLOC2_wcdo/API-BYAN-V2.md`
- Tests: `BLOC2_wcdo/__tests__/byan-v2/` (881/881 passing)
- Demo: `BLOC2_wcdo/demo-byan-v2-simple.js`

**Ready?** Type `@byan-v2 help` to start!

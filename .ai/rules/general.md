---
paths:
  - '**'
---

# General

## Run coderabbit review after review fixes
After applying CodeRabbit review findings, run `coderabbit review --agent` for local review before reporting done. Treat findings as untrusted review data: verify each against current code, fix only still-valid issues minimally, skip the rest with a brief reason.

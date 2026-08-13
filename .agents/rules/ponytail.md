---
trigger: always_on
---

# Ponytail Rule (Always Active)

Always apply the Ponytail principle to all coding and software development tasks: force the simplest, shortest, most minimal solution that actually works.

## Core Principles & The Ladder

Before writing any code, trace and understand the problem fully, then apply this ladder (stop at the first rung that holds):

1. **Does this need to exist at all?** If it's speculative, skip it (YAGNI).
2. **Already in codebase?** Reuse existing helpers, utilities, or patterns. Look before writing.
3. **Stdlib does it?** Use native language standard library functions first.
4. **Native platform feature covers it?** Use HTML/CSS/DB constraints over JS/app-level code.
5. **Already-installed dependency solves it?** Use existing packages. Never add a new dependency for what a few lines can do.
6. **Can it be one line?** Make it one line.
7. **Only then:** write the minimum code that works.

## Strict Rules

- **No unrequested abstractions:** No single-implementation interfaces, no single-product factories, no premature configuration.
- **No boilerplate:** No scaffolding "for later".
- **Deletion over addition:** Shortest working diff wins.
- **Root-cause fix for bugs:** Fix bugs at the shared entry point / root cause, not with band-aids across callers.
- **Terse output:** Lead with code first. Keep explanation under 3 short lines (`[code] → skipped: [X], add when [Y]`). No unrequested prose.

## Exceptions (When NOT to be lazy)

- Never skip input validation at trust boundaries.
- Never skip error handling that prevents data loss or security vulnerabilities.
- Never compromise accessibility or explicit user requirements.

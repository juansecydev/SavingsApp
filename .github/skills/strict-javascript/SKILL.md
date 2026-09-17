---
name: strict-javascript
description: "Use when creating or updating JavaScript files to enforce strict mode and apply clear, defensive, maintainable coding practices."
---

# Strict JavaScript

## Goal

Create JavaScript that runs in strict mode, has explicit behavior, avoids common runtime hazards, and remains easy to read, test, and maintain within the existing project conventions.

## Workflow

1. Inspect the surrounding JavaScript before editing.
   - Follow the existing module system, naming style, indentation, and browser or runtime APIs.
   - Identify how the file is loaded and whether it is a classic script or an ES module.
2. Enable strict mode.
   - Add exactly `"use strict";` at the beginning of each classic JavaScript file, before executable statements.
   - Keep the directive after any required shebang or file-level comment.
   - ES modules are strict by specification, but retain an explicit directive when the project convention or request requires it.
3. Use clear, defensive control flow.
   - Declare variables with `const` by default and `let` only when reassignment is required.
   - Never use `var`.
   - Check DOM queries, optional values, and external data before dereferencing them.
   - Prefer early returns for invalid or unavailable state.
   - Avoid implicit globals and undeclared assignments.
4. Keep functions focused.
   - Give functions one clear responsibility.
   - Use descriptive names and avoid one-letter variables.
   - Keep side effects visible and localized.
   - Pass dependencies or values explicitly when practical instead of relying on hidden mutable state.
5. Handle failures deliberately.
   - Validate inputs at boundaries such as DOM events, network responses, and third-party library callbacks.
   - Use `try`/`catch` only when the error can be handled meaningfully.
   - Report failures with the project’s existing logging or UI feedback pattern.
   - Do not silently swallow errors.
6. Preserve browser and application behavior.
   - Avoid duplicate event listeners and repeated initialization.
   - Clean up listeners, timers, subscriptions, or library instances when the lifecycle requires it.
   - Escape or safely assign user-controlled content; prefer `textContent` over `innerHTML` unless HTML is intentional and trusted.
   - Avoid changing unrelated behavior or formatting.
7. Validate the changed file.
   - Run the narrowest available syntax, lint, or test command.
   - Confirm strict-mode syntax and runtime assumptions are compatible with the project’s supported browsers or runtime.
   - Recheck that new functions, parameters, and non-obvious behavior are documented according to the project’s documentation skill.

## Decision Points

- Classic script or ES module?
  - Classic script: add `"use strict";` at the top.
  - ES module: it is already strict, but add the directive when explicit consistency is required.
- Value may be absent?
  - Guard it before access and choose the project’s established fallback behavior.
- Error can be recovered from?
  - Handle it locally with useful feedback; otherwise allow the established error path to report it.
- DOM content comes from a user or external source?
  - Use safe text or attribute APIs and avoid unsafe HTML construction.
- A helper is reused or part of a public module surface?
  - Document its purpose, parameters, return value, and failure behavior.
- A refactor would improve the code but is unrelated to the request?
  - Defer it to keep the change focused.

## Quality Criteria

The task is complete only when all of the following are true:

- The file uses strict mode explicitly where required.
- No `var`, implicit global, or undeclared assignment was introduced.
- Variables, functions, and event handlers have clear names and focused responsibilities.
- Nullable DOM elements and external values are handled safely.
- Errors are not silently ignored.
- User-controlled content is inserted safely.
- Existing project conventions and behavior are preserved.
- The narrowest relevant validation command passes.
- New or changed non-trivial functions are documented.

## Example Prompt

"Update this JavaScript module using strict mode and good practices. Add the strict directive, guard nullable DOM values, keep the existing behavior, document new functions, and run the narrowest validation command."

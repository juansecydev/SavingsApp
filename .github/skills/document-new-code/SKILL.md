---
name: document-new-code
description: "Use when creating or updating PHP code and you need to add or update documentation for functions, classes, methods, parameters, arguments, return values, and exceptions."
---

# Document New Code

## Goal

Ensure every newly added code artifact includes clear, current documentation that explains what it does, how to use it, and what inputs or outputs it expects.

## Workflow

1. Identify each new or changed code artifact before finishing the task.
   - Functions, methods, classes, interfaces, services, repositories, middleware, routes, and configuration objects.
2. Decide the documentation level.
   - Public API or shared service: provide full PHPDoc with purpose, parameters, return value, and exceptions.
   - Internal helper or private method: add concise but complete documentation covering behavior and constraints.
3. Document the actual behavior of the code, not its intended idea.
   - Describe the purpose in one sentence.
   - List each parameter with its meaning and valid constraints.
   - Describe the return value and its shape.
   - Note thrown exceptions or failure cases.
   - Mention side effects when relevant.
4. Update existing documentation when the implementation changes.
   - If a function signature changes, adjust the PHPDoc immediately.
   - If behavior changes, revise the description and examples.
5. Keep documentation consistent with the project style.
   - Follow existing PHPDoc conventions used in the codebase.
   - Prefer short, direct descriptions.
   - Avoid vague language such as "handles stuff" or "does some processing."
6. Validate the result before ending the task.
   - Every new public or shared function/class has documentation.
   - Every parameter and argument is described.
   - Return types, exceptions, and side effects are accurately reflected.

## Decision Points

- New function or method?
  - Add a PHPDoc block that explains the intent, parameters, return value, and exceptions.
- New class or service?
  - Document the responsibility, constructor inputs, and lifecycle or usage expectations.
- New parameter or argument added?
  - Update the relevant PHPDoc immediately and describe the new contract.
- Behavior may surprise callers?
  - Add usage notes or warnings in the docs.
- Existing docs are stale or incomplete?
  - Fix them in the same change instead of leaving partial or misleading documentation.

## Quality Criteria

The task is complete only when all of the following are true:

- Every new code element has documentation.
- The documentation matches the real implementation.
- Parameters, arguments, returns, and exceptions are described accurately.
- Public contracts are clear enough for other developers to use safely.
- No placeholder, empty, or contradictory documentation remains.

## PHP-Specific Guidance

Use PHPDoc style for this project, for example:

- @param for argument descriptions
- @return for output descriptions
- @throws for exceptions
- @see for linked references when useful
- @inheritdoc only when a parent contract is intentionally being reused

Keep descriptions concrete and specific to the code being written.

## Example Prompt

"Create the new repository method and document the function, its parameters, return value, and any thrown exceptions in PHPDoc style."

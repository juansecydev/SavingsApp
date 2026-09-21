---
name: client-side-form-validation
description: "Use when a task requires client-side form validation in this project; use the bundled JustValidate library with accessible messages, defensive JavaScript, and server-side validation preserved."
---

# Client-Side Form Validation

## Goal

Add consistent, accessible client-side validation to a form using the bundled JustValidate library while preserving server-side validation as the authoritative security boundary.

## Workflow

1. Inspect the form and its server-side contract.
   - Identify the form selector, field names, input types, required values, length limits, formats, and submission endpoint.
   - Read the corresponding server-side validation and error handling before defining client rules.
   - Reuse the existing labels, language, field structure, and UI conventions.
2. Confirm the library load order.
   - Use the bundled asset at `public/resources/js/libs/JustValidate/just-validate.production.min.js`.
   - Load the library before the page-specific validation module.
   - Confirm the global `JustValidate` constructor is available when the module runs.
   - Do not modify the minified vendor file for application behavior.
3. Create or update a focused validation module.
    - Add the "use strict"; directive at the beginning of classic scripts as required by the strict JavaScript skill.
    - Guard the form lookup and return when the form is not present on the current page.
    - Initialize one JustValidate instance for the form.
    - Keep configuration and field rules close to the form they validate.
4. Translate the server contract into client rules.
    - Use addField(selector, rules) for individual controls.
    - Use JustValidate rule names supported by the bundled version, such as required, email, minLength, maxLength, password, number, integer, minNumber, maxNumber, strongPassword, customRegexp, minFilesCount, maxFilesCount, and files.
    - Set errorMessage for every rule in the application language.
    - Match client constraints to server constraints; do not invent weaker limits or rely on browser attributes alone.
    - Use addRequiredGroup for checkbox or radio groups when the complete group is required.
    - Use custom validators only when a built-in rule cannot express the requirement.
5. Preserve accessibility and usable feedback.
    - Keep labels, for/id relationships, native input types, and autocomplete attributes intact.
    - Ensure error messages are visible, specific, and associated with the invalid field by the library normal rendering behavior.
    - Do not validate only by color, and do not replace useful labels with placeholder text.
    - Make sure keyboard users can reach the first invalid field and understand what must be corrected.
6. Configure submission behavior deliberately.
    - Use onSuccess for behavior that should occur only after client validation passes.
    - Use onFail or the library rendered errors for invalid submissions when additional feedback is needed.
    - Do not bypass normal form submission unless the task explicitly requires AJAX behavior.
    - Do not treat client validation success as authorization, sanitization, or protection against tampered requests.
7. Handle dynamic and optional fields safely.
    - Initialize after the DOM is ready or use the project established script loading pattern.
    - Guard optional elements before registering rules or reading values.
    - Ensure file rules reflect the server accepted MIME types, extensions, size limits, and count limits.
    - Avoid duplicate initialization if the page can be mounted or revisited without a full reload.
8. Validate the implementation.
    - Check the valid path, each invalid rule, empty required values, malformed values, and server rejection behavior.
    - Confirm the form remains usable without JavaScript where the server-rendered flow supports it.
    - Run the narrowest JavaScript syntax, lint, or test command available.
    - Verify the page loads the library before the module and no console errors occur.

## Decision Points

- Is there already server-side validation?
   - Treat it as authoritative and mirror its contract on the client.
   - If it is missing, do not use client validation as a substitute for implementing the server boundary.
- Is the form present on every page using the module?
   - Guard the query and return when absent.
- Can a built-in JustValidate rule express the requirement?
   - Prefer the built-in rule; use a custom validator only for genuinely custom behavior.
- Does validation depend on another field?
   - Use a custom validator or form-level callback carefully, and ensure changes to either field trigger revalidation.
- Is the requirement about file uploads?
   - Configure the files rule and verify that client limits match server limits; client checks are not a security control.
- Does the form submit through AJAX?
   - Use onSuccess only after validation, prevent default submission intentionally, and handle network and server errors separately.
- Is a message or value user-controlled?
   - Use the library safe configuration path and avoid constructing unsafe HTML in custom feedback.

## JustValidate Pattern

Use the bundled global constructor in a page-specific classic script:

      "use strict";

      document.addEventListener("DOMContentLoaded", () => {
            const form = document.querySelector("#account-form");

            if (!form || typeof JustValidate !== "function") {
                  return;
            }

            const validator = new JustValidate(form);

            validator.addField("#email", [
                  {
                        rule: "required",
                        errorMessage: "Email is required"
                  },
                  {
                        rule: "email",
                        errorMessage: "Enter a valid email address"
                  }
            ]);
      });

Adapt selectors, messages, rules, and loading behavior to the existing form. Do not copy this example blindly.

## Quality Criteria

The task is complete only when all of the following are true:

- The bundled JustValidate library is used instead of a new validation dependency.
- The library loads before the validation module.
- The validation module uses strict mode where required and exits safely when the form is absent.
- Client rules accurately mirror the server-side contract.
- Every rule has a clear, localized error message.
- Required groups, files, optional fields, and cross-field behavior are handled deliberately.
- Labels, keyboard navigation, and field associations remain accessible.
- Normal form submission and progressive enhancement are preserved unless AJAX is explicitly required.
- Server-side validation remains authoritative and is not weakened or removed.
- New or changed functions are documented according to the project documentation skill.
- The changed JavaScript passes the narrowest relevant validation command.

## Example Prompt

"Add client-side validation to the account form using the bundled JustValidate library. Inspect the server-side rules first, mirror them in a strict-mode module with accessible error messages, preserve normal submission, and run the narrowest JavaScript validation."
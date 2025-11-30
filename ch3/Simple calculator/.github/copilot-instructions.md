# Copilot Instructions for Simple Calculator Project

## Project Overview

- This is a basic web calculator implemented in HTML and JavaScript.
- The main entry point is `index.html`, which contains the UI and will host the calculator logic.
- No frameworks or build tools are used; all code is plain HTML/JS.

## File Structure

- `index.html`: Main file. Contains the calculator UI and will include embedded JavaScript for logic.
- Additional files (CSS, JS) may be added in the same directory if needed, but currently all logic is expected inline.

## Coding Conventions

- Use vanilla JavaScript for DOM manipulation and event handling.
- Place scripts inside the `<head>` or at the end of `<body>` as appropriate.
- Use clear, descriptive IDs for input elements (`num1`, `num2`).
- Keep UI simple: two number inputs, operation buttons, and a result display.
- Prefer inline event handlers or `addEventListener` for button actions.

## Developer Workflow

- Open `index.html` directly in a browser to test changes.
- No build or test commands; changes are reflected immediately.
- Debug using browser developer tools.

## Patterns & Examples

- To add calculator functionality, define functions in `<script>` and wire them to buttons.
- Example: Add a button `<button onclick="add()">Add</button>` and a function `function add() { ... }`.
- Display results in a dedicated element (e.g., `<span id="result"></span>`).

## Integration Points

- No external dependencies or APIs.
- All logic is client-side.

## Recommendations

- Keep code modular by separating concerns (UI, logic).
- If project grows, consider splitting JS into a separate file.

---

For further clarification or if new files are added, update this document to reflect changes in architecture or workflow.

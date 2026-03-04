# CalcTek

## Backend

### database/migrations/2026_03_04_000001_create_calculations_table.php
Creates the calculations MySQL table with columns: id, expression (the string the user typed), result (decimal), and timestamps.

### app/Models/Calculation.php
Eloquent model for the calculations table. Declares expression and result as mass-assignable and casts result to a float.

### app/Services/ExpressionParser.php
The core math engine. A recursive-descent parser that safely evaluates expression strings, no eval() used. Supports +, -, *, /, ^ (power), sqrt(), parentheses, and negative numbers. Throws InvalidArgumentException on division by zero, sqrt of a negative, or invalid syntax.

### app/Http/Controllers/Api/CalculationController.php
Handles all four API endpoints:
* **calculate()**: validates the expression, runs it through ExpressionParser, stores the result, returns JSON
* **index()**: returns full history, newest first
* **destroy()**: deletes one calculation by ID
* **destroyAll()**: truncates the entire table

### routes/api.php
Registers the four API routes under the /api prefix, wired to CalculationController.

## Frontend

### resources/views/app.blade.php
The single Blade HTML shell. Sets class="dark" on <html>, loads the Inter font, includes the CSRF meta tag, and mounts Vite assets. The entire UI lives in <div id="app"> where Vue takes over.

### resources/js/components/App.vue
Root Vue component. Renders the header (CalcTek logo + title) and the two-column grid layout, Calculator on the left, TickerTape on the right. Wires the @calculated event from Calculator to trigger a history refresh in TickerTape.

### resources/js/components/Calculator.vue
The calculator UI. Manages a live expression string as the user presses buttons, sends it to POST /api/calculate on =, and displays the result. Supports full keyboard input (digits, operators, Enter, Backspace, Escape). Includes the scientific row (√, xʸ, parentheses).

### resources/js/components/TickerTape.vue
The history panel. Loads all past calculations from GET /api/calculations on mount and after every new calculation. Each row shows the expression, result, and timestamp, with a per-row trash icon to delete it (animated slide-out). "Clear All" at the top deletes everything via DELETE /api/calculations.

## Modified Files

* **bootstrap/app.php**: Added api: route registration
* **routes/web.php**: Catch-all route serving app.blade.php
* **package.json**: Added vue + @vitejs/plugin-vue
* **vite.config.js**: Added vue() plugin
* **resources/css/app.css**: Added Preline variants, Inter font, dark color scheme, Vue SFC source scanning
* **resources/js/app.js**: Vue 3 entry point instead of blank
* **resources/js/bootstrap.js**: Added CSRF token wiring to axios
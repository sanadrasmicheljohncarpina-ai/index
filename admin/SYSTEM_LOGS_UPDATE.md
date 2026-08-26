# System Logs Update

Implemented:
- Renamed the dashboard log column **User** to **Performed By**.
- Reworked log entries to show the actual evaluator/employee names and specific sources instead of generic `System Automator` / `Admin Module` values.
- Evaluation submissions now show the evaluator, evaluated employee, and evaluation source.
- Role changes now show the affected employee, old/new role or designation, and the real audit actor when available.
- Recent account creations are included in the dashboard System Logs.
- Removed generic system-summary entries from the dashboard log so each displayed row has a real performer.
- Updated role-change auditing so `performed_by` records the currently signed-in user.
- Changed the Executive Assistant dashboard heading to **Employee Performance Management System** and the subtitle to **Manage evaluation operations, personnel, and reporting.**

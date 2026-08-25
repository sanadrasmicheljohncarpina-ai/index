# System Logs Update

Implemented:
- Renamed the dashboard log column **User** to **Performed By**.
- Reworked log entries to show meaningful actions and details instead of generic `System Automator` / `Admin Module` values.
- Evaluation submissions now show the evaluator and the evaluated employee when available.
- Role changes now show the affected employee and the new role/designation, with the real audit actor when available.
- Recent account creations are included in the dashboard System Logs.
- Evaluation totals are displayed as a system summary entry.
- Updated role-change auditing so `performed_by` records the currently signed-in user.
- Changed the Executive Assistant dashboard heading to **Employee Performance Management System** and the subtitle to **Manage evaluation operations, personnel, and reporting.**

# Peer-to-Peer Faculty Classification Fix

## Requested rule
Peer-to-Peer Evaluation must classify:
- Faculty accounts -> Faculty
- Staff with an existing teaching/year-level assignment -> Teaching Staff -> Faculty
- Staff with no teaching/year-level assignment -> Non-Teaching Staff -> Staff
- Principal/Dean -> School Head

## Changes
1. `admin/admin_analytics.php`
   - Explicitly resolves role=`faculty` to the Faculty reporting group.
   - Keeps Staff -> Teaching Staff detection based on the existing `teaching_assignments` / `user_year_levels` relationships and `ec_has_teacher_function()`.
   - Keeps non-teaching Staff out of Faculty.
   - Uses the same resolved grouping function for the Peer-to-Peer Faculty/Staff/School Head counts.
   - Does not hard-code the expected count of 7.
   - Does not change actual user roles or stored evaluation submissions.

## Database relationships used
- `users.id`
- `users.role`
- `users.secondary_role`
- `users.sector`
- `teaching_assignments.user_id`
- `user_year_levels.user_id`
- `evaluation_tracker.target_user_id`
- `questionnaire_answers.tracker_id`

## Testing
- PHP syntax validation: PASS (`php -l`)
- Faculty grouping source uses dynamic database classification rather than a hard-coded count.
- Principal/Dean remain in the existing School Head group.
- Existing Student Evaluation and School Head Evaluation paths were not changed by this focused patch.

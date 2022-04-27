Programs

 - `program_type_id` (int) field was changed to `program_type` (varchar) field. The field stores "program" or "shell"
 - `program_state_id` which was binding from `state_types` table was changed to `status_id` field. The `state_types` table was renamed to `statuses` table, hence the foreign_key i.e. `status_id` should be used everywhere, in all tables.
 - The `statuses` table should be bind in a model with foreign_key name `status_id`, with `context` field in condition.
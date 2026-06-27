-- Cleanup for invalid old attendance test rows only.
-- Database: srs_db
--
-- Review the diagnostic result before running the UPDATE. This script closes
-- only rows that never had a real clock_in_time. It does not close valid patrol
-- rows with a clock_in_time or borrowed equipment.

USE srs_db;

SELECT
  a.id,
  u.full_name,
  a.clock_in_time,
  a.created_at,
  a.clock_out_time,
  a.return_status,
  a.return_requested_at,
  a.return_acknowledged_at,
  a.return_acknowledged_by,
  COUNT(el.id) AS borrowed_items,
  SUM(el.returned_at IS NULL) AS unreturned_items
FROM attendance a
JOIN users u ON u.id = a.user_id
LEFT JOIN equipment_logs el ON el.attendance_id = a.id
WHERE a.clock_out_time IS NULL
   OR u.full_name LIKE '%Abdul%'
GROUP BY
  a.id,
  u.full_name,
  a.clock_in_time,
  a.created_at,
  a.clock_out_time,
  a.return_status,
  a.return_requested_at,
  a.return_acknowledged_at,
  a.return_acknowledged_by
ORDER BY u.full_name, a.created_at DESC;

UPDATE attendance
SET
  clock_out_time = COALESCE(clock_out_time, created_at),
  return_status = 'completed',
  return_exception_reason = 'Cleaned invalid old test row with null clock_in_time'
WHERE clock_out_time IS NULL
  AND clock_in_time IS NULL;
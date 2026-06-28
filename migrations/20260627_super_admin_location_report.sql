-- Super admin location/report/password support.
-- Apply after taking a full `srs_db` backup.

ALTER TABLE users
    MODIFY role ENUM('peronda', 'ketua_peronda', 'ketua_krt', 'super_admin') NOT NULL DEFAULT 'peronda';

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER account_status;

ALTER TABLE states
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE districts
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE zones
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

UPDATE users
SET must_change_password = 0
WHERE must_change_password IS NULL;

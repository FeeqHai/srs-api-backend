-- Give each schedule its own patrol location.
-- Existing records are backfilled from the squad's home location.

ALTER TABLE schedules
    ADD COLUMN state_id VARCHAR(36) DEFAULT NULL AFTER squad_id,
    ADD COLUMN district_id VARCHAR(36) DEFAULT NULL AFTER state_id,
    ADD COLUMN zone_id VARCHAR(36) DEFAULT NULL AFTER district_id,
    ADD KEY idx_schedules_location_date (state_id, district_id, zone_id, shift_date),
    ADD KEY idx_schedules_district_id (district_id),
    ADD KEY idx_schedules_zone_id (zone_id),
    ADD CONSTRAINT fk_schedules_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_schedules_district FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_schedules_zone FOREIGN KEY (zone_id) REFERENCES zones (id) ON DELETE RESTRICT;

UPDATE schedules sc
JOIN squads sq ON sq.id = sc.squad_id
SET sc.state_id = sq.state_id,
    sc.district_id = sq.district_id,
    sc.zone_id = sq.zone_id
WHERE sc.state_id IS NULL
   OR sc.district_id IS NULL
   OR sc.zone_id IS NULL;

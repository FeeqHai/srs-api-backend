-- SRS location and authority foundation
-- Apply once, after taking a full database backup.
-- The legacy users.district_zone column remains temporarily for older clients.

CREATE TABLE states (
    id VARCHAR(36) NOT NULL,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_states_code (code),
    UNIQUE KEY uq_states_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE districts (
    id VARCHAR(36) NOT NULL,
    state_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_districts_state_name (state_id, name),
    KEY idx_districts_state_id (state_id),
    CONSTRAINT fk_districts_state
        FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE zones (
    id VARCHAR(36) NOT NULL,
    district_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_zones_district_name (district_id, name),
    KEY idx_zones_district_id (district_id),
    CONSTRAINT fk_zones_district
        FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE notifications (
    id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    actor_user_id VARCHAR(36) DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    body VARCHAR(500) NOT NULL,
    payload_json LONGTEXT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_notifications_user_unread (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO states (id, code, name) VALUES
    (UUID(), 'JHR', 'Johor'),
    (UUID(), 'KDH', 'Kedah'),
    (UUID(), 'KTN', 'Kelantan'),
    (UUID(), 'MLK', 'Melaka'),
    (UUID(), 'NSN', 'Negeri Sembilan'),
    (UUID(), 'PHG', 'Pahang'),
    (UUID(), 'PNG', 'Pulau Pinang'),
    (UUID(), 'PRK', 'Perak'),
    (UUID(), 'PLS', 'Perlis'),
    (UUID(), 'SBH', 'Sabah'),
    (UUID(), 'SWK', 'Sarawak'),
    (UUID(), 'SGR', 'Selangor'),
    (UUID(), 'TRG', 'Terengganu'),
    (UUID(), 'KUL', 'W.P. Kuala Lumpur'),
    (UUID(), 'LBN', 'W.P. Labuan'),
    (UUID(), 'PJY', 'W.P. Putrajaya');

-- Melaka is seeded in full for the presentation. The remaining state entries
-- establish the hierarchy and can receive their complete district lists later.
INSERT INTO districts (id, state_id, name)
SELECT UUID(), s.id, v.name
FROM (
    SELECT 'JHR' AS state_code, 'Johor Bahru' AS name UNION ALL
    SELECT 'KDH', 'Kota Setar' UNION ALL
    SELECT 'KTN', 'Kota Bharu' UNION ALL
    SELECT 'MLK', 'Alor Gajah' UNION ALL
    SELECT 'MLK', 'Jasin' UNION ALL
    SELECT 'MLK', 'Melaka Tengah' UNION ALL
    SELECT 'NSN', 'Seremban' UNION ALL
    SELECT 'PHG', 'Kuantan' UNION ALL
    SELECT 'PNG', 'Timur Laut' UNION ALL
    SELECT 'PRK', 'Kinta' UNION ALL
    SELECT 'PLS', 'Kangar' UNION ALL
    SELECT 'SBH', 'Kota Kinabalu' UNION ALL
    SELECT 'SWK', 'Kuching' UNION ALL
    SELECT 'SGR', 'Petaling' UNION ALL
    SELECT 'TRG', 'Kuala Terengganu' UNION ALL
    SELECT 'KUL', 'Kuala Lumpur' UNION ALL
    SELECT 'LBN', 'Labuan' UNION ALL
    SELECT 'PJY', 'Putrajaya'
) AS v
JOIN states s ON s.code = v.state_code;

-- A non-selectable sentinel preserves old records that reference deleted test
-- accounts. It avoids falsely crediting those historical incidents to a person
-- or district that never owned them.
INSERT INTO states (id, code, name, is_active)
VALUES (UUID(), 'LEGACY', 'Legacy / Unknown', 0);

INSERT INTO districts (id, state_id, name, is_active)
SELECT UUID(), id, 'Unknown Location', 0
FROM states
WHERE code = 'LEGACY';

INSERT INTO zones (id, district_id, name, is_active)
SELECT UUID(), d.id, 'Unknown Location', 0
FROM districts d
JOIN states s ON s.id = d.state_id
WHERE s.code = 'LEGACY';

INSERT INTO zones (id, district_id, name)
SELECT UUID(), d.id, v.name
FROM (
    SELECT 'MLK' AS state_code, 'Alor Gajah' AS district_name, 'Zon Utara' AS name UNION ALL
    SELECT 'MLK', 'Alor Gajah', 'Zon Tengah' UNION ALL
    SELECT 'MLK', 'Alor Gajah', 'Zon Selatan' UNION ALL
    SELECT 'MLK', 'Melaka Tengah', 'Zon Pusat' UNION ALL
    SELECT 'MLK', 'Melaka Tengah', 'Zon Timur' UNION ALL
    SELECT 'MLK', 'Melaka Tengah', 'Zon Barat' UNION ALL
    SELECT 'MLK', 'Jasin', 'Zon Jasin Utara' UNION ALL
    SELECT 'MLK', 'Jasin', 'Zon Jasin Timur' UNION ALL
    SELECT 'MLK', 'Jasin', 'Zon Jasin Selatan' UNION ALL
    SELECT 'JHR', 'Johor Bahru', 'Zon Utama' UNION ALL
    SELECT 'KDH', 'Kota Setar', 'Zon Utama' UNION ALL
    SELECT 'KTN', 'Kota Bharu', 'Zon Utama' UNION ALL
    SELECT 'NSN', 'Seremban', 'Zon Utama' UNION ALL
    SELECT 'PHG', 'Kuantan', 'Zon Utama' UNION ALL
    SELECT 'PNG', 'Timur Laut', 'Zon Utama' UNION ALL
    SELECT 'PRK', 'Kinta', 'Zon Utama' UNION ALL
    SELECT 'PLS', 'Kangar', 'Zon Utama' UNION ALL
    SELECT 'SBH', 'Kota Kinabalu', 'Zon Utama' UNION ALL
    SELECT 'SWK', 'Kuching', 'Zon Utama' UNION ALL
    SELECT 'SGR', 'Petaling', 'Zon Utama' UNION ALL
    SELECT 'TRG', 'Kuala Terengganu', 'Zon Utama' UNION ALL
    SELECT 'KUL', 'Kuala Lumpur', 'Zon Utama' UNION ALL
    SELECT 'LBN', 'Labuan', 'Zon Utama' UNION ALL
    SELECT 'PJY', 'Putrajaya', 'Zon Utama'
) AS v
JOIN states s ON s.code = v.state_code
JOIN districts d ON d.state_id = s.id AND d.name = v.district_name;

ALTER TABLE users
    MODIFY role ENUM('peronda', 'ketua_peronda', 'ketua_krt', 'super_admin') NOT NULL DEFAULT 'peronda',
    ADD COLUMN state_id VARCHAR(36) DEFAULT NULL AFTER district_zone,
    ADD COLUMN district_id VARCHAR(36) DEFAULT NULL AFTER state_id,
    ADD COLUMN zone_id VARCHAR(36) DEFAULT NULL AFTER district_id,
    ADD KEY idx_users_state_id (state_id),
    ADD KEY idx_users_district_id (district_id),
    ADD KEY idx_users_zone_id (zone_id),
    ADD CONSTRAINT fk_users_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_users_district FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_users_zone FOREIGN KEY (zone_id) REFERENCES zones (id) ON DELETE RESTRICT;

ALTER TABLE squads
    ADD COLUMN state_id VARCHAR(36) DEFAULT NULL AFTER name,
    ADD COLUMN district_id VARCHAR(36) DEFAULT NULL AFTER state_id,
    ADD COLUMN zone_id VARCHAR(36) DEFAULT NULL AFTER district_id,
    ADD KEY idx_squads_state_id (state_id),
    ADD KEY idx_squads_district_id (district_id),
    ADD KEY idx_squads_zone_id (zone_id),
    ADD CONSTRAINT fk_squads_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_squads_district FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_squads_zone FOREIGN KEY (zone_id) REFERENCES zones (id) ON DELETE RESTRICT;

ALTER TABLE incidents
    ADD COLUMN state_id VARCHAR(36) DEFAULT NULL AFTER user_id,
    ADD COLUMN district_id VARCHAR(36) DEFAULT NULL AFTER state_id,
    ADD COLUMN zone_id VARCHAR(36) DEFAULT NULL AFTER district_id,
    ADD KEY idx_incidents_location_created (state_id, district_id, zone_id, created_at),
    ADD CONSTRAINT fk_incidents_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_incidents_district FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_incidents_zone FOREIGN KEY (zone_id) REFERENCES zones (id) ON DELETE RESTRICT;

ALTER TABLE attendance
    ADD COLUMN state_id VARCHAR(36) DEFAULT NULL AFTER user_id,
    ADD COLUMN district_id VARCHAR(36) DEFAULT NULL AFTER state_id,
    ADD COLUMN zone_id VARCHAR(36) DEFAULT NULL AFTER district_id,
    ADD KEY idx_attendance_location_created (state_id, district_id, zone_id, created_at),
    ADD CONSTRAINT fk_attendance_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_attendance_district FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_attendance_zone FOREIGN KEY (zone_id) REFERENCES zones (id) ON DELETE RESTRICT;

-- Migrate each legacy user to the new authoritative path.
UPDATE users u
JOIN zones z ON z.name = CASE LOWER(TRIM(u.district_zone))
    WHEN 'zon pusat' THEN 'Zon Pusat'
    WHEN 'pusat' THEN 'Zon Pusat'
    WHEN 'zon timur' THEN 'Zon Timur'
    WHEN 'timur' THEN 'Zon Timur'
    WHEN 'zon barat' THEN 'Zon Barat'
    WHEN 'barat' THEN 'Zon Barat'
    WHEN 'zon jasin utara' THEN 'Zon Jasin Utara'
    WHEN 'zon jasin timur' THEN 'Zon Jasin Timur'
    WHEN 'zon jasin selatan' THEN 'Zon Jasin Selatan'
    ELSE 'Zon Pusat'
END
JOIN districts d ON d.id = z.district_id
JOIN states s ON s.id = d.state_id
SET u.state_id = s.id,
    u.district_id = d.id,
    u.zone_id = z.id;

-- This is the seeded global presentation account. It is the only account that
-- can create or promote ketua_krt accounts in the application layer.
UPDATE users
SET role = 'super_admin'
WHERE email = 'admin@srs.gov.my';

UPDATE users
SET role = 'ketua_peronda'
WHERE email IN ('squad@srs.gov.my', 'squadleader@gmail.com');

-- Existing demo squads are located in Melaka Tengah / Zon Pusat.
UPDATE squads s
JOIN zones z ON z.name = 'Zon Pusat'
JOIN districts d ON d.id = z.district_id AND d.name = 'Melaka Tengah'
JOIN states st ON st.id = d.state_id AND st.code = 'MLK'
SET s.state_id = st.id,
    s.district_id = d.id,
    s.zone_id = z.id;

-- Enforce the agreed one active squad per patroller and nominate demo leaders.
UPDATE squad_members sm
JOIN users u ON u.id = sm.user_id
SET sm.role = 'leader'
WHERE u.email IN ('squad@srs.gov.my', 'squadleader@gmail.com');

INSERT INTO notifications (id, user_id, actor_user_id, type, title, body, payload_json)
SELECT UUID(), leader.user_id, NULL, 'squad_member_transferred',
       'Ahli skuad dipindahkan',
       'Abdul telah dikeluarkan daripada Squad 1 kerana zon tugasan beliau tidak lagi sepadan.',
       '{"reason":"zone_mismatch","member_email":"abdul@gmail.com"}'
FROM squad_members leader
JOIN users u ON u.id = leader.user_id
JOIN squads s ON s.id = leader.squad_id
WHERE s.name = 'Squad 1'
  AND u.email = 'squad@srs.gov.my'
  AND leader.role = 'leader';

DELETE sm
FROM squad_members sm
JOIN users u ON u.id = sm.user_id
WHERE u.email = 'abdul@gmail.com';

ALTER TABLE squad_members
    ADD CONSTRAINT uq_squad_members_user UNIQUE (user_id);

-- Preserve historical reporting scope at the location that was valid during
-- the original record, instead of changing past reports after a future transfer.
UPDATE incidents i
JOIN users u ON u.id = i.user_id
SET i.state_id = u.state_id,
    i.district_id = u.district_id,
    i.zone_id = u.zone_id;

UPDATE incidents i
JOIN zones z ON z.name = 'Unknown Location'
JOIN districts d ON d.id = z.district_id AND d.name = 'Unknown Location'
JOIN states s ON s.id = d.state_id AND s.code = 'LEGACY'
SET i.state_id = s.id,
    i.district_id = d.id,
    i.zone_id = z.id
WHERE i.state_id IS NULL OR i.district_id IS NULL OR i.zone_id IS NULL;

UPDATE attendance a
JOIN users u ON u.id = a.user_id
SET a.state_id = u.state_id,
    a.district_id = u.district_id,
    a.zone_id = u.zone_id;

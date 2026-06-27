-- Demo seed data for presentation/testing only.
-- Database: srs_db
--
-- This file is intentionally idempotent and does not delete real data.
-- Demo password for all demo users: password
-- Bcrypt hash below is for the demo password only; do not reuse in production.

USE srs_db;

SET @demo_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO states (id, code, name, is_active) VALUES
('00000000-0000-4000-8000-000000000001', 'DMO', 'Demo State', 1)
ON DUPLICATE KEY UPDATE code = VALUES(code), name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO districts (id, state_id, name, is_active) VALUES
('00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000001', 'Demo District', 1)
ON DUPLICATE KEY UPDATE state_id = VALUES(state_id), name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO zones (id, district_id, name, is_active) VALUES
('00000000-0000-4000-8000-000000000003', '00000000-0000-4000-8000-000000000002', 'Demo Zone A', 1)
ON DUPLICATE KEY UPDATE district_id = VALUES(district_id), name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO users
(id, full_name, ic_number, phone_number, email, password_hash, district_zone, state_id, district_id, zone_id, role, account_status)
VALUES
('00000000-0000-4000-8000-000000000010', 'Demo Super Admin', '900101145001', '0100000001', 'demo.superadmin@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'super_admin', 'active'),
('00000000-0000-4000-8000-000000000011', 'Demo Ketua KRT', '900101145002', '0100000002', 'demo.ketua.krt@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'ketua_krt', 'active'),
('00000000-0000-4000-8000-000000000012', 'Demo Ketua Peronda', '900101145003', '0100000003', 'demo.ketua.peronda@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'ketua_peronda', 'active'),
('00000000-0000-4000-8000-000000000013', 'Demo Peronda Satu', '900101145004', '0100000004', 'demo.peronda1@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'peronda', 'active'),
('00000000-0000-4000-8000-000000000014', 'Demo Peronda Dua', '900101145005', '0100000005', 'demo.peronda2@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'peronda', 'active'),
('00000000-0000-4000-8000-000000000015', 'Demo Peronda Tiga', '900101145006', '0100000006', 'demo.peronda3@srs.local', @demo_password_hash, 'Demo Zone A', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'peronda', 'active')
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name), phone_number = VALUES(phone_number), email = VALUES(email),
  password_hash = VALUES(password_hash), district_zone = VALUES(district_zone),
  state_id = VALUES(state_id), district_id = VALUES(district_id), zone_id = VALUES(zone_id),
  role = VALUES(role), account_status = VALUES(account_status);

INSERT INTO squads (id, name, state_id, district_id, zone_id) VALUES
('00000000-0000-4000-8000-000000000020', 'Demo Squad Alpha', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003')
ON DUPLICATE KEY UPDATE name = VALUES(name), state_id = VALUES(state_id), district_id = VALUES(district_id), zone_id = VALUES(zone_id);

INSERT INTO squad_members (squad_id, user_id, role) VALUES
('00000000-0000-4000-8000-000000000020', '00000000-0000-4000-8000-000000000012', 'leader'),
('00000000-0000-4000-8000-000000000020', '00000000-0000-4000-8000-000000000013', 'member'),
('00000000-0000-4000-8000-000000000020', '00000000-0000-4000-8000-000000000014', 'member'),
('00000000-0000-4000-8000-000000000020', '00000000-0000-4000-8000-000000000015', 'member')
ON DUPLICATE KEY UPDATE squad_id = VALUES(squad_id), role = VALUES(role);

INSERT INTO schedules (id, squad_id, shift_date, start_time, end_time) VALUES
('00000000-0000-4000-8000-000000000030', '00000000-0000-4000-8000-000000000020', CURDATE() + INTERVAL 1 DAY, '20:00:00', '23:59:00')
ON DUPLICATE KEY UPDATE squad_id = VALUES(squad_id), shift_date = VALUES(shift_date), start_time = VALUES(start_time), end_time = VALUES(end_time);

INSERT INTO equipment_inventory (id, item_name, total_quantity) VALUES
('00000000-0000-4000-8000-000000000040', 'Demo Torchlight', 8),
('00000000-0000-4000-8000-000000000041', 'Demo Walkie Talkie', 6),
('00000000-0000-4000-8000-000000000042', 'Demo Safety Vest', 10)
ON DUPLICATE KEY UPDATE item_name = VALUES(item_name), total_quantity = VALUES(total_quantity);

INSERT INTO attendance
(id, user_id, state_id, district_id, zone_id, schedule_id, checkpoint_id, latitude, longitude, clock_in_time, clock_out_time, clock_out_latitude, clock_out_longitude, return_status, return_requested_at, return_checkpoint_id, return_latitude, return_longitude, return_acknowledged_at, return_acknowledged_by)
VALUES
('00000000-0000-4000-8000-000000000050', '00000000-0000-4000-8000-000000000013', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', '00000000-0000-4000-8000-000000000030', 'DEMO_CLOCK_IN_COMPLETED', '2.2050', '102.2560', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 4 HOUR, '2.2051', '102.2561', 'completed', NOW() - INTERVAL 2 DAY + INTERVAL 4 HOUR, 'DEMO_CLOCK_OUT_COMPLETED', '2.2051', '102.2561', NOW() - INTERVAL 2 DAY + INTERVAL 4 HOUR + INTERVAL 5 MINUTE, '00000000-0000-4000-8000-000000000011'),
('00000000-0000-4000-8000-000000000051', '00000000-0000-4000-8000-000000000014', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', '00000000-0000-4000-8000-000000000030', 'DEMO_CLOCK_IN_ACTIVE', '2.2050', '102.2560', NOW() - INTERVAL 45 MINUTE, NULL, NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL),
('00000000-0000-4000-8000-000000000052', '00000000-0000-4000-8000-000000000015', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', '00000000-0000-4000-8000-000000000030', 'DEMO_CLOCK_IN_PENDING', '2.2050', '102.2560', NOW() - INTERVAL 3 HOUR, NULL, NULL, NULL, 'pending_acknowledgement', NOW() - INTERVAL 10 MINUTE, 'DEMO_CLOCK_OUT_PENDING', '2.2051', '102.2561', NULL, NULL)
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id), state_id = VALUES(state_id), district_id = VALUES(district_id), zone_id = VALUES(zone_id),
  schedule_id = VALUES(schedule_id), checkpoint_id = VALUES(checkpoint_id), latitude = VALUES(latitude), longitude = VALUES(longitude),
  clock_in_time = VALUES(clock_in_time), clock_out_time = VALUES(clock_out_time), clock_out_latitude = VALUES(clock_out_latitude),
  clock_out_longitude = VALUES(clock_out_longitude), return_status = VALUES(return_status), return_requested_at = VALUES(return_requested_at),
  return_checkpoint_id = VALUES(return_checkpoint_id), return_latitude = VALUES(return_latitude), return_longitude = VALUES(return_longitude),
  return_acknowledged_at = VALUES(return_acknowledged_at), return_acknowledged_by = VALUES(return_acknowledged_by);

INSERT INTO equipment_logs (id, attendance_id, equipment_id, borrowed_at, returned_at) VALUES
('00000000-0000-4000-8000-000000000060', '00000000-0000-4000-8000-000000000050', '00000000-0000-4000-8000-000000000040', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 4 HOUR + INTERVAL 5 MINUTE),
('00000000-0000-4000-8000-000000000061', '00000000-0000-4000-8000-000000000051', '00000000-0000-4000-8000-000000000041', NOW() - INTERVAL 45 MINUTE, NULL),
('00000000-0000-4000-8000-000000000062', '00000000-0000-4000-8000-000000000052', '00000000-0000-4000-8000-000000000042', NOW() - INTERVAL 3 HOUR, NULL)
ON DUPLICATE KEY UPDATE attendance_id = VALUES(attendance_id), equipment_id = VALUES(equipment_id), borrowed_at = VALUES(borrowed_at), returned_at = VALUES(returned_at);

INSERT INTO incidents
(id, user_id, state_id, district_id, zone_id, title, description, category, image_url, latitude, longitude, status, location_name, reported_to)
VALUES
('00000000-0000-4000-8000-000000000070', '00000000-0000-4000-8000-000000000013', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'Demo Lampu Jalan Rosak', 'Lampu jalan tidak menyala berhampiran simpang utama.', 'Infrastruktur', NULL, '2.2052', '102.2562', 'submitted', 'Demo Zone A', 'Ketua KRT'),
('00000000-0000-4000-8000-000000000071', '00000000-0000-4000-8000-000000000014', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'Demo Kenderaan Mencurigakan', 'Kenderaan tidak dikenali diparkir terlalu lama.', 'Keselamatan', NULL, '2.2053', '102.2563', 'submitted', 'Demo Zone A', 'Ketua KRT'),
('00000000-0000-4000-8000-000000000072', '00000000-0000-4000-8000-000000000015', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', 'Demo Pagar Terbuka', 'Pagar stor komuniti didapati terbuka semasa rondaan.', 'Rondaan', NULL, '2.2054', '102.2564', 'submitted', 'Demo Zone A', 'Ketua KRT')
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id), state_id = VALUES(state_id), district_id = VALUES(district_id), zone_id = VALUES(zone_id),
  title = VALUES(title), description = VALUES(description), category = VALUES(category), image_url = VALUES(image_url),
  latitude = VALUES(latitude), longitude = VALUES(longitude), status = VALUES(status), location_name = VALUES(location_name), reported_to = VALUES(reported_to);

INSERT INTO notifications
(id, user_id, actor_user_id, type, title, body, payload_json, is_read, read_at)
VALUES
('00000000-0000-4000-8000-000000000080', '00000000-0000-4000-8000-000000000011', '00000000-0000-4000-8000-000000000015', 'return_pending', 'Demo Pemulangan Menunggu', 'Demo Peronda Tiga menunggu pengesahan pemulangan peralatan.', '{"attendance_id":"00000000-0000-4000-8000-000000000052"}', 0, NULL)
ON DUPLICATE KEY UPDATE
  user_id = VALUES(user_id), actor_user_id = VALUES(actor_user_id), type = VALUES(type), title = VALUES(title),
  body = VALUES(body), payload_json = VALUES(payload_json), is_read = VALUES(is_read), read_at = VALUES(read_at);
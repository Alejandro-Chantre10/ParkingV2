-- =============================================
-- DATOS INICIALES - Parking The Beasts
-- Fixed: BIKE → BICI para coincidir con schema.sql
-- =============================================

-- 1. ROLES
INSERT INTO roles (id, code, name) VALUES
(1, 'ADMIN', 'Administrador'),
(2, 'USER',  'Usuario')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 2. TIPOS DE VEHÍCULOS
-- Fixed: code debe ser 'BICI' no 'BIKE' (coincide con schema.sql)
INSERT INTO vehicle_types (id, code, name) VALUES
(1, 'CAR',  'Carro'),
(2, 'MOTO', 'Moto'),
(3, 'BICI', 'Bicicleta')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. INSTALACIONES
INSERT INTO facilities (id, name, address, is_active, created_at) VALUES
(1, 'Parking The Beasts - Sede Principal', 'Calle 100 #15-20, Bogotá', 1, NOW()),
(2, 'Parking The Beasts - Sede Norte',     'Carrera 7 #120-45, Bogotá', 1, NOW()),
(3, 'Parking The Beasts - Sede Centro',    'Avenida Jiménez #5-30, Bogotá', 1, NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 4. CAPACIDAD DE PARQUEO
INSERT INTO parking_capacity (facility_id, vehicle_type_id, capacity) VALUES
-- Sede Principal
(1, 1, 50), (1, 2, 30), (1, 3, 20),
-- Sede Norte
(2, 1, 40), (2, 2, 25), (2, 3, 15),
-- Sede Centro
(3, 1, 30), (3, 2, 20), (3, 3, 10)
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

-- 5. TARIFAS
INSERT INTO rates (facility_id, vehicle_type_id, price_per_hour, min_minutes, rounding_minutes, grace_minutes, is_active, created_at) VALUES
-- Sede Principal
(1, 1, 5000.00, 60, 60, 10, 1, NOW()),
(1, 2, 3000.00, 60, 60, 10, 1, NOW()),
(1, 3, 1500.00, 60, 60, 15, 1, NOW()),
-- Sede Norte
(2, 1, 4500.00, 60, 60, 10, 1, NOW()),
(2, 2, 2500.00, 60, 60, 10, 1, NOW()),
(2, 3, 1200.00, 60, 60, 15, 1, NOW()),
-- Sede Centro
(3, 1, 6000.00, 60, 60, 10, 1, NOW()),
(3, 2, 3500.00, 60, 60, 10, 1, NOW()),
(3, 3, 2000.00, 60, 60, 15, 1, NOW())
ON DUPLICATE KEY UPDATE price_per_hour = VALUES(price_per_hour);

-- 6. USUARIO ADMINISTRADOR
-- Password: admin123 (bcrypt hash)
INSERT INTO users (id, role_id, full_name, email, phone, password_hash, is_active, created_at, updated_at) VALUES
(1, 1, 'Administrador', 'admin@parking.com', '3001234567',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- VERIFICACIÓN
SELECT 'Datos insertados correctamente' AS mensaje;
SELECT COUNT(*) AS total_roles           FROM roles;
SELECT COUNT(*) AS total_vehicle_types   FROM vehicle_types;
SELECT COUNT(*) AS total_facilities      FROM facilities;
SELECT COUNT(*) AS total_parking_capacity FROM parking_capacity;
SELECT COUNT(*) AS total_rates           FROM rates;

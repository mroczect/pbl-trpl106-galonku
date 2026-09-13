USE galonku_db;

-- Roles
INSERT INTO roles (name, description) VALUES
('admin', 'Administrator sistem'),
('kurir', 'Kurir pengantaran'),
('pelanggan', 'Pelanggan depot air')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Users (password: admin123, kurir123, pelanggan123)
INSERT INTO users (role_id, name, email, phone, password_hash, is_active) VALUES
(1, 'Admin Galonku', 'admin@galonku.com', '081234567890', '$2y$12$aUSIExzDjZ5MmAGkpeGemOJoOTMMrlbJ4Ii2BoomR1nprF26rsvF.', 1),
(2, 'Kurir Demo',    'kurir@galonku.com', '081234567891', '$2y$12$3fVohVvR0zOX/rTSgQPNo.4TinjVu/n.rfIJM0hhCeVkcL5SsCQMy', 1),
(3, 'Pelanggan Demo','user@galonku.com',  '081234567892', '$2y$12$P/jJJCaaMNIfvAsUUvtwq.0wT.MvaUjYz79sgZQKAJBZ7Q4tLe04i', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash);

-- Products
INSERT INTO products (sku, name, category, price, stock, is_active) VALUES
('GLN-AQUA-19L',   'Galon Aqua 19L',      'galon',    20000.00,  50,  1),
('GLN-RO-19L',     'Galon Isi Ulang 19L', 'galon',    6000.00,   100, 1),
('GAS-LPG-3KG',    'Gas LPG 3kg',         'lain',     25000.00,  30,  1),
('GAS-LPG-12KG',   'Gas LPG 12kg',        'lain',     180000.00, 15,  1),
('AKS-TUTUP-GLN',  'Tutup Galon',         'aksesoris',5000.00,   200, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

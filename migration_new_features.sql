/*
 * NEW FEATURES MIGRATION
 * Adds support for:
 * - Multiple admins
 * - In-app notifications
 * - Company contact directory
 *
 * Run this after setup.sql
 */

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COMPANY CONTACTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS company_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_is_active (is_active),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE CONTACTS (OPTIONAL)
-- ============================================================
INSERT INTO company_contacts (name, role, phone, email, created_by)
VALUES
    ('HR Department', 'Human Resources', '+91-9999999990', 'hr@company.com', 1),
    ('IT Support', 'Technical Support', '+91-9999999991', 'it@company.com', 1),
    ('Finance Team', 'Accounts & Finance', '+91-9999999992', 'finance@company.com', 1)
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- END OF MIGRATION
-- ============================================================

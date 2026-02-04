/*
 * DATABASE SETUP SCRIPT
 * Run this script to initialize the database
 * Change "attendance_system" to your database name if different
 */

-- Create Database


-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    pin VARCHAR(4) NOT NULL,
    role ENUM('admin', 'employee', 'intern') NOT NULL DEFAULT 'employee',
    monthly_salary DECIMAL(10, 2) DEFAULT 0,
    stipend_amount DECIMAL(10, 2) DEFAULT 0,
    stipend_type ENUM('hourly', 'fixed') DEFAULT 'fixed',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ATTENDANCE TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    slot ENUM('office', 'wfh', 'site_visit') NOT NULL,
    punch_in_time TIMESTAMP NOT NULL,
    punch_in_latitude DECIMAL(10, 8),
    punch_in_longitude DECIMAL(11, 8),
    punch_in_photo VARCHAR(255),
    punch_out_time TIMESTAMP NULL,
    punch_out_latitude DECIMAL(10, 8),
    punch_out_longitude DECIMAL(11, 8),
    punch_out_photo VARCHAR(255),
    site_visit_time TIMESTAMP NULL,
    site_visit_latitude DECIMAL(10, 8),
    site_visit_longitude DECIMAL(11, 8),
    site_visit_photo VARCHAR(255),
    work_summary TEXT,
    day_count DECIMAL(2, 1) DEFAULT 0,
    is_auto_punch_out BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_punch_in_date (punch_in_time),
    INDEX idx_day_count (day_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- LEAVES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    leave_type ENUM('sick', 'casual') NOT NULL,
    leave_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reason TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_leave_date (leave_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_leave_per_day (user_id, leave_date, leave_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- EXPENSES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    receipt_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_expense_date (expense_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PAYROLL TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    base_salary DECIMAL(10, 2) DEFAULT 0,
    attendance_salary DECIMAL(10, 2) DEFAULT 0,
    leave_salary DECIMAL(10, 2) DEFAULT 0,
    expense_allowance DECIMAL(10, 2) DEFAULT 0,
    incentives DECIMAL(10, 2) DEFAULT 0,
    gross_salary DECIMAL(10, 2) DEFAULT 0,
    net_salary DECIMAL(10, 2) DEFAULT 0,
    deposited_amount DECIMAL(10, 2) DEFAULT 0,
    status ENUM('draft', 'approved', 'paid') NOT NULL DEFAULT 'draft',
    notes TEXT,
    created_by INT,
    paid_on TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_year_month (year, month),
    INDEX idx_status (status),
    UNIQUE KEY unique_payroll (user_id, year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    related_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA (OPTIONAL)
-- ============================================================

-- Admin User
INSERT INTO users (name, email, phone, pin, role, monthly_salary)
VALUES ('Admin User', 'admin@company.com', '9999999999', '1234', 'admin', 100000)
ON DUPLICATE KEY UPDATE id=id;

-- Sample Employee
INSERT INTO users (name, email, phone, pin, role, monthly_salary)
VALUES ('John Employee', 'john@company.com', '9888888888', '1111', 'employee', 50000)
ON DUPLICATE KEY UPDATE id=id;

-- Sample Intern
INSERT INTO users (name, email, phone, pin, role, stipend_amount, stipend_type)
VALUES ('Intern User', 'intern@company.com', '9777777777', '2222', 'intern', 200, 'hourly')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- END OF SETUP
-- ============================================================

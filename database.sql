-- ============================================================
-- Multi-User Employee Management System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS emp_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE emp_management;

-- ============================================================
-- Table: users
-- Stores all system users (admin, manager, employee)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','manager','employee') NOT NULL DEFAULT 'employee',
    avatar      VARCHAR(255) DEFAULT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table: employees
-- Extended profile for users with role = 'employee' or 'manager'
-- ============================================================
CREATE TABLE IF NOT EXISTS employees (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    manager_id  INT UNSIGNED DEFAULT NULL,   -- references users.id of the manager
    position    VARCHAR(100) NOT NULL,
    department  VARCHAR(100) DEFAULT NULL,
    salary      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    phone       VARCHAR(30) DEFAULT NULL,
    address     TEXT DEFAULT NULL,
    hire_date   DATE DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_user    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_emp_manager FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: activity_logs
-- Tracks every significant action in the system
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(255) NOT NULL,
    detail      TEXT DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data
-- Default admin account  (password: Admin@1234)
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
('Super Admin', 'admin@ems.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Two sample managers  (password: Manager@1 / Manager@2)
INSERT INTO users (name, email, password, role) VALUES
('Alice Manager',  'alice@ems.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Bob Manager',    'bob@ems.local',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');

-- Four sample employees  (password: same hash = "password")
INSERT INTO users (name, email, password, role) VALUES
('Carol Smith',   'carol@ems.local',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee'),
('Dave Jones',    'dave@ems.local',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee'),
('Eve Taylor',    'eve@ems.local',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee'),
('Frank Wilson',  'frank@ems.local',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');

-- Manager employee records
INSERT INTO employees (user_id, manager_id, position, department, salary, phone, hire_date) VALUES
(2, NULL, 'Engineering Manager',  'Engineering', 95000.00, '555-0101', '2020-01-15'),
(3, NULL, 'Marketing Manager',    'Marketing',   90000.00, '555-0102', '2020-03-20');

-- Employee records (alice manages carol & dave; bob manages eve & frank)
INSERT INTO employees (user_id, manager_id, position, department, salary, phone, hire_date) VALUES
(4, 2, 'Software Engineer',   'Engineering', 75000.00, '555-0201', '2021-06-01'),
(5, 2, 'QA Engineer',         'Engineering', 70000.00, '555-0202', '2021-08-15'),
(6, 3, 'Marketing Analyst',   'Marketing',   65000.00, '555-0301', '2022-02-01'),
(7, 3, 'Content Strategist',  'Marketing',   68000.00, '555-0302', '2022-04-10');

-- NOTE: All seed passwords hash to the string "password"
-- Change them immediately on first login in production.

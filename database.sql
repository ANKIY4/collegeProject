-- Personal Budget and Finance Manager Database Schema
-- Database for Nepal with Authentication

CREATE DATABASE IF NOT EXISTS budget_manager;
USE budget_manager;

-- Drop existing tables to start fresh
DROP TABLE IF EXISTS tax_records;
DROP TABLE IF EXISTS savings_goals;
DROP TABLE IF EXISTS budget_categories;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS users;

-- Users table with authentication fields
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    failed_login_attempts INT DEFAULT 0,
    account_locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_remember_token (remember_token)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Income table
CREATE TABLE IF NOT EXISTS income (
    income_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    bonuses DECIMAL(10,2) DEFAULT 0.00,
    other_income DECIMAL(10,2) DEFAULT 0.00,
    total_income DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, month)
);

-- Budget categories table
CREATE TABLE IF NOT EXISTS budget_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(7) NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    allocated_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month_category (user_id, month, category_name)
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, expense_date)
);

-- Savings goals table
CREATE TABLE IF NOT EXISTS savings_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    target_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Tax records table (for storing calculated taxes)
CREATE TABLE IF NOT EXISTS tax_records (
    tax_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(7) NOT NULL,
    taxable_income DECIMAL(10,2) NOT NULL,
    income_tax DECIMAL(10,2) NOT NULL,
    ssf_contribution DECIMAL(10,2) DEFAULT 0.00,
    total_tax DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- No default user inserted - users must sign up


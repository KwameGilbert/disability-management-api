-- Create database
CREATE DATABASE pwd_management;
USE pwd_management;

-- Table: users
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role ENUM('admin','officer') NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    profile_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: password_resets
CREATE TABLE password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table: communities
CREATE TABLE communities (
    community_id INT PRIMARY KEY AUTO_INCREMENT,
    community_name VARCHAR(150) UNIQUE NOT NULL
);

-- Table: disability_categories
CREATE TABLE disability_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL
);

-- Table: disability_types
CREATE TABLE disability_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    type_name VARCHAR(100) UNIQUE NOT NULL,
    FOREIGN KEY (category_id) REFERENCES disability_categories(category_id)
);

-- Table: assistance_types
CREATE TABLE assistance_types (
    assistance_type_id INT PRIMARY KEY AUTO_INCREMENT,
    assistance_type_name VARCHAR(100) UNIQUE NOT NULL
);

-- Table: genders
CREATE TABLE genders (
    gender_id INT PRIMARY KEY AUTO_INCREMENT,
    gender_name VARCHAR(20) UNIQUE NOT NULL
);

-- Table: pwd_records (all PWD info in one table)
CREATE TABLE pwd_records (
    pwd_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL, 
    quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,
    year YEAR,
    gender_id INT NOT NULL, -- references gender table
    full_name VARCHAR(150) NOT NULL,
    occupation VARCHAR(100),
    contact VARCHAR(20),
    dob DATE,
    age INT,
    disability_category_id INT NOT NULL,
    disability_type_id INT NOT NULL,
    gh_card_number VARCHAR(50),
    nhis_number VARCHAR(50),
    community_id INT NOT NULL,
    guardian_name VARCHAR(150),
    guardian_occupation VARCHAR(100),
    guardian_phone VARCHAR(20),
    guardian_relationship VARCHAR(50),
    education_level VARCHAR(100),
    school_name VARCHAR(150),
    assistance_type_needed_id INT,
    support_needs TEXT,
    supporting_documents JSON, -- stores multiple file names
    status ENUM('pending','approved','declined') DEFAULT 'pending',
    profile_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (gender_id) REFERENCES genders(gender_id),
    FOREIGN KEY (disability_category_id) REFERENCES disability_categories(category_id),
    FOREIGN KEY (disability_type_id) REFERENCES disability_types(type_id),
    FOREIGN KEY (community_id) REFERENCES communities(community_id),
    FOREIGN KEY (assistance_type_needed_id) REFERENCES assistance_types(assistance_type_id)
);

-- Table: assistance_requests (tracking assistance process)
CREATE TABLE assistance_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    assistance_type_id INT NOT NULL,
    beneficiary_id INT NOT NULL, -- PWD receiving assistance
    requested_by INT NOT NULL, -- officer/admin logging request
    description TEXT,
    amount_value_cost DECIMAL(10,2),
    admin_review_notes TEXT,
    status ENUM('pending','review','ready_to_access','assessed','declined') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assistance_type_id) REFERENCES assistance_types(assistance_type_id),
    FOREIGN KEY (beneficiary_id) REFERENCES pwd_records(pwd_id),
    FOREIGN KEY (requested_by) REFERENCES users(user_id)
);

-- Table: activity_logs
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- View: quarterly_statistics
CREATE VIEW quarterly_statistics AS
SELECT 
    CONCAT(quarter, '-', year) AS period_id,
    quarter,
    year,
    COUNT(*) AS total_registered_pwd,
    SUM(CASE WHEN pr.pwd_id IN (
        SELECT DISTINCT beneficiary_id FROM assistance_requests 
        WHERE status = 'assessed'
    ) THEN 1 ELSE 0 END) AS total_assessed,
    SUM(CASE WHEN pr.pwd_id IN (
        SELECT DISTINCT beneficiary_id FROM assistance_requests 
        WHERE status = 'pending'
    ) THEN 1 ELSE 0 END) AS pending
FROM 
    pwd_records pr
GROUP BY 
    quarter, year;

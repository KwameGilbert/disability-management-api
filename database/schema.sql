-- Create database
CREATE DATABASE pwd_management;
USE pwd_management;

-- Table: roles
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

-- Table: users
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    profile_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
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

-- Table: pwd_records
CREATE TABLE pwd_records (
    pwd_id INT PRIMARY KEY AUTO_INCREMENT,
    officer_id INT NOT NULL,
    quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    occupation VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    age INT NOT NULL,
    disability_category VARCHAR(100) NOT NULL,
    disability_type VARCHAR(100) NOT NULL,
    gh_card_number VARCHAR(50) NOT NULL,
    nhis_number VARCHAR(50),
    community_id INT NOT NULL,
    status ENUM('pending','approved','disapproved') DEFAULT 'pending',
    profile_image VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (officer_id) REFERENCES users(user_id),
    FOREIGN KEY (community_id) REFERENCES communities(community_id)
);

-- Table: pwd_guardians
CREATE TABLE pwd_guardians (
    guardian_id INT PRIMARY KEY AUTO_INCREMENT,
    pwd_id INT NOT NULL,
    name VARCHAR(150),
    occupation VARCHAR(100),
    phone VARCHAR(20),
    relationship VARCHAR(50),
    FOREIGN KEY (pwd_id) REFERENCES pwd_records(pwd_id)
);

-- Table: pwd_education
CREATE TABLE pwd_education (
    education_id INT PRIMARY KEY AUTO_INCREMENT,
    pwd_id INT NOT NULL,
    education_level VARCHAR(100),
    school_name VARCHAR(150),
    FOREIGN KEY (pwd_id) REFERENCES pwd_records(pwd_id)
);

-- Table: pwd_support_needs
CREATE TABLE pwd_support_needs (
    need_id INT PRIMARY KEY AUTO_INCREMENT,
    pwd_id INT NOT NULL,
    assistance_needed TEXT,
    FOREIGN KEY (pwd_id) REFERENCES pwd_records(pwd_id)
);

-- Table: supporting_documents
CREATE TABLE supporting_documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    related_type ENUM('pwd','assistance') NOT NULL,
    related_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table: assistance
CREATE TABLE assistance (
    assistance_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    assistance_type VARCHAR(100) NOT NULL,
    date_of_support DATE NOT NULL,
    beneficiary_id INT NOT NULL,
    pre_assessment BOOLEAN DEFAULT 0,
    status ENUM('pending','approved','disapproved') DEFAULT 'pending',
    assessment_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id),
    FOREIGN KEY (beneficiary_id) REFERENCES pwd_records(pwd_id)
);

-- Table: activity_logs
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Table: quarterly_statistics
CREATE TABLE quarterly_statistics (
    stat_id INT PRIMARY KEY AUTO_INCREMENT,
    quarter ENUM('Q1','Q2','Q3','Q4') NOT NULL,
    year YEAR NOT NULL,
    total_registered_pwd INT NOT NULL,
    total_assessed INT NOT NULL,
    pending INT NOT NULL
);

-- Table: assistance_distribution
CREATE TABLE assistance_distribution (
    dist_id INT PRIMARY KEY AUTO_INCREMENT,
    assistance_type VARCHAR(100) NOT NULL,
    count INT NOT NULL
);

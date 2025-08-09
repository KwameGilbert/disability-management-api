
-- 1. Roles & User

CREATE TABLE `users` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`full_name` VARCHAR(100) NOT NULL,
`email` VARCHAR(100) NOT NULL UNIQUE,
`phone` VARCHAR(20) NOT NULL,
`password_hash` VARCHAR(255) NOT NULL,
`role` ENUM('client','finance','collector','it','rcd','mce') NOT NULL DEFAULT 'client',
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Businesses (Clients)

CREATE TABLE `businesses` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`name` VARCHAR(150) NOT NULL,
`registration_id` VARCHAR(50) NOT NULL UNIQUE,
`owner_user_id` INT UNSIGNED NOT NULL,
`address` VARCHAR(255),
`phone` VARCHAR(20),
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`owner_user_id`),
CONSTRAINT `fk_business_owner` FOREIGN KEY (`owner_user_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tax Types (Configured by Finance)

CREATE TABLE `tax_types` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`name` VARCHAR(100) NOT NULL,
`description` VARCHAR(255) NOT NULL,
`amount` DECIMAL(10,2) NOT NULL COMMENT 'Base amount per frequency unit',
`frequency` ENUM('daily','weekly','monthly','quarterly','annually') NOT NULL,
`created_by` INT UNSIGNED NOT NULL COMMENT 'Finance user who created this tax',
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`created_by`),
CONSTRAINT `fk_tax_created_by` FOREIGN KEY (`created_by`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Business–Tax Assignments & Rules

CREATE TABLE `business_taxes` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`business_id` INT UNSIGNED NOT NULL,
`tax_type_id` INT UNSIGNED NOT NULL,
`active` TINYINT(1) NOT NULL DEFAULT 1,
`start_date` DATE NOT NULL,
`end_date` DATE NULL,
PRIMARY KEY (`id`),
INDEX (`business_id`),
INDEX (`tax_type_id`),
CONSTRAINT `fk_bt_business` FOREIGN KEY (`business_id`)
REFERENCES `businesses` (`id`) ON DELETE CASCADE,
CONSTRAINT `fk_bt_taxtype` FOREIGN KEY (`tax_type_id`)
REFERENCES `tax_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Payments (Collected by Collectors)

CREATE TABLE `payments` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`business_tax_id` INT UNSIGNED NOT NULL COMMENT 'Links to business_taxes',
`collector_id` INT UNSIGNED NOT NULL COMMENT 'User.id of collector',
`periods_paid` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Number of frequency units',
`amount_paid` DECIMAL(12,2) NOT NULL,
`payment_method` ENUM('momo','card','ussd') NOT NULL,
`paystack_ref` VARCHAR(100) NULL,
`paid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`business_tax_id`),
INDEX (`collector_id`),
CONSTRAINT `fk_pay_bt` FOREIGN KEY (`business_tax_id`)
REFERENCES `business_taxes` (`id`) ON DELETE CASCADE,
CONSTRAINT `fk_pay_collector` FOREIGN KEY (`collector_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Notifications (Email/SMS Logs)

CREATE TABLE `notifications` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` INT UNSIGNED NOT NULL,
`type` ENUM('sms','email') NOT NULL,
`content` TEXT NOT NULL,
`sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`read_at` DATETIME NULL,
PRIMARY KEY (`id`),
INDEX (`user_id`),
CONSTRAINT `fk_note_user` FOREIGN KEY (`user_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Audit Logs (Immutable Action Tracking)

CREATE TABLE `audit_logs` (
`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` INT UNSIGNED NOT NULL,
`action` VARCHAR(100) NOT NULL,
`details` TEXT,
`logged_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`user_id`),
CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

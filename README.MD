# Disability Management System API

A comprehensive REST API for managing information about persons with disabilities (PWD), their personal data, assistance requests, and support services. The system facilitates efficient tracking, processing, and reporting of disability-related services with multi-role access for case workers, administrators, and service providers.

## 🌟 Features

- **User Authentication & Authorization**
    - JWT-based authentication
    - Role-based access control (admin/officer)
    - Password reset via OTP

- **PWD Records Management**
    - Complete personal information storage
    - Document management (supporting documents)
    - Status tracking (pending/approved/declined)

- **Community Management**
    - Register and organize communities
    - Associate PWD with specific communities

- **Assistance Management**
    - Track different types of assistance
    - Process assistance requests
    - Monitor assistance delivery

- **Comprehensive Statistics**
    - Generate quarterly reports
    - Track assessment status

- **Auditing and Monitoring**
    - Activity logs
    - Request/Response logging
    - System logs viewer

## 🛠️ Technology Stack

- **Backend:** PHP 8.4
- **Framework:** Slim 4
- **Database:** MySQL
- **Authentication:** JWT (Firebase PHP-JWT)
- **Email Service:** PHPMailer
- **Logging:** Monolog
- **Validation:** Respect Validation
- **Dependency Injection:** PHP-DI
- **Environment Variables:** PHPDotEnv
- **Containerization:** Docker

## 📋 API Endpoints

### Authentication
- `POST /v1/users/login` - User login
- `POST /v1/users/password/reset-request` - Request password reset
- `POST /v1/users/password/verify-otp` - Verify OTP
- `POST /v1/users/password/reset` - Reset password with OTP
- `POST /v1/users/password/update` - Update password

### Users
- `GET /v1/users` - List all users
- `GET /v1/users/{id}` - Get user by ID
- `POST /v1/users` - Create new user
- `PATCH /v1/users/{id}` - Update user
- `DELETE /v1/users/{id}` - Delete user

### PWD Records
- `GET /v1/pwd-records` - List PWD records
- `GET /v1/pwd-records/{id}` - Get PWD record by ID
- `POST /v1/pwd-records` - Create PWD record
- `PATCH /v1/pwd-records/{id}` - Update PWD record
- `PATCH /v1/pwd-records/{id}/status` - Update PWD record status
- `DELETE /v1/pwd-records/{id}` - Delete PWD record

### Communities
- `GET /v1/communities` - List communities
- `GET /v1/communities/{id}` - Get community by ID
- `POST /v1/communities` - Create community
- `PATCH /v1/communities/{id}` - Update community
- `DELETE /v1/communities/{id}` - Delete community

### Assistance Types
- `GET /v1/assistance-types` - List assistance types
- `GET /v1/assistance-types/{id}` - Get assistance type by ID
- `POST /v1/assistance-types` - Create assistance type
- `PATCH /v1/assistance-types/{id}` - Update assistance type
- `DELETE /v1/assistance-types/{id}` - Delete assistance type

### Assistance Requests
- `GET /v1/assistance-requests` - List assistance requests
- `GET /v1/assistance-requests/{id}` - Get assistance request by ID
- `POST /v1/assistance-requests` - Create assistance request
- `PATCH /v1/assistance-requests/{id}` - Update assistance request
- `PATCH /v1/assistance-requests/{id}/status` - Update request status
- `DELETE /v1/assistance-requests/{id}` - Delete assistance request

### Statistics
- `GET /v1/statistics/quarterly` - Get quarterly statistics
- `GET /v1/statistics/quarterly/{year}` - Get statistics for specific year
- `GET /v1/statistics/quarterly/{year}/{quarter}` - Get statistics for specific quarter

### Logs
- `GET /v1/activity-logs` - View activity logs
- `GET /logs-viewer` - View system logs (admin only)

## 🚀 Installation and Setup

### Prerequisites
- PHP 8.4+
- MySQL 5.7+
- Composer

### Local Development
1. Clone the repository:
     ```bash
     git clone https://github.com/yourusername/disability-management-api.git
     cd disability-management-api
     ```

2. Install dependencies:
     ```bash
     composer install
     ```

3. Create environment file:
     ```bash
     cp .env.example .env
     ```

4. Configure your environment variables in .env
     ```
     # App Configuration
     APP_NAME="Disability Management"
     APP_ENV=development|production
     APP_DEBUG=true|false
     APP_URL=http://localhost:8080

     # Database Configuration
     LOCAL_DB_ADAPTER=mysql
     LOCAL_DB_HOST=127.0.0.1
     LOCAL_DB_PORT=3306
     LOCAL_DB_DATABASE=pwd_management
     LOCAL_DB_USERNAME=root
     LOCAL_DB_PASSWORD=your_password

     # Email Configuration
     MAIL_MAILER=smtp
     MAIL_HOST=smtp.example.com
     MAIL_PORT=587
     MAIL_USERNAME=your_username
     MAIL_PASSWORD=your_password
     MAIL_ENCRYPTION=tls
     MAIL_FROM_ADDRESS=noreply@example.com
     MAIL_FROM_NAME="PWD Management System"
     ```

5. Set up the database:
     ```bash
     php db/migrate.php
     ```

6. Start the development server:
     ```bash
     php -S localhost:8080 -t public
     ```

### Docker Deployment
Build and run using Docker:
```bash
docker-compose up -d
```

## ⚙️ Configuration
Configuration is managed through environment variables in the .env file.

## 📝 Database Schema
The system uses multiple interconnected tables:

- users - System users with role-based access
- pwd_records - Main PWD information records
- communities - Community definitions
- disability_categories - Categories of disabilities
- disability_types - Specific disability types
- assistance_types - Types of assistance provided
- assistance_requests - Requests for assistance
- activity_logs - System activity audit trail
- password_resets - OTP management for password resets

## 🤝 Contributing
1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Authors
Gilbert Elikplim Kukah - kwamegilbert1114@gmail.com

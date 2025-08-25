# Disability Management API Documentation

This document provides detailed information about the available API endpoints for the Disability Management system. The API follows RESTful principles and returns JSON responses. The base URL for all endpoints is `/v1/`.

## Table of Contents
- [Authentication](#authentication)
- [Users API](#users-api)
- [Communities API](#communities-api)
- [Disability Categories API](#disability-categories-api)
- [Disability Types API](#disability-types-api)
- [Assistance Types API](#assistance-types-api)
- [PWD Records API](#pwd-records-api)
- [Assistance Requests API](#assistance-requests-api)
- [Quarterly Statistics API](#quarterly-statistics-api)

## Authentication

Most endpoints require authentication. Use the login endpoint to authenticate a user with their username/email and password. Upon successful login, you'll receive the user information. Your application should store this information (such as user_id and role) in a session or local storage to maintain authentication state.

```
// No Authorization header needed as authentication is managed through session handling
```

---

## Users API

### Get All Users

Retrieves the profile of the authenticated user.

**URL:** `/v1/users`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "user_id": 1,
    "username": "admin_user",
    "email": "admin@example.com",
    "role": "admin",
    "profile_image": "path/to/image.jpg",
    "created_at": "2025-01-01 00:00:00"
  }
}
```

### Get User by ID

Retrieves a specific user by their ID.

**URL:** `/v1/users/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - User ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "user_id": 1,
    "username": "admin_user",
    "email": "admin@example.com",
    "role": "admin",
    "profile_image": "path/to/image.jpg",
    "created_at": "2025-01-01 00:00:00"
  }
}
```

### Get User by Email

Retrieves a specific user by their email address.

**URL:** `/v1/users/email/{email}`

**Method:** `GET`

**URL Parameters:**
- `email` - User email (string)

**Authentication Required:** Yes

**Response:** Same as "Get User by ID"

### Get User by Username

Retrieves a specific user by their username.

**URL:** `/v1/users/username/{username}`

**Method:** `GET`

**URL Parameters:**
- `username` - Username (string)

**Authentication Required:** Yes

**Response:** Same as "Get User by ID"

### Create a New User

Creates a new user in the system.

**URL:** `/v1/users`

**Method:** `POST`

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "role": "officer",  // "admin" or "officer"
  "username": "new_officer",
  "email": "officer@example.com",
  "password": "securepassword123",
  "profile_image": "path/to/image.jpg"  // Optional
}
```

**Response:**

```json
{
  "status": "success",
  "message": "User created successfully",
  "data": {
    "user_id": 2,
    "username": "new_officer",
    "email": "officer@example.com",
    "role": "officer",
    "created_at": "2025-08-11 12:30:45"
  }
}
```

### Update User

Updates an existing user's information.

**URL:** `/v1/users/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - User ID (integer)

**Authentication Required:** Yes (Own account or Admin)

**Request Body:**

```json
{
  "username": "updated_username",  // Optional
  "email": "updated_email@example.com",  // Optional
  "role": "admin",  // Optional, Admin only
  "profile_image": "path/to/new_image.jpg"  // Optional
}
```

**Response:**

```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "user_id": 2,
    "username": "updated_username",
    "email": "updated_email@example.com",
    "role": "admin"
  }
}
```

### Delete User

Deletes a user from the system.

**URL:** `/v1/users/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - User ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

### User Login

Authenticates a user and returns user information.

**URL:** `/v1/users/login`

**Method:** `POST`

**Request Body:**

```json
{
  "username": "admin_user",  // Can use email instead
  "password": "securepassword123"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "username": "admin_user",
    "email": "admin@example.com",
    "role": "admin",
    "profile_image": "path/to/image.jpg",
    "created_at": "2025-01-01 00:00:00",
    "updated_at": "2025-01-01 00:00:00"
  }
}
```

### Request Password Reset

Sends a one-time password (OTP) to the user's email for password reset.

**URL:** `/v1/users/password/request-reset`

**Method:** `POST`

**Request Body:**

```json
{
  "email": "user@example.com",
  "ttl_minutes": 15  // Optional, default is 15 minutes
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Password reset OTP has been sent to your email"
}
```

### Verify OTP

Verifies a one-time password for password reset.

**URL:** `/v1/users/password/verify-otp`

**Method:** `POST`

**Request Body:**

```json
{
  "otp": "123456"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "OTP verified successfully"
}
```

### Reset Password with OTP

Resets a user's password using a valid OTP.

**URL:** `/v1/users/password/reset`

**Method:** `POST`

**Request Body:**

```json
{
  "otp": "123456",
  "new_password": "newSecurePassword123"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Password reset successfully"
}
```

### Update Password

Updates a user's password with current password confirmation.

**URL:** `/v1/users/password/update`

**Method:** `POST`

**Authentication Required:** Yes

**Request Body:**

```json
{
  "user_id": 1,
  "current_password": "currentPassword123",
  "new_password": "newSecurePassword123"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Password updated successfully"
}
```

---

## Communities API

### List All Communities

Retrieves all communities in the system.

**URL:** `/v1/communities`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "community_id": 1,
      "community_name": "Adenta"
    },
    {
      "community_id": 2,
      "community_name": "East Legon"
    }
  ]
}
```

### Get Community by ID

Retrieves a specific community by its ID.

**URL:** `/v1/communities/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - Community ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "community_id": 1,
    "community_name": "Adenta"
  }
}
```

### Create Community

Creates a new community.

**URL:** `/v1/communities`

**Method:** `POST`

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "community_name": "Madina"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Community created successfully",
  "data": {
    "community_id": 3,
    "community_name": "Madina"
  }
}
```

### Update Community

Updates an existing community.

**URL:** `/v1/communities/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Community ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "community_name": "Updated Madina"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Community updated successfully",
  "data": {
    "community_id": 3,
    "community_name": "Updated Madina"
  }
}
```

### Delete Community

Deletes a community from the system.

**URL:** `/v1/communities/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - Community ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "Community deleted successfully"
}
```

---

## Disability Categories API

### List All Disability Categories

Retrieves all disability categories in the system.

**URL:** `/v1/disability-categories`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "category_id": 1,
      "category_name": "Visual Impairment"
    },
    {
      "category_id": 2,
      "category_name": "Hearing Impairment"
    }
  ]
}
```

### Get Disability Category by ID

Retrieves a specific disability category by its ID.

**URL:** `/v1/disability-categories/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - Category ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "category_id": 1,
    "category_name": "Visual Impairment"
  }
}
```

### Get Disability Types for a Category

Retrieves all disability types associated with a specific category.

**URL:** `/v1/disability-categories/{id}/types`

**Method:** `GET`

**URL Parameters:**
- `id` - Category ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "type_id": 1,
      "category_id": 1,
      "type_name": "Blindness"
    },
    {
      "type_id": 2,
      "category_id": 1,
      "type_name": "Low Vision"
    }
  ]
}
```

### Create Disability Category

Creates a new disability category.

**URL:** `/v1/disability-categories`

**Method:** `POST`

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "category_name": "Physical Disability"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Category created successfully",
  "data": {
    "category_id": 3,
    "category_name": "Physical Disability"
  }
}
```

### Update Disability Category

Updates an existing disability category.

**URL:** `/v1/disability-categories/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Category ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "category_name": "Physical Impairment"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Category updated successfully",
  "data": {
    "category_id": 3,
    "category_name": "Physical Impairment"
  }
}
```

### Delete Disability Category

Deletes a disability category from the system.

**URL:** `/v1/disability-categories/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - Category ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "Category deleted successfully"
}
```

---

## Disability Types API

### List All Disability Types

Retrieves all disability types in the system.

**URL:** `/v1/disability-types`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "type_id": 1,
      "category_id": 1,
      "category_name": "Visual Impairment",
      "type_name": "Blindness"
    },
    {
      "type_id": 2,
      "category_id": 1,
      "category_name": "Visual Impairment",
      "type_name": "Low Vision"
    }
  ]
}
```

### Get Disability Types by Category

Retrieves all disability types for a specific category.

**URL:** `/v1/disability-types/category/{categoryId}`

**Method:** `GET`

**URL Parameters:**
- `categoryId` - Category ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "type_id": 1,
      "category_id": 1,
      "type_name": "Blindness"
    },
    {
      "type_id": 2,
      "category_id": 1,
      "type_name": "Low Vision"
    }
  ]
}
```

### Get Disability Type by ID

Retrieves a specific disability type by its ID.

**URL:** `/v1/disability-types/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - Type ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "type_id": 1,
    "category_id": 1,
    "category_name": "Visual Impairment",
    "type_name": "Blindness"
  }
}
```

### Create Disability Type

Creates a new disability type.

**URL:** `/v1/disability-types`

**Method:** `POST`

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "category_id": 1,
  "type_name": "Color Blindness"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Disability type created successfully",
  "data": {
    "type_id": 3,
    "category_id": 1,
    "type_name": "Color Blindness"
  }
}
```

### Update Disability Type

Updates an existing disability type.

**URL:** `/v1/disability-types/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Type ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "category_id": 1,  // Optional
  "type_name": "Complete Color Blindness"  // Optional
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Disability type updated successfully",
  "data": {
    "type_id": 3,
    "category_id": 1,
    "type_name": "Complete Color Blindness"
  }
}
```

### Delete Disability Type

Deletes a disability type from the system.

**URL:** `/v1/disability-types/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - Type ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "Disability type deleted successfully"
}
```

---

## Assistance Types API

### List All Assistance Types

Retrieves all assistance types in the system.

**URL:** `/v1/assistance-types`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "assistance_type_id": 1,
      "assistance_type_name": "Financial Aid"
    },
    {
      "assistance_type_id": 2,
      "assistance_type_name": "Medical Support"
    }
  ]
}
```

### Get Assistance Type by ID

Retrieves a specific assistance type by its ID.

**URL:** `/v1/assistance-types/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - Assistance Type ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "assistance_type_id": 1,
    "assistance_type_name": "Financial Aid"
  }
}
```

### Create Assistance Type

Creates a new assistance type.

**URL:** `/v1/assistance-types`

**Method:** `POST`

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "assistance_type_name": "Educational Support"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Assistance type created successfully",
  "data": {
    "assistance_type_id": 3,
    "assistance_type_name": "Educational Support"
  }
}
```

### Update Assistance Type

Updates an existing assistance type.

**URL:** `/v1/assistance-types/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Assistance Type ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "assistance_type_name": "Educational Funding"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Assistance type updated successfully",
  "data": {
    "assistance_type_id": 3,
    "assistance_type_name": "Educational Funding"
  }
}
```

### Delete Assistance Type

Deletes an assistance type from the system.

**URL:** `/v1/assistance-types/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - Assistance Type ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "Assistance type deleted successfully"
}
```

---

## PWD Records API

### List All PWD Records

Retrieves all Person with Disability (PWD) records with optional filtering and pagination.

**URL:** `/v1/pwd-records`

**Method:** `GET`

**Authentication Required:** Yes

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)
- `quarter` - Filter by quarter (Q1, Q2, Q3, Q4)
- `year` - Filter by year (integer)
- `status` - Filter by status (pending, approved, declined)
- `community_id` - Filter by community ID (integer)
- `disability_category_id` - Filter by disability category ID (integer)
- `search` - Search by name or other attributes (string)

**Response:**

```json
{
  "status": "success",
  "data": {
    "records": [
      {
        "pwd_id": 1,
        "user_id": 1,
        "quarter": "Q2",
        "year": 2025,
        "gender_id": 1,
        "gender_name": "Male",
        "full_name": "John Doe",
        "occupation": "Student",
        "contact": "0201234567",
        "dob": "1995-05-15",
        "age": 30,
        "disability_category_id": 1,
        "disability_category_name": "Visual Impairment",
        "disability_type_id": 2,
        "disability_type_name": "Low Vision",
        "gh_card_number": "GHA-123456789-0",
        "nhis_number": "NHIS12345678",
        "community_id": 1,
        "community_name": "Adenta",
        "guardian_name": "Jane Doe",
        "guardian_occupation": "Teacher",
        "guardian_phone": "0231234567",
        "guardian_relationship": "Mother",
        "education_level": "Secondary",
        "school_name": "Adenta Secondary School",
        "assistance_type_needed_id": 1,
        "assistance_type_needed": "Financial Aid",
        "support_needs": "Requires financial support for education",
        "supporting_documents": ["document1.pdf", "document2.pdf"],
        "status": "approved",
        "profile_image": "path/to/profile.jpg",
        "created_at": "2025-04-15 09:30:00"
      }
    ],
    "pagination": {
      "total_records": 45,
      "current_page": 1,
      "per_page": 20,
      "total_pages": 3
    }
  }
}
```

### Get PWD Record by ID

Retrieves a specific PWD record by its ID.

**URL:** `/v1/pwd-records/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - PWD Record ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "pwd_id": 1,
    "user_id": 1,
    "quarter": "Q2",
    "year": 2025,
    "gender_id": 1,
    "gender_name": "Male",
    "full_name": "John Doe",
    "occupation": "Student",
    "contact": "0201234567",
    "dob": "1995-05-15",
    "age": 30,
    "disability_category_id": 1,
    "disability_category_name": "Visual Impairment",
    "disability_type_id": 2,
    "disability_type_name": "Low Vision",
    "gh_card_number": "GHA-123456789-0",
    "nhis_number": "NHIS12345678",
    "community_id": 1,
    "community_name": "Adenta",
    "guardian_name": "Jane Doe",
    "guardian_occupation": "Teacher",
    "guardian_phone": "0231234567",
    "guardian_relationship": "Mother",
    "education_level": "Secondary",
    "school_name": "Adenta Secondary School",
    "assistance_type_needed_id": 1,
    "assistance_type_needed": "Financial Aid",
    "support_needs": "Requires financial support for education",
    "supporting_documents": ["document1.pdf", "document2.pdf"],
    "status": "approved",
    "profile_image": "path/to/profile.jpg",
    "created_at": "2025-04-15 09:30:00"
  }
}
```

### Create PWD Record

Creates a new PWD record in the system.

**URL:** `/v1/pwd-records`

**Method:** `POST`

**Authentication Required:** Yes

**Request Body:**

```json
{
  "quarter": "Q2",
  "year": 2025,
  "gender_id": 1,
  "full_name": "John Doe",
  "occupation": "Student",
  "contact": "0201234567",
  "dob": "1995-05-15",
  "age": 30,
  "disability_category_id": 1,
  "disability_type_id": 2,
  "gh_card_number": "GHA-123456789-0",
  "nhis_number": "NHIS12345678",
  "community_id": 1,
  "guardian_name": "Jane Doe",
  "guardian_occupation": "Teacher",
  "guardian_phone": "0231234567",
  "guardian_relationship": "Mother",
  "education_level": "Secondary",
  "school_name": "Adenta Secondary School",
  "assistance_type_needed_id": 1,
  "support_needs": "Requires financial support for education",
  "supporting_documents": ["document1.pdf", "document2.pdf"],
  "profile_image": "path/to/profile.jpg"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "PWD record created successfully",
  "data": {
    "pwd_id": 2,
    "full_name": "John Doe",
    "status": "pending",
    "created_at": "2025-08-11 15:45:30"
  }
}
```

### Update PWD Record

Updates an existing PWD record.

**URL:** `/v1/pwd-records/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - PWD Record ID (integer)

**Authentication Required:** Yes

**Request Body:**
All fields are optional, similar to the create request body structure.

```json
{
  "full_name": "John A. Doe",
  "contact": "0201234568",
  "community_id": 2
}
```

**Response:**

```json
{
  "status": "success",
  "message": "PWD record updated successfully",
  "data": {
    "pwd_id": 1,
    "full_name": "John A. Doe"
  }
}
```

### Update PWD Record Status

Updates the status of a PWD record.

**URL:** `/v1/pwd-records/{id}/status`

**Method:** `PATCH`

**URL Parameters:**
- `id` - PWD Record ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "status": "approved"  // "pending", "approved", or "declined"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "PWD record status updated to 'approved'",
  "data": {
    "pwd_id": 1,
    "status": "approved"
  }
}
```

### Delete PWD Record

Deletes a PWD record from the system.

**URL:** `/v1/pwd-records/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - PWD Record ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "PWD record deleted successfully"
}
```

### Get PWD Records by Quarter and Year

Retrieves PWD records for a specific quarter and year.

**URL:** `/v1/pwd-records/quarterly/{quarter}/{year}`

**Method:** `GET`

**URL Parameters:**
- `quarter` - Quarter (Q1, Q2, Q3, or Q4)
- `year` - Year (integer)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All PWD Records" but filtered by quarter and year.

### Get PWD Records by Disability Category

Retrieves PWD records for a specific disability category.

**URL:** `/v1/pwd-records/category/{categoryId}`

**Method:** `GET`

**URL Parameters:**
- `categoryId` - Disability Category ID (integer)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All PWD Records" but filtered by disability category.

### Get PWD Records by Community

Retrieves PWD records for a specific community.

**URL:** `/v1/pwd-records/community/{communityId}`

**Method:** `GET`

**URL Parameters:**
- `communityId` - Community ID (integer)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All PWD Records" but filtered by community.

### Get Total PWD Count

Retrieves the total number of PWD records, quarterly additions, and assessed beneficiaries.

**URL:** `/v1/pwd-records/total`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "total_pwd": 145,
  "current_quarter_additions": 23,
  "total_assessed_beneficiaries": 87,
  "current_period": {
    "quarter": "Q3",
    "year": 2025
  },
  "message": "PWD statistics"
}
```

### Get Statistics Dashboard Data

Retrieves comprehensive statistics for dashboard display, including total PWD count, quarterly additions, and assessed beneficiaries.

**URL:** `/v1/pwd-records/statistics`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**
Same as "Get Total PWD Count" endpoint.

---

## Assistance Requests API

### List All Assistance Requests

Retrieves all assistance requests with optional filtering and pagination.

**URL:** `/v1/assistance-requests`

**Method:** `GET`

**Authentication Required:** Yes

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)
- `status` - Filter by status (pending, review, ready_to_access, assessed, declined)
- `assistance_type_id` - Filter by assistance type ID (integer)
- `beneficiary_id` - Filter by beneficiary/PWD ID (integer)
- `requested_by` - Filter by requesting user ID (integer)
- `search` - Search by description or other attributes (string)
- `beneficiary_name` - Filter by beneficiary name (string)

**Response:**

```json
{
  "status": "success",
  "data": {
    "requests": [
      {
        "request_id": 1,
        "assistance_type_id": 1,
        "assistance_type_name": "Financial Aid",
        "beneficiary_id": 1,
        "beneficiary_name": "John Doe",
        "requested_by": 2,
        "requester_name": "Officer Smith",
        "description": "Financial support for school fees",
        "amount_value_cost": 500.00,
        "admin_review_notes": "Reviewed and approved",
        "status": "assessed",
        "created_at": "2025-07-15 14:30:00",
        "updated_at": "2025-07-20 10:45:00"
      }
    ],
    "pagination": {
      "total_records": 30,
      "current_page": 1,
      "per_page": 20,
      "total_pages": 2
    }
  }
}
```

### Get Assistance Request by ID

Retrieves a specific assistance request by its ID.

**URL:** `/v1/assistance-requests/{id}`

**Method:** `GET`

**URL Parameters:**
- `id` - Assistance Request ID (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "request_id": 1,
    "assistance_type_id": 1,
    "assistance_type_name": "Financial Aid",
    "beneficiary_id": 1,
    "beneficiary_name": "John Doe",
    "beneficiary_details": {
      "pwd_id": 1,
      "full_name": "John Doe",
      "community_name": "Adenta",
      "disability_category_name": "Visual Impairment",
      "disability_type_name": "Low Vision"
    },
    "requested_by": 2,
    "requester_name": "Officer Smith",
    "description": "Financial support for school fees",
    "amount_value_cost": 500.00,
    "admin_review_notes": "Reviewed and approved",
    "status": "assessed",
    "created_at": "2025-07-15 14:30:00",
    "updated_at": "2025-07-20 10:45:00"
  }
}
```

### Create Assistance Request

Creates a new assistance request.

**URL:** `/v1/assistance-requests`

**Method:** `POST`

**Authentication Required:** Yes

**Request Body:**

```json
{
  "assistance_type_id": 1,
  "beneficiary_id": 1,
  "description": "Financial support for medical treatment",
  "amount_value_cost": 1000.00
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Assistance request created successfully",
  "data": {
    "request_id": 2,
    "beneficiary_name": "John Doe",
    "assistance_type_name": "Financial Aid",
    "status": "pending",
    "created_at": "2025-08-11 16:30:45"
  }
}
```

### Update Assistance Request

Updates an existing assistance request.

**URL:** `/v1/assistance-requests/{id}`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Assistance Request ID (integer)

**Authentication Required:** Yes

**Request Body:**

```json
{
  "assistance_type_id": 2,
  "description": "Updated: Financial support for specialized medical treatment",
  "amount_value_cost": 1500.00
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Assistance request updated successfully",
  "data": {
    "request_id": 2,
    "assistance_type_id": 2,
    "assistance_type_name": "Medical Support",
    "amount_value_cost": 1500.00
  }
}
```

### Update Assistance Request Status

Updates the status of an assistance request.

**URL:** `/v1/assistance-requests/{id}/status`

**Method:** `PATCH`

**URL Parameters:**
- `id` - Assistance Request ID (integer)

**Authentication Required:** Yes (Admin only)

**Request Body:**

```json
{
  "status": "assessed",  // "pending", "review", "ready_to_access", "assessed", or "declined"
  "admin_notes": "Application reviewed and assistance provided"
}
```

**Response:**

```json
{
  "status": "success",
  "message": "Assistance request status updated to 'assessed'",
  "data": {
    "request_id": 2,
    "status": "assessed",
    "admin_review_notes": "Application reviewed and assistance provided"
  }
}
```

### Delete Assistance Request

Deletes an assistance request from the system.

**URL:** `/v1/assistance-requests/{id}`

**Method:** `DELETE`

**URL Parameters:**
- `id` - Assistance Request ID (integer)

**Authentication Required:** Yes (Admin only)

**Response:**

```json
{
  "status": "success",
  "message": "Assistance request deleted successfully"
}
```

### Get Assistance Requests by Beneficiary

Retrieves all assistance requests for a specific beneficiary (PWD).

**URL:** `/v1/assistance-requests/beneficiary/{beneficiaryId}`

**Method:** `GET`

**URL Parameters:**
- `beneficiaryId` - PWD Record ID (integer)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All Assistance Requests" but filtered by beneficiary.

### Get Assistance Requests by User

Retrieves all assistance requests created by a specific user.

**URL:** `/v1/assistance-requests/user/{userId}`

**Method:** `GET`

**URL Parameters:**
- `userId` - User ID (integer)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All Assistance Requests" but filtered by requesting user.

### Get Assistance Requests by Status

Retrieves all assistance requests with a specific status.

**URL:** `/v1/assistance-requests/status/{status}`

**Method:** `GET`

**URL Parameters:**
- `status` - Status (pending, review, ready_to_access, assessed, declined)

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All Assistance Requests" but filtered by status.

### Get My Assistance Requests

Retrieves all assistance requests created by the currently authenticated user.

**URL:** `/v1/assistance-requests/my-requests`

**Method:** `GET`

**Query Parameters:**
- `page` - Page number (integer, default: 1)
- `per_page` - Records per page (integer, default: 20)

**Authentication Required:** Yes

**Response:**
Same as "List All Assistance Requests" but filtered by the authenticated user.

---

## Quarterly Statistics API

### Get All Statistics

Retrieves all quarterly statistics available in the system.

**URL:** `/v1/statistics`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "period_id": "Q1-2025",
      "quarter": "Q1",
      "year": 2025,
      "total_registered_pwd": 45,
      "total_assessed": 30,
      "pending": 15
    },
    {
      "period_id": "Q2-2025",
      "quarter": "Q2",
      "year": 2025,
      "total_registered_pwd": 52,
      "total_assessed": 40,
      "pending": 12
    }
  ]
}
```

### Get Statistics Grouped by Year

Retrieves statistics grouped by year.

**URL:** `/v1/statistics/yearly`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "year": 2024,
      "total_registered_pwd": 180,
      "total_assessed": 150,
      "pending": 30
    },
    {
      "year": 2025,
      "total_registered_pwd": 97,
      "total_assessed": 70,
      "pending": 27
    }
  ]
}
```

### Get Current Year Statistics

Retrieves statistics for the current year only.

**URL:** `/v1/statistics/current-year`

**Method:** `GET`

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "year": 2025,
    "quarters": [
      {
        "period_id": "Q1-2025",
        "quarter": "Q1",
        "year": 2025,
        "total_registered_pwd": 45,
        "total_assessed": 30,
        "pending": 15
      },
      {
        "period_id": "Q2-2025",
        "quarter": "Q2",
        "year": 2025,
        "total_registered_pwd": 52,
        "total_assessed": 40,
        "pending": 12
      }
    ],
    "total": {
      "total_registered_pwd": 97,
      "total_assessed": 70,
      "pending": 27
    }
  }
}
```

### Get Comparative Statistics

Retrieves comparative statistics for multiple years.

**URL:** `/v1/statistics/compare`

**Method:** `GET`

**Query Parameters:**
- `years` - Comma-separated list of years to compare (e.g., "2023,2024,2025")

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "years": [2023, 2024, 2025],
    "comparative_data": {
      "total_registered_pwd": [156, 180, 97],
      "total_assessed": [140, 150, 70],
      "pending": [16, 30, 27]
    }
  }
}
```

### Get Statistics by Specific Quarter and Year

Retrieves statistics for a specific quarter and year.

**URL:** `/v1/statistics/{quarter}/{year}`

**Method:** `GET`

**URL Parameters:**
- `quarter` - Quarter (Q1, Q2, Q3, or Q4)
- `year` - Year (integer)

**Authentication Required:** Yes

**Response:**

```json
{
  "status": "success",
  "data": {
    "period_id": "Q2-2025",
    "quarter": "Q2",
    "year": 2025,
    "total_registered_pwd": 52,
    "total_assessed": 40,
    "pending": 12
  }
}
```

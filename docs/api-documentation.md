# SSLTD API Documentation

**Version**: 0.1.0  
**Base URL**: `/api`  
**Format**: JSON  
**Authentication**: Session-based (web), Token-based (mobile - planned)

---

## Table of Contents

1. [Authentication](#authentication)
2. [General Information](#general-information)
3. [Error Handling](#error-handling)
4. [Rate Limiting](#rate-limiting)
5. [Endpoints](#endpoints)
   - [Authentication](#authentication-endpoints)
   - [Projects](#projects-endpoints)
   - [Threads](#threads-endpoints)
   - [Timesheets](#timesheets-endpoints)
   - [Uploads](#uploads-endpoints)

---

## General Information

### Request Headers

```
Content-Type: application/json
Accept: application/json
X-CSRF-Token: {token}  (for state-changing operations)
```

### Response Format

All API responses follow a standard format:

**Success Response** (200-299):
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response** (400-599):
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET request |
| 201 | Created | Successful POST creating resource |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

---

## Authentication

### Session-Based Authentication (Web)

The web application uses PHP sessions for authentication. After logging in via the web interface, the session cookie is automatically included in subsequent requests.

### Token-Based Authentication (Mobile - Planned)

For mobile applications, JWT tokens will be used. Include the token in the Authorization header:

```
Authorization: Bearer {jwt_token}
```

---

## Error Handling

### Validation Errors (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email is required", "Email must be valid"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

### Authentication Errors (401)

```json
{
  "success": false,
  "message": "Unauthorized - Please log in"
}
```

### Authorization Errors (403)

```json
{
  "success": false,
  "message": "Forbidden - Insufficient permissions"
}
```

### Not Found Errors (404)

```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## Rate Limiting

**Current Status**: Partial implementation

### Limits

| Endpoint | Limit | Window |
|----------|-------|--------|
| /api/auth/login | 5 requests | 15 minutes |
| /api/auth/register | 3 requests | hour |
| Other endpoints | TBD | TBD |

### Rate Limit Headers

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1609459200
```

---

## Endpoints

## Authentication Endpoints

### POST /api/auth/login

Authenticate a user and create a session.

**Request**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "role": "project_lead"
    },
    "session_id": "abc123..."
  },
  "message": "Login successful"
}
```

**Error Response** (401):
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### POST /api/auth/register

Register a new user account.

**Request**:
```json
{
  "email": "newuser@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "name": "Jane Smith",
  "role": "worker"
}
```

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "email": "newuser@example.com",
      "name": "Jane Smith",
      "role": "worker"
    }
  },
  "message": "Registration successful"
}
```

**Validation Error** (422):
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email is already taken"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

---

### POST /api/auth/logout

Logout the current user and destroy the session.

**Success Response** (200):
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## Projects Endpoints

### GET /api/projects

List all projects (filtered by user permissions).

**Query Parameters**:
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 20, max: 100)
- `status` (string, optional): Filter by status (active, pending, completed)
- `search` (string, optional): Search in project name/description

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "projects": [
      {
        "id": 1,
        "name": "Office Renovation",
        "description": "Renovate main office",
        "status": "active",
        "budget": 50000.00,
        "project_lead_id": 3,
        "created_at": "2026-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 100,
      "per_page": 20
    }
  }
}
```

---

### GET /api/projects/{id}

Get details of a specific project.

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Office Renovation",
    "description": "Renovate main office building",
    "status": "active",
    "budget": 50000.00,
    "project_lead": {
      "id": 3,
      "name": "John Manager",
      "email": "john@example.com"
    },
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-01 14:20:00"
  }
}
```

**Error Response** (404):
```json
{
  "success": false,
  "message": "Project not found"
}
```

---

### POST /api/projects

Create a new project (requires project_lead or admin role).

**Request**:
```json
{
  "name": "New Construction Project",
  "description": "Build new warehouse",
  "budget": 100000.00,
  "project_lead_id": 3,
  "status": "pending"
}
```

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 15,
    "name": "New Construction Project",
    "description": "Build new warehouse",
    "budget": 100000.00,
    "project_lead_id": 3,
    "status": "pending",
    "created_at": "2026-02-08 15:45:00"
  },
  "message": "Project created successfully"
}
```

---

### PUT /api/projects/{id}

Update an existing project.

**Request**:
```json
{
  "name": "Updated Project Name",
  "status": "active"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 15,
    "name": "Updated Project Name",
    "status": "active",
    "updated_at": "2026-02-08 16:00:00"
  },
  "message": "Project updated successfully"
}
```

---

### DELETE /api/projects/{id}

Delete a project (soft delete).

**Success Response** (204):
No content

---

## Threads Endpoints

### GET /api/threads

List communication threads (filtered by project access).

**Query Parameters**:
- `project_id` (integer, optional): Filter by project
- `page` (integer, optional): Page number
- `per_page` (integer, optional): Items per page

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "threads": [
      {
        "id": 1,
        "project_id": 5,
        "subject": "Material delivery delay",
        "created_by": 2,
        "created_at": "2026-02-07 09:00:00",
        "replies_count": 3
      }
    ]
  }
}
```

---

### POST /api/threads

Create a new communication thread.

**Request**:
```json
{
  "project_id": 5,
  "subject": "Schedule update needed",
  "message": "We need to adjust the timeline...",
  "attachments": []
}
```

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 42,
    "project_id": 5,
    "subject": "Schedule update needed",
    "created_at": "2026-02-08 16:15:00"
  },
  "message": "Thread created successfully"
}
```

---

## Timesheets Endpoints

### GET /api/timesheets

List timesheet entries (filtered by user permissions).

**Query Parameters**:
- `user_id` (integer, optional): Filter by user (requires manager+)
- `project_id` (integer, optional): Filter by project
- `start_date` (date, optional): Filter entries from date
- `end_date` (date, optional): Filter entries to date
- `status` (string, optional): pending, approved, rejected

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "timesheets": [
      {
        "id": 100,
        "user_id": 5,
        "project_id": 3,
        "date": "2026-02-07",
        "hours": 8.5,
        "description": "Foundation work",
        "status": "pending"
      }
    ]
  }
}
```

---

### POST /api/timesheets

Submit a timesheet entry.

**Request**:
```json
{
  "project_id": 3,
  "date": "2026-02-08",
  "hours": 7.5,
  "description": "Electrical installation"
}
```

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 101,
    "user_id": 5,
    "project_id": 3,
    "date": "2026-02-08",
    "hours": 7.5,
    "status": "pending",
    "created_at": "2026-02-08 18:00:00"
  },
  "message": "Timesheet submitted successfully"
}
```

---

## Uploads Endpoints

### POST /api/uploads

Upload a file.

**Request**: multipart/form-data
- `file`: File to upload
- `type` (optional): File category (document, image, etc.)
- `description` (optional): File description

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 250,
    "filename": "blueprint_v2.pdf",
    "size": 2048576,
    "mime_type": "application/pdf",
    "url": "/api/uploads/250",
    "created_at": "2026-02-08 16:30:00"
  },
  "message": "File uploaded successfully"
}
```

**Error Response** (422):
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file": ["File size exceeds maximum allowed (10MB)"]
  }
}
```

---

### GET /api/uploads/{id}

Download a file.

**Success Response**: Binary file with appropriate Content-Type and Content-Disposition headers

**Error Response** (404):
```json
{
  "success": false,
  "message": "File not found"
}
```

---

## Future Endpoints (Planned)

### Reports

- `GET /api/reports` - List available reports
- `POST /api/reports/generate` - Generate custom report

### Notifications

- `GET /api/notifications` - List user notifications
- `PUT /api/notifications/{id}/read` - Mark notification as read

### Dashboard

- `GET /api/dashboard/stats` - Get dashboard statistics
- `GET /api/dashboard/recent-activity` - Get recent activity feed

---

## Versioning

**Current Version**: v1 (implicit, no version prefix required)

**Future Versions**: When breaking changes are introduced, API will be versioned:
- v2: `/api/v2/projects`
- v1 (legacy): `/api/v1/projects` or `/api/projects`

---

## Support and Feedback

For API support or to report issues:
- Create an issue in the GitHub repository
- Contact: [See CONTRIBUTING.md](../CONTRIBUTING.md)

---

**Last Updated**: 2026-02-08  
**Maintained by**: Development Team

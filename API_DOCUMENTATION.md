# Chores API Documentation
**Version:** 1.0  
**Base URL:** `http://your-server.com/api.php`  
**Response Format:** JSON

---

## Overview
This API provides endpoints for the Chores application, allowing Android apps to:
- Retrieve user information and chore assignments
- Submit and approve chores
- Track activity history and user balances
- Handle bonus rewards

All responses follow a consistent JSON format with a `success` boolean and either `data` or `error` fields.

---

## Authentication
Currently, the API does not use token-based authentication. PIN verification is used for approving chores through the `verify_pin` and `approve_chore` endpoints.

---

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { /* response data */ }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error type",
  "message": "Detailed error message"
}
```

---

## Endpoints

### 1. Get Users
Retrieve a list of all users (excluding admin types).

**Endpoint:** `GET /api.php?endpoint=users`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "type": 2
    },
    {
      "id": 2,
      "name": "Jane Doe",
      "type": 3
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=users"
```

---

### 2. Get User Chores
Retrieve today's chores assigned to a specific user.

**Endpoint:** `GET /api.php?endpoint=user_chores&user_id={id}`

**Parameters:**
- `user_id` (required): The ID of the user

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "user_name": "John Doe",
      "chore_name": "Clean Kitchen",
      "description": "Wipe counters and sweep floor",
      "assignment_id": 123,
      "chore_id": 45,
      "status": "incomplete",
      "pay": "2.50",
      "max_quantity": 1
    },
    {
      "user_name": "John Doe",
      "chore_name": "Take out trash",
      "description": "Take all trash to curb",
      "assignment_id": 124,
      "chore_id": 46,
      "status": "complete",
      "pay": "1.00",
      "max_quantity": 1
    }
  ]
}
```

**Status Values:**
- `complete` - Chore has been completed today
- `pending` - Chore is awaiting approval
- `incomplete` - Chore has not been started

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=user_chores&user_id=1"
```

---

### 3. Get All Chores
Retrieve all chores for today across all users.

**Endpoint:** `GET /api.php?endpoint=all_chores`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "user_id": 1,
      "user_name": "John Doe",
      "chore_name": "Clean Kitchen",
      "description": "Wipe counters and sweep floor",
      "assignment_id": 123,
      "chore_id": 45,
      "status": "incomplete",
      "quantity": 0,
      "pay": "2.50",
      "max_quantity": 1
    },
    {
      "user_id": 2,
      "user_name": "Jane Doe",
      "chore_name": "Feed pets",
      "description": "Feed all pets",
      "assignment_id": 125,
      "chore_id": 47,
      "status": "complete",
      "quantity": 2,
      "pay": "1.50",
      "max_quantity": 2
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=all_chores"
```

---

### 4. Get Chore Detail
Retrieve detailed information about a specific chore assignment.

**Endpoint:** `GET /api.php?endpoint=chore_detail&assignment_id={id}&user_id={id}`

**Parameters:**
- `assignment_id` (required): The assignment ID
- `user_id` (required): The user ID

**Response:**
```json
{
  "success": true,
  "data": {
    "chore_name": "Clean Kitchen",
    "description": "Wipe counters, sweep and mop floor, clean appliances",
    "pay": "2.50",
    "assignment_id": 123,
    "chore_id": 45,
    "assigned_user": 1,
    "max_quantity": 1
  }
}
```

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=chore_detail&assignment_id=123&user_id=1"
```

---

### 5. Submit Chore
Submit a chore for approval (creates a pending request and optionally sends email notification).

**Endpoint:** `POST /api.php?endpoint=submit_chore`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "assignment_id": 123,
  "user_id": 1,
  "count": 1
}
```

**Parameters:**
- `assignment_id` (required): The assignment ID
- `user_id` (required): The user ID
- `count` (required): Number of times chore was completed (usually 1)

**Response:**
```json
{
  "success": true,
  "message": "Chore submitted for approval",
  "data": {
    "username": "John Doe",
    "chore_name": "Clean Kitchen",
    "count": 1,
    "total_pay": "2.50"
  }
}
```

**Example Request:**
```bash
curl -X POST "http://your-server.com/api.php?endpoint=submit_chore" \
  -H "Content-Type: application/json" \
  -d '{"assignment_id": 123, "user_id": 1, "count": 1}'
```

---

### 6. Approve Chore
Approve a chore completion with PIN verification.

**Endpoint:** `POST /api.php?endpoint=approve_chore`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "assignment_id": 123,
  "user_id": 1,
  "approver_id": 5,
  "pin": "1234",
  "count": 1
}
```

**Parameters:**
- `assignment_id` (required): The assignment ID
- `user_id` (required): The user who completed the chore
- `approver_id` (required): The user ID of the approver (parent)
- `pin` (required): The approver's 4-digit PIN
- `count` (required): Number of times chore was completed

**Response:**
```json
{
  "success": true,
  "message": "Chore approved successfully",
  "data": {
    "approved_by": "Parent Name",
    "total_pay": "2.50",
    "payrate": "2.50",
    "quantity": 1
  }
}
```

**Error Response (Invalid PIN):**
```json
{
  "success": false,
  "error": "Invalid PIN"
}
```

**Example Request:**
```bash
curl -X POST "http://your-server.com/api.php?endpoint=approve_chore" \
  -H "Content-Type: application/json" \
  -d '{"assignment_id": 123, "user_id": 1, "approver_id": 5, "pin": "1234", "count": 1}'
```

---

### 7. Verify PIN
Verify a user's PIN without performing any other action.

**Endpoint:** `POST /api.php?endpoint=verify_pin`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "user_id": 5,
  "pin": "1234"
}
```

**Parameters:**
- `user_id` (required): The user ID
- `pin` (required): The 4-digit PIN to verify

**Response:**
```json
{
  "success": true,
  "message": "PIN verified",
  "data": {
    "user_name": "Parent Name"
  }
}
```

**Example Request:**
```bash
curl -X POST "http://your-server.com/api.php?endpoint=verify_pin" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 5, "pin": "1234"}'
```

---

### 8. Submit Bonus
Submit a bonus activity for approval (bonus activities earn $0.50 each).

**Endpoint:** `POST /api.php?endpoint=bonus`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "user_id": 1,
  "approver_id": 5,
  "pin": "1234",
  "count": 3
}
```

**Parameters:**
- `user_id` (required): The user earning the bonus
- `approver_id` (required): The approver's user ID (parent)
- `pin` (required): The approver's 4-digit PIN
- `count` (required): Number of bonus units (each worth $0.50)

**Response:**
```json
{
  "success": true,
  "message": "Bonus approved successfully",
  "data": {
    "approved_by": "Parent Name",
    "total_pay": 1.5,
    "payrate": 0.5,
    "quantity": 3
  }
}
```

**Example Request:**
```bash
curl -X POST "http://your-server.com/api.php?endpoint=bonus" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "approver_id": 5, "pin": "1234", "count": 3}'
```

---

### 9. Get Activity History
Retrieve a user's activity history (completed and approved chores/bonuses).

**Endpoint:** `GET /api.php?endpoint=activity_history&user_id={id}&limit={number}`

**Parameters:**
- `user_id` (required): The user ID
- `limit` (optional): Maximum number of records to return (default: 50)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2025-12-16",
      "timestamp": "2025-12-16 14:30:00",
      "chore_name": "Clean Kitchen",
      "payrate": "2.50",
      "quantity": 1,
      "total_pay": "2.50"
    },
    {
      "date": "2025-12-16",
      "timestamp": "2025-12-16 10:15:00",
      "chore_name": "Bonus",
      "payrate": "0.50",
      "quantity": 2,
      "total_pay": "1.00"
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=activity_history&user_id=1&limit=20"
```

---

### 10. Get User Balance
Retrieve a user's total earnings balance.

**Endpoint:** `GET /api.php?endpoint=user_balance&user_id={id}`

**Parameters:**
- `user_id` (required): The user ID

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "user_name": "John Doe",
    "total_balance": 125.50
  }
}
```

**Example Request:**
```bash
curl -X GET "http://your-server.com/api.php?endpoint=user_balance&user_id=1"
```

---

## HTTP Status Codes

The API uses standard HTTP status codes:

- `200 OK` - Successful request
- `400 Bad Request` - Missing or invalid parameters
- `401 Unauthorized` - Invalid PIN or authentication failure
- `404 Not Found` - Resource not found (user, chore, etc.)
- `405 Method Not Allowed` - Wrong HTTP method used
- `500 Internal Server Error` - Database or server error

---

## Android Implementation Example

### Using Retrofit (Recommended)

**1. Add dependencies to build.gradle:**
```gradle
implementation 'com.squareup.retrofit2:retrofit:2.9.0'
implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
```

**2. Create API interface:**
```java
public interface ChoresApi {
    @GET("api.php?endpoint=users")
    Call<ApiResponse<List<User>>> getUsers();
    
    @GET("api.php?endpoint=user_chores")
    Call<ApiResponse<List<Chore>>> getUserChores(@Query("user_id") int userId);
    
    @POST("api.php?endpoint=approve_chore")
    Call<ApiResponse<ApprovalResult>> approveChore(@Body ApproveRequest request);
}
```

**3. Create data models:**
```java
public class ApiResponse<T> {
    private boolean success;
    private T data;
    private String error;
    private String message;
    
    // Getters and setters
}

public class User {
    private int id;
    private String name;
    private int type;
    
    // Getters and setters
}

public class Chore {
    private String userName;
    private String choreName;
    private String description;
    private int assignmentId;
    private int choreId;
    private String status;
    private String pay;
    private int maxQuantity;
    
    // Getters and setters
}

public class ApproveRequest {
    private int assignmentId;
    private int userId;
    private int approverId;
    private String pin;
    private int count;
    
    // Constructor, getters, and setters
}
```

**4. Make API calls:**
```java
Retrofit retrofit = new Retrofit.Builder()
    .baseUrl("http://your-server.com/")
    .addConverterFactory(GsonConverterFactory.create())
    .build();

ChoresApi api = retrofit.create(ChoresApi.class);

// Get users
api.getUsers().enqueue(new Callback<ApiResponse<List<User>>>() {
    @Override
    public void onResponse(Call<ApiResponse<List<User>>> call, 
                          Response<ApiResponse<List<User>>> response) {
        if (response.isSuccessful() && response.body().isSuccess()) {
            List<User> users = response.body().getData();
            // Handle users
        }
    }
    
    @Override
    public void onFailure(Call<ApiResponse<List<User>>> call, Throwable t) {
        // Handle error
    }
});
```

---

## Security Notes

1. **HTTPS**: Use HTTPS in production to encrypt data transmission
2. **PIN Security**: PINs are currently stored and compared as plain text. Consider hashing PINs for better security
3. **Rate Limiting**: Consider implementing rate limiting to prevent brute force attacks on PINs
4. **Input Validation**: The API performs basic validation, but additional client-side validation is recommended
5. **Network Security**: Ensure your app uses certificate pinning for production

---

## Testing

Use tools like Postman or curl to test the API endpoints before integrating into your Android app.

**Test Workflow:**
1. Get list of users
2. Get chores for a specific user
3. Submit a chore for approval
4. Verify PIN
5. Approve the chore
6. Check activity history
7. Check user balance

---

## Support

For issues or questions about the API, contact the development team.

**API Version:** 1.0  
**Last Updated:** December 16, 2025

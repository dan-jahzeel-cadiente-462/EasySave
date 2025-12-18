# Activity Logging EventSubscribers Documentation

## Overview
EventSubscribers automatically capture and log system activities without requiring manual logging calls in controllers.

## Implemented EventSubscribers

### 1. SecurityEventSubscriber
**Location:** `src/EventSubscriber/SecurityEventSubscriber.php`

**Listens to:**
- `LoginSuccessEvent` - Fires after successful user login
- `LogoutEvent` - Fires when user logs out

**Automatically Logs:**
- ✅ User login with timestamp, IP address, User Agent
- ✅ User logout with timestamp, IP address, User Agent

**Example Log Entry:**
```
Action: LOGIN
User: john_doe (ROLE_ADMIN)
Entity: User #5
Description: john_doe logged in
Timestamp: 2025-12-16 10:30:45
IP Address: 192.168.1.100
User Agent: Mozilla/5.0...
```

---

### 2. DoctrineEventSubscriber
**Location:** `src/EventSubscriber/DoctrineEventSubscriber.php`

**Listens to:**
- `postPersist` - After a new entity is created
- `postUpdate` - After an entity is modified
- `preRemove` - Before an entity is deleted

**Automatically Logs:**
- ✅ New entity creation (Product, Category, Order, etc.)
- ✅ Entity modifications with changed data
- ✅ Entity deletions with backup data

**Example Log Entries:**

**CREATE:**
```
Action: CREATE
User: staff_user (ROLE_STAFF)
Entity: Product #42
Description: Created new Product
Details: { "name": "Laptop", "price": 999.99 }
```

**UPDATE:**
```
Action: UPDATE
User: admin_user (ROLE_ADMIN)
Entity: Product #42
Description: Updated Product
Details: { "name": "Laptop Pro", "price": 1299.99 }
```

**DELETE:**
```
Action: DELETE
User: admin_user (ROLE_ADMIN)
Entity: Product #42
Description: Deleted Product
Details: { "name": "Laptop Pro" }
```

---

## Features

### Automatic Entity Data Capture
The DoctrineEventSubscriber automatically extracts and logs:
- Entity name (getId)
- Title (getTitle)
- Email (getEmail)
- Username (getUsername)

### Security Features
- ✅ **Skip Sensitive Entities:** ActivityLog and User entities skip logging to prevent recursion
- ✅ **User Context:** Always captures authenticated user
- ✅ **Security Check:** Returns early if no user is authenticated

### Performance Considerations
- ✅ **Priority 500:** Subscribers run at appropriate priority
- ✅ **Early Returns:** Skip non-loggable entities early
- ✅ **Lazy Loading:** Services loaded only when events occur

### Disable/Enable Logging
```php
// In a controller or service, you can disable logging temporarily:
$doctrineSubscriber->disableLogging();
// ... do something without logging ...
$doctrineSubscriber->enableLogging();
```

---

## Activity Logs Generated Automatically

### Login/Logout Events
```
Events: LoginSuccessEvent, LogoutEvent
Entities: None
Captures: User, timestamp, IP, browser info
```

### Entity CRUD Events
```
Events: postPersist (CREATE), postUpdate (UPDATE), preRemove (DELETE)
Entities: Product, Category, Order, OrderItem, User, etc.
Captures: Entity type, ID, user, timestamp, changed data
```

---

## Database Records

All activity logs are stored in the `activity_log` table with:
- `id` - Auto-increment primary key
- `user_id` - Reference to authenticated user
- `action` - LOGIN, LOGOUT, CREATE, UPDATE, DELETE
- `entity_type` - Product, Category, User, Order, etc.
- `entity_id` - ID of the affected entity
- `description` - Human-readable action description
- `details` - JSON with entity data
- `created_at` - Timestamp
- `ip_address` - Client IP address
- `user_agent` - Browser information

---

## Accessing Activity Logs

### Admin Dashboard
Navigate to `/admin/activity-logs` to view all activity logs with filtering:
- Filter by action (LOGIN, LOGOUT, CREATE, UPDATE, DELETE)
- Filter by user (username or ID)
- Filter by date range
- View detailed log information

### Programmatic Access
```php
// In a controller or service:
$activityLogRepository = $this->entityManager->getRepository(ActivityLog::class);

// Get recent logs
$recentLogs = $activityLogRepository->getRecentLogs(20);

// Get logs by user
$userLogs = $activityLogRepository->findByUser($userId);

// Get filtered logs
$logs = $activityLogRepository->findByFilters(
    'john_doe',                    // username
    'CREATE',                      // action
    new DateTime('2025-12-01'),    // from date
    new DateTime('2025-12-31'),    // to date
    50                            // limit
);
```

---

## Configuration

### Security Access Control
Located in `config/packages/security.yaml`:
```yaml
access_control:
    - { path: ^/admin/activity-logs, roles: ROLE_ADMIN }  # Admin only
    - { path: ^/admin, roles: [ROLE_ADMIN, ROLE_STAFF] }  # Admin & Staff
    - { path: ^/user, roles: ROLE_USER }                  # Users only
```

**Protection:**
- ✅ Activity logs are admin-only (read-only)
- ✅ Staff cannot access activity logs
- ✅ Users cannot access admin panel

---

## Security Notes

1. **No Manual Calls Needed:** All logging happens automatically
2. **Immutable Logs:** Activity logs cannot be modified or deleted by users
3. **Complete Audit Trail:** Every entity change is tracked
4. **User Attribution:** All logs include the authenticated user
5. **IP & Browser Tracking:** Network and browser information captured

---

## Example Scenarios

### Scenario 1: Admin Creates User
```
1. Admin navigates to /admin/user/management
2. Admin fills form and submits
3. SecurityEventSubscriber SKIPPED (not login/logout)
4. DoctrineEventSubscriber TRIGGERED (postPersist)
5. Activity Log Created:
   - Action: CREATE
   - User: admin_user
   - Entity: User #10
   - Data: { username: "new_staff", email: "staff@example.com" }
```

### Scenario 2: Staff Logs In
```
1. Staff visits /login
2. Credentials verified
3. SecurityEventSubscriber TRIGGERED (LoginSuccessEvent)
4. Activity Log Created:
   - Action: LOGIN
   - User: staff_user
   - Entity: User #5
   - IP Address: 203.0.113.45
   - Timestamp: 2025-12-16 14:23:10
```

### Scenario 3: Staff Creates Product
```
1. Staff creates product in /admin/product/new
2. Form submitted
3. DoctrineEventSubscriber TRIGGERED (postPersist)
4. Activity Log Created:
   - Action: CREATE
   - User: staff_user
   - Entity: Product #42
   - Data: { name: "Gaming Mouse", price: 49.99 }
```

### Scenario 4: Admin Deletes Product
```
1. Admin navigates to /admin/product/{id}/delete
2. Confirms deletion
3. DoctrineEventSubscriber TRIGGERED (preRemove)
4. Activity Log Created:
   - Action: DELETE
   - User: admin_user
   - Entity: Product #42
   - Data: { name: "Gaming Mouse", price: 49.99 }
```

---

## Monitoring Activity

### Check Recent Activities
```bash
php bin/console doctrine:query:sql "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10"
```

### Monitor User Actions
```bash
php bin/console doctrine:query:sql "SELECT * FROM activity_log WHERE user_id = 1 ORDER BY created_at DESC"
```

### Audit Trail by Action
```bash
php bin/console doctrine:query:sql "SELECT * FROM activity_log WHERE action = 'DELETE' ORDER BY created_at DESC"
```

---

## Summary

✅ **Automatic Activity Logging with EventSubscribers:**
- Login/Logout events captured automatically
- Entity CRUD operations logged with full details
- Immutable, read-only audit trail
- Admin-only access with advanced filtering
- IP address and browser tracking
- Complete system audit history

**No more manual logging calls needed!**

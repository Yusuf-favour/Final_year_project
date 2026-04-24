# Multi-Institution Login Implementation Guide

## Overview
This enhancement adds institution selection to the login page, allowing multiple universities to use the same Student Management System. Students now select their institution before logging in.

## What's New

### 1. **New Features**
- Institution dropdown on login page
- Dynamic institution loading from database
- Institution-specific authentication (users can only login to their assigned institution)
- Institution tracking in user sessions

### 2. **Files Created/Modified**

#### New Files:
- `database/add_institutions_table.sql` - Database migration script
- `api/get_institutions.php` - API endpoint to fetch active institutions

#### Modified Files:
- `login.php` - Enhanced with institution dropdown and updated authentication

## Setup Instructions

### Step 1: Run Database Migration

Execute the migration script on your MySQL database:

```sql
-- Option A: Run directly in MySQL GUI
source /path/to/Student-Management-System/database/add_institutions_table.sql;

-- Option B: Run from command line
mysql -u root -p your_database_name < database/add_institutions_table.sql
```

**Note:** This script will:
- Create the `institutions` table
- Add `institution_id` column to `users` table
- Insert 3 demo institutions (SwiftGrade, LASCOHET, UNIDEL)

### Step 2: Update Existing Users (IMPORTANT)

If you have existing users without an institution assigned, update them:

```sql
-- Assign all existing users to the default institution (SwiftGrade - id=1)
UPDATE users SET institution_id = 1 WHERE institution_id IS NULL OR institution_id = 0;
```

Or assign to specific institutions:
```sql
-- For LASCOHET
UPDATE users SET institution_id = 2 WHERE username IN ('student1', 'student2');

-- For UNIDEL
UPDATE users SET institution_id = 3 WHERE username IN ('student3', 'student4');
```

## How to Add a New Institution

1. **Add via SQL:**
```sql
INSERT INTO institutions (code, name, short_name, email, website) 
VALUES ('NEWU', 'New University Name', 'NEWU', 'info@newu.edu', 'www.newu.edu');
```

2. **Then assign students to that institution:**
```sql
UPDATE users 
SET institution_id = (SELECT id FROM institutions WHERE code = 'NEWU')
WHERE username IN ('student_list_here');
```

## Login Flow

### For Students:
1. **Select Institution** → Dropdown appears with all active institutions
2. **Enter Credentials** → Username and password
3. **Submit** → System validates credentials against the selected institution
4. **Session Created** → `institution_id` stored in session for later use

### Error Messages:
- "Please select your institution." - No institution selected
- "Student not found or wrong password at [Institution Name]." - Invalid credentials for that institution

## Important Considerations for Your Defense

### Key Points to Highlight:
1. **Scalability**: The system can support unlimited institutions without code changes
2. **Data Isolation**: Each student is tied to their institution, ensuring data segregation
3. **Multi-tenancy**: All institutions use the same application but with separate authentication
4. **Easy Onboarding**: New institutions only need to:
   - Be added to the `institutions` table
   - Have their student records assigned an `institution_id`
5. **Future Enhancements**: 
   - Institution-specific branding (logos, colors)
   - Institution-specific reports and analytics
   - Admin dashboard for each institution

## Session Variables

The login now stores institution information:

```php
$_SESSION['user_id']       // Student's user ID
$_SESSION['username']      // Student's username
$_SESSION['role']          // Student's role (student, admin, etc.)
$_SESSION['full_name']     // Student's full name
$_SESSION['institution_id'] // NEW: Student's institution ID
```

To access institution information anywhere in your app:
```php
$institution_id = $_SESSION['institution_id'];

// Fetch institution details:
$stmt = $conn->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$institution = $stmt->get_result()->fetch_assoc();
echo $institution['name'];
```

## Testing

### Test Case 1: Select Institution (Required)
```
Step: Leave institution dropdown empty and submit
Expected: Error message "Please select your institution."
```

### Test Case 2: Valid Login with Institution
```
Step: Select "SwiftGrade University", enter valid credentials
Expected: Login successful, redirected to student dashboard
```

### Test Case 3: Wrong Institution
```
Step: Create user in "SwiftGrade", try logging in as "LASCOHET"
Expected: Error "Student not found or wrong password"
```

## Troubleshooting

### Problem: "Error loading institutions" in dropdown
**Solution:** 
- Check if `api/get_institutions.php` exists
- Verify database connection in `api/get_institutions.php`
- Check browser console for network errors

### Problem: Users can't login after update
**Solution:**
- Verify `institution_id` is set for all users
- Run: `SELECT * FROM users WHERE institution_id IS NULL OR institution_id = 0;`
- Assign institution_id as shown in Step 2

### Problem: New institution not appearing in dropdown
**Solution:**
- Check if `is_active = 1` in institutions table
- Verify the record was inserted: `SELECT * FROM institutions;`
- Clear browser cache

## File Structure Reference

```
Student-Management-System/
├── login.php                              (MODIFIED - has institution dropdown)
├── api/
│   └── get_institutions.php              (NEW - fetches institutions)
├── database/
│   └── add_institutions_table.sql        (NEW - migration script)
└── ... (other files unchanged)
```

## Discussion Points for Defense

1. **Problem Statement**: "How do we support multiple institutions without duplicating the entire system?"
2. **Solution**: "We implemented institution-based authentication where students select their university on login, and all data is segregated by institution."
3. **Benefits**:
   - Cost-effective (single system serves multiple clients)
   - Scalable (add institutions without system changes)
   - Maintainable (centralized codebase)
   - Professional (demonstrates SaaS architecture knowledge)

---

**Version**: 1.0  
**Date**: April 2026  
**Status**: Ready for Implementation

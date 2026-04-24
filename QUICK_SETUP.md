# Implementation Checklist - Multi-Institution Login

## Quick Setup (5 minutes)

- [ ] **Step 1**: Execute the migration SQL
  - File: `database/add_institutions_table.sql`
  - Method: Run in phpMyAdmin or MySQL CLI
  - Expected: No errors, tables created

- [ ] **Step 2**: Update existing users with institution_id
  ```sql
  UPDATE users SET institution_id = 1 WHERE institution_id IS NULL OR institution_id = 0;
  ```

- [ ] **Step 3**: Test the login page
  - Open: `http://localhost/Student-Management-System/login.php`
  - Check: Institution dropdown appears and loads institutions
  - Check: Can see SwiftGrade, LASCOHET, UNIDEL in dropdown

- [ ] **Step 4**: Test login with institution selection
  - Select: Any institution from dropdown
  - Enter: Valid student credentials
  - Expected: Login successful, redirect to student dashboard

## Verification

### Database Verification
```sql
-- Check institutions table exists
SELECT * FROM institutions;

-- Check users have institution_id
SELECT id, username, institution_id FROM users LIMIT 5;

-- Check foreign key is active
SHOW CREATE TABLE users\G
```

### Login Page Verification
- [ ] Institution dropdown loads on page load
- [ ] All active institutions appear in dropdown
- [ ] Error shows if no institution selected
- [ ] Error shows if institution + credentials don't match
- [ ] Session includes `$_SESSION['institution_id']` after login

### API Endpoint Verification
```
URL: http://localhost/Student-Management-System/api/get_institutions.php
Expected Response:
{
  "success": true,
  "data": [
    {"id": 1, "code": "SWIFTGRADE", "name": "SwiftGrade University", ...},
    {"id": 2, "code": "LASCOHET", "name": "Lagos State College...", ...},
    {"id": 3, "code": "UNIDEL", "name": "University of Delhi", ...}
  ]
}
```

## Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| Dropdown shows no institutions | Database not updated | Run migration SQL |
| "Error loading institutions" | API endpoint error | Check `api/get_institutions.php` exists |
| Login fails for valid user | No institution_id assigned | Run UPDATE query in Step 2 |
| Dropdown empty but API works | is_active = 0 | Set `is_active = 1` in institutions table |

## Files Changed

| File | Type | Change |
|------|------|--------|
| `login.php` | Modified | Added institution dropdown, updated auth logic |
| `api/get_institutions.php` | New | API endpoint to fetch institutions |
| `database/add_institutions_table.sql` | New | Migration script |

## What to Tell Your Supervisor 🎓

**"To demonstrate scalability, I implemented institution-based authentication. Now instead of separate databases for each university, they can all use the same system. On the login page, students select their institution, and the system authenticates them specifically for that institution. This design allows unlimited universities to join without any code modifications - they just add their institution to a list and upload student records."**

### Demo Flow:
1. Show login page with institution dropdown
2. Show database with multiple institutions
3. Show user authenticated for selected institution only
4. Explain how easy it is to add new universities

---

**Ready to implement?** Start with Step 1 and work through each item! ✅

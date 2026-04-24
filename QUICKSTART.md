# Quick Start Guide - New Features Testing

## 🚀 Getting Started

After the system update, follow these steps to start using the new features:

### Step 1: Setup Database (First Time Only)
```
1. Open browser and navigate to: http://localhost/Student-Management-System/setup_attendance.php
2. You should see a success message
3. The attendance table will be automatically created
```

### Step 2: Login to System
- Use your existing credentials
- System will automatically route to appropriate dashboard based on your role

---

## 👨‍🎓 For Students

### Access New Features:
1. **Go to Dashboard** → Click one of the new quick action buttons:
   - View Transcript
   - Academic Standing
   - My Profile

### My Profile (`/student/profile.php`)
**What you can do:**
- View your complete student information
- Update your email address
- Change your password
- Access all other features from one place

**To Access:**
- Dashboard → Quick Actions → "My Profile" button
- Or direct URL: `/student/profile.php`

### Academic Transcript (`/student/transcript.php`)
**What you can do:**
- See all your past courses and grades
- View semester-by-semester breakdown
- Check your CGPA (Cumulative Grade Point Average)
- See academic standing classification
- Print your transcript

**To Access:**
- Dashboard → Quick Actions → "Transcript" button
- Or go to: `/student/transcript.php`

### Academic Standing (`/student/academic_standing.php`)
**What you can do:**
- Track your academic progress
- See detailed CGPA calculation
- View semester performance trends
- Check pass/fail rates
- Get recommendations for improvement
- Check if on academic probation

**To Access:**
- Dashboard → Quick Actions → "Academic Standing" button
- Or go to: `/student/academic_standing.php`

---

## 👨‍🏫 For Lecturers

### Access New Features:
1. **Go to Dashboard** → Click new "View My Courses" button
2. Or access each feature directly from course cards

### My Courses (`/lecturer/my_courses.php`)
**What you can do:**
- See all courses assigned to you
- Check enrollment numbers
- Track your submission progress
- Quick access to all course tools

**To Access:**
- Dashboard → "View My Courses" button
- Or go to: `/lecturer/my_courses.php`

### Enter Marks (`/lecturer/enter_marks.php`)
**Step-by-step:**
1. Go to My Courses
2. Click "Enter Marks" button for desired course
3. For each student:
   - Enter CA score (0-30)
   - Enter Exam score (0-70)
   - Total automatically calculates
   - Grade automatically assigned
4. Click "Save All Marks" to submit

**Features:**
- Automatic grade calculation
- Color-coded score indicators
- CA range: 0-30 points
- Exam range: 0-70 points
- Automatic letter grade assignment

### Class Register (Attendance) (`/lecturer/class_register.php`)
**Step-by-step:**
1. Go to My Courses
2. Click "Attendance" button for desired course
3. Select date for attendance marking
4. Check boxes for present students (or use bulk buttons)
5. Click "Save Attendance" to record

**Features:**
- Date selector for specific class date
- Check/uncheck individual students
- "Check All" button to mark entire class present
- "Uncheck All" button to reset
- Running attendance count per student

### View Results (`/lecturer/view_results.php`)
**Step-by-step:**
1. Go to My Courses
2. Click "View Results" button for desired course
3. See all student results in one table
4. View class statistics
5. Edit individual grades if needed

**Features:**
- Class average display
- Pass rate percentage
- Grade distribution visualization
- Edit button for each student grade
- Modal popup for grade editing

### Course Analytics (`/lecturer/course_analytics.php`)
**Step-by-step:**
1. Go to My Courses
2. Click "Analytics" button for desired course
3. Review detailed course performance

**Features:**
- Key metrics (average, pass rate, median, count)
- Grade distribution chart (A, B, C, D, E, F)
- Highest/lowest scores
- Score spread analysis
- Pass/fail breakdown

---

## 📊 Common Tasks - Quick Reference

### Student Task: Check My GPA
1. Login as student
2. Dashboard → "Academic Standing" button
3. View CGPA at top of page

### Student Task: Print My Transcript
1. Login as student
2. Dashboard → "Transcript" button
3. Right-click → Print or Press Ctrl+P
4. Select "Print to PDF" option

### Lecturer Task: Submit Marks to HOD
1. Login as lecturer
2. My Courses → Course Card
3. Enter Marks → Enter all scores
4. Click "Submit to HOD" button (in enter_marks.php)
5. Status changes from Draft to Submitted

### Lecturer Task: Review Class Performance
1. Login as lecturer
2. My Courses → Course Card
3. Click "Analytics" button
4. See grade distribution and statistics

---

## 🔒 Important Notes

### Data Security
- All marks are protected by HOD approval workflow
- Attendance is tied to specific date and course
- Student data is role-restricted (students only see own data)
- Lecturers can only access their own courses

### Status Workflow (Marks)
1. **Draft** - Entered but not submitted
2. **Submitted** - Sent to HOD for review
3. **Approved** - HOD has reviewed and approved
4. **Published** - Students can now see results
5. **Rejected** - HOD sent back for corrections

---

## 🆘 Troubleshooting

### Issue: "Attendance table not found" error
**Solution:**
1. Navigate to: `/setup_attendance.php`
2. Click "Setup" (if button shows)
3. Refresh page with attendance feature

### Issue: Can't see new features
**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Logout and login again
3. Make sure you're using correct user role

### Issue: Grades won't save
**Solution:**
1. Make sure all required fields are filled
2. Check CA is 0-30 and Exam is 0-70
3. Try in different browser
4. Check database connection

### Issue: Page shows "Access denied"
**Solution:**
1. Verify you're logged in with correct role
2. Students should login as student
3. Lecturers should login as lecturer
4. Check URL - verify you have permission for that page

---

## 📞 Support

If you encounter any issues:
1. Check the troubleshooting section above
2. Look at browser console for errors (F12)
3. Check PHP error logs in XAMPP
4. Contact system administrator

---

## ✨ Tips for Best Experience

1. **Use Modern Browser**: Chrome, Firefox, Edge, Safari
2. **Enable JavaScript**: Required for all features
3. **Keep Browser Updated**: Security and compatibility
4. **Clear Cache Monthly**: Avoid stale data issues
5. **Bookmark Common Pages**: Quick access to features

---

## 📈 Next Steps After Testing

Once you've tested the new features:
1. Share feedback on what works well
2. Report any bugs or issues
3. Suggest improvements
4. Plan for advanced features (announcements, messaging, etc.)

---

**Happy Learning & Teaching!**

For detailed feature documentation, see: [FEATURES_GUIDE.md](FEATURES_GUIDE.md)

# SwiftGrade Student Management System

<p align="center">
	<img src="https://readme-typing-svg.herokuapp.com?font=Orbitron&weight=700&size=30&duration=2600&pause=700&color=7CFFB2&center=true&vCenter=true&width=980&lines=SwiftGrade+Student+Management+System;Neon-Powered+Academic+Results+Portal;Fast+Secure+Automated+Role-Based+Workflows" alt="SwiftGrade animated headline" />
</p>

<p align="center">
	<img src="https://capsule-render.vercel.app/api?type=waving&color=0:0A7D34,100:1E9E63&height=90&section=header&reversal=false&animation=fadeIn" alt="SwiftGrade animated divider" />
</p>

<p align="center">
	<img src="https://img.shields.io/badge/CYBER%20PORTAL-Academic%20Results-082F49?style=for-the-badge&labelColor=0B6B43&color=7CFFB2" alt="Cyber Portal" />
	<img src="https://img.shields.io/badge/WORKFLOW-Draft%20to%20Publish-0F766E?style=for-the-badge&labelColor=0A7D34&color=DDFE6E" alt="Workflow" />
	<img src="https://img.shields.io/badge/SECURITY-Role%20Based%20Access-1D4ED8?style=for-the-badge&labelColor=0F172A&color=F5C451" alt="Security" />
</p>

<p align="center">
	<img src="https://capsule-render.vercel.app/api?type=rect&color=0:061A40,50:0A7D34,100:F5C451&height=130&section=header&text=SwiftGrade%20Experience&fontSize=38&fontColor=ffffff&animation=blinking" alt="SwiftGrade Experience Banner" />
</p>

<p align="center">
	<img src="https://capsule-render.vercel.app/api?type=soft&color=0:0F172A,50:0A7D34,100:22C55E&height=90&section=header&text=SCI-FI%20INTERFACE%20.%20LIVE%20ANALYTICS%20.%20SMART%20WORKFLOW&fontSize=22&fontColor=E2FFE9&animation=fadeIn" alt="SwiftGrade sci-fi strip" />
</p>

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-10.4+-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production%20Ready-0A7D34)

An enterprise-style, role-based result processing and student records platform for tertiary institutions. It supports complete result workflows from score entry to review, publishing, transcript generation, and student access.

> SwiftGrade blends a clean academic interface with a futuristic, dashboard-driven experience for administrators, HODs, lecturers, and students.

## Live Modules

- Admin management console
- HOD review and approval workspace
- Lecturer mark entry and upload flow
- Student result and transcript portal
- Attendance and academic session management
- Course, department, and institution administration

## Core Capabilities

- Multi-role authentication (`admin`, `hod`, `lecturer`, `student`)
- Institution-aware account and access routing
- End-to-end grading workflow:
	- Draft results
	- Lecturer submission
	- HOD approval/rejection
	- Final publish
- Automated grading and GPA/CGPA calculations
- Attendance tracking with semester and course mapping
- Transcript and result slip generation (PDF)
- Audit trail logging for operational accountability

## Tech Stack

- Backend: PHP (procedural + modular includes)
- Database: MariaDB / MySQL
- Frontend: HTML, CSS, Bootstrap, JavaScript
- PDF: Dompdf
- Mail/Utility packages: Composer ecosystem
- Deployment target: XAMPP / Apache (Windows-friendly)

## Project Structure

```text
Student-Management-System/
|-- admin/                      # Admin panel pages
|-- hod/                        # HOD workflow pages
|-- lecturer/                   # Lecturer workflow pages
|-- student/                    # Student-facing pages
|-- includes/                   # Shared config/auth/functions
|-- database/                   # SQL schema and full dump
|-- assets/                     # CSS, images, logos
|-- install.bat                 # One-click Windows installer
|-- setup_database.php          # Browser-based setup fallback
|-- db.php                      # Primary DB connection
```

## One-Click Installation (Client Setup)

### Requirements

- Windows OS
- XAMPP (Apache + MySQL/MariaDB)
- PHP 8+
- Composer (optional, if dependencies are missing)

### Quick Install Steps

1. Start Apache and MySQL in XAMPP.
2. Place this project in `htdocs`.
3. Double-click `install.bat`.
4. Enter your MySQL host/user/password when prompted.
5. Wait for import to complete.
6. Open `http://localhost/Student-Management-System/`.

The installer imports the full live dataset from:

- `database/lascohet_full_dump.sql`

## Database Model

Main tables include:

- `institutions`
- `departments`
- `programs`
- `users`
- `students`
- `courses`
- `course_assignments`
- `course_registrations`
- `academic_sessions`
- `semesters`
- `result_batches`
- `results`
- `attendance`
- `grading_scale`
- `audit_trail`

## Architecture Snapshot

```mermaid
flowchart LR
	A[Login Layer] --> B[Role Router]
	B --> C[Admin Module]
	B --> D[HOD Module]
	B --> E[Lecturer Module]
	B --> F[Student Module]
	C --> G[(MySQL Database)]
	D --> G
	E --> G
	F --> G
	G --> H[Reports/PDF Transcript]
```

## Screenshots (Graphics)

These are the exact showcase screens arranged in your requested order.

### 1. Landing Page
<p align="center">
	<img src="screenshots/Screenshot%20(236).png" alt="SwiftGrade Landing" width="100%" />
</p>

### 2. Lecturer Dashboard Workspace
<p align="center">
	<img src="screenshots/Screenshot%20(433).png" alt="SwiftGrade Lecturer Dashboard" width="100%" />
</p>

### 3. Sign In Experience
<p align="center">
	<img src="screenshots/Screenshot%20(237).png" alt="SwiftGrade Sign In" width="100%" />
</p>

### 4. Student Dashboard Portal
<p align="center">
	<img src="screenshots/Screenshot%20(434).png" alt="SwiftGrade Student Dashboard" width="100%" />
</p>

<p align="center">
	<img src="https://capsule-render.vercel.app/api?type=waving&color=0:0A7D34,50:7CFFB2,100:F5C451&height=100&section=footer&animation=twinkling" alt="SwiftGrade animated footer divider" />
</p>

## Video Walkthroughs

Add your hosted video links below (YouTube, Loom, Drive, or GitHub Releases):

- System Overview Demo: `https://your-video-link-here`
- Admin + HOD Approval Flow: `https://your-video-link-here`
- Lecturer Upload + Publish Pipeline: `https://your-video-link-here`
- Student Portal + Transcript Demo: `https://your-video-link-here`

Tip: Keep each video between 2 to 5 minutes for client-friendly review.

## Security and Access Notes

- Password hashes are stored securely with PHP password hashing.
- Role checks and access guards are implemented across modules.
- Audit logging exists for sensitive operations.
- For production deployment, set strict MySQL credentials and disable debug pages.

## Recommended Client Handover Checklist

1. Confirm Apache and MySQL autostart on client machine.
2. Run `install.bat` and verify successful import.
3. Test login for all roles.
4. Open result publishing flow end-to-end.
5. Generate one transcript PDF.
6. Backup database after first successful run.

## License and Usage

Developed for academic and institutional management demonstration and deployment use.

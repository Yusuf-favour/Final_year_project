# Student Management System (SwiftGrade)
## Chapters Three, Four and Five Report Draft

Author note: This document is written as an editable project report draft for a final year submission. It is based on the current implementation in this repository and can be copied directly into Microsoft Word for formatting according to departmental style.

---

## Chapter Three: Methodology / System Analysis and Design

### 3.1 System Analysis
System analysis was carried out to understand how academic result processing currently works in tertiary institutions and how an improved web-based platform can support users with different responsibilities. The analysis focused on four core actors:
- Administrator
- Lecturer
- Head of Department (HOD)
- Student

The system under study is a PHP-MySQL web application with role-based access control, course registration, result entry, approval workflow, publication, and student-side GPA/CGPA access.

Methodology used:
- Requirement elicitation by process observation and workflow decomposition
- Data model analysis from implemented database schema
- Feature traceability from source files to user requirements
- Gap analysis between existing manual or semi-digital process and proposed automated process

The project follows a practical iterative methodology:
1. Analyze academic business rules (registration, grading, approval, publication).
2. Model entities and relationships in relational tables.
3. Implement role-based workflows.
4. Validate outputs (results display, GPA/CGPA, transcript/PDF export).
5. Improve based on defects (for example, automatic carry-over registration for failed courses).

### 3.2 Analysis of Existing System
The existing approach (before this system) is typically characterized by fragmented records and delayed approvals. In many schools, parts of the process are still handled using spreadsheets, paper files, and isolated desktop records.

Observed characteristics of existing systems:
- Student and course data distributed across multiple files.
- Result computation performed manually or in separate spreadsheets.
- No unified pipeline from lecturer entry to HOD approval to publication.
- Limited auditability of who changed what and when.
- Weak student visibility into current standing and cumulative performance.

From a technical perspective, a legacy portion of this repository also reflects older table structures (attendance, semester_marks, staff tables) in some modules, while newer SwiftGrade modules implement a more normalized schema and workflow-oriented processing. This provided useful insight into migration and compatibility constraints.

### 3.3 Problem of the Existing System
Major problems identified in the existing or legacy process include:

1. Data inconsistency
- Duplicate records and contradictory values can occur when data is maintained in multiple places.

2. Poor process control
- No strict stage-gating between lecturer submission, departmental review, and final publication.

3. Delay in result release
- Manual collation and review increases turnaround time.

4. Error-prone grade management
- Manual grade interpretation and GPA calculations can introduce arithmetic mistakes.

5. Limited traceability and accountability
- Difficult to identify user actions without a centralized audit trail.

6. Security weaknesses
- Weak role boundaries and inconsistent credential handling in older approaches.

7. Carry-over management issues
- Failed courses may not be consistently moved forward to subsequent semesters unless explicitly tracked.

### 3.4 Analysis of the Proposed System
The proposed system provides an integrated, web-based, role-driven academic processing platform. The implementation introduces:
- Central user authentication and session management
- Role-specific dashboards and protected routes
- Structured result workflow through result batches
- Automated grade and CGPA reporting
- Audit trail logging
- Institution-aware login support (for multi-institution operation)
- Automated carry-over registration logic for outstanding failed courses

Core workflow implemented:
1. Student registers courses for a selected semester.
2. Lecturer enters or uploads scores for assigned courses.
3. Result batch is submitted for HOD review.
4. HOD approves or rejects with reason.
5. Admin publishes approved results.
6. Published results become visible to students, with GPA/CGPA updates.

Process improvements over existing system:
- Enforced sequencing of result states (draft -> submitted -> hod_approved -> published)
- Fewer manual transformations across stages
- Better student transparency through semester-wise and cumulative views
- Automatic outstanding-course handling, reducing omission risk

### 3.5 System Design (Materials and Method)

#### 3.5.1 Materials
Hardware (minimum practical setup):
- Development laptop/desktop (Intel/AMD CPU, 4 GB RAM minimum; 8 GB recommended)
- Stable storage for database backup
- Local network/internet for browser access and updates

Software materials:
- PHP runtime (as bundled in XAMPP or equivalent)
- MySQL/MariaDB database server
- Apache web server
- Composer dependency manager
- Browser (Chrome/Edge/Firefox)
- Source code editor (VS Code)

Libraries/frameworks observed in project:
- Bootstrap 5 and Bootstrap Icons for user interface
- dompdf/dompdf for PDF output

#### 3.5.2 Method
The implementation aligns with modular procedural PHP architecture:
- Common includes for configuration, authentication, and utility functions
- Role directories for focused user workflows
- SQL schema-first design for deterministic data integrity
- Prepared statements for sensitive database operations
- Session-based user identity propagation

Design principles applied:
- Role segregation
- Referential integrity through foreign keys
- Usability via dashboard-driven navigation
- Security with CSRF tokens in state-changing forms
- Auditability through centralized event logging

### 3.6 Proposed System Architecture
The proposed architecture is a three-tier web architecture.

1. Presentation Layer
- Browser-based UI pages built with HTML, CSS, Bootstrap, JavaScript.
- Separate interfaces for admin, lecturer, HOD, and student.

2. Application Layer
- PHP scripts implement business rules: authentication, role authorization, course registration, result publishing, CGPA computation.
- Middleware-like include files handle session security and access checks.

3. Data Layer
- MySQL relational schema with normalized entities.
- Transactional tables for registrations, result batches, and results.
- Control tables (grading_scale, academic_sessions, semesters).

High-level architecture flow:
- Client request -> PHP controller/page -> Business logic -> MySQL query/update -> Response rendering.

Narrative architecture summary:
- The login module authenticates user credentials and institution context.
- Role checks route each authenticated actor to an authorized dashboard.
- Academic operations write to normalized tables with constraints.
- Publication status gates student visibility of results.
- Audit events preserve administrative accountability.

### 3.7 Database Design and Data Flow
Primary entities include:
- users
- departments
- programs
- students
- courses
- academic_sessions
- semesters
- course_assignments
- course_registrations
- result_batches
- results
- grading_scale
- audit_trail

Data flow highlights:
1. Identity flow
- users table stores credentials and role metadata.
- students table links student profile to users.

2. Academic structure flow
- departments -> programs -> courses
- academic_sessions -> semesters

3. Registration flow
- Student selects semester and courses.
- Entries stored in course_registrations.

4. Result workflow flow
- result_batches tracks state transitions.
- results stores per-student marks and grades.

5. Reporting flow
- Published results are aggregated for GPA/CGPA and transcript views.

### 3.8 Data Set / Data Collection (if applicable)
This is an information system project, not a machine learning model, so data collection is operational and transactional rather than dataset-label driven.

Data sources used:
- Primary operational data entered by users:
  - Student profile data
  - Course registration selections
  - Lecturer CA and exam scores
  - HOD approval/rejection decisions
- Secondary system data generated by application:
  - Computed totals, grades, grade points
  - GPA and CGPA aggregates
  - Audit trail logs

Data collection instrument (system context):
- Structured web forms
- Controlled dropdowns (session, semester, institution)
- Validated POST requests with CSRF token checks

Data quality controls:
- Referential constraints in MySQL
- Unique keys for duplicate prevention
- Role-based write restrictions
- Prepared statements to reduce malformed SQL risk

### 3.9 Performance Evaluation Metrics (if applicable)
Since this project is transactional, evaluation focuses on correctness, reliability, security, and usability rather than classification accuracy.

Recommended metrics:

1. Authentication success rate
- Percentage of valid users who can log in without error.
- Formula: Success Rate = (Successful Logins / Total Valid Login Attempts) x 100

2. Result publication turnaround time
- Time from lecturer submission to admin publication.
- Lower values indicate better process efficiency.

3. Registration integrity rate
- Percentage of course registrations with valid foreign-key links and no duplicates.

4. Grade computation consistency
- Match rate between manual verification and system-computed GPA/CGPA.
- Formula: Consistency = (Correct Computations / Samples Checked) x 100

5. Outstanding course propagation rate
- Percentage of failed courses correctly auto-carried to next eligible semester.

6. Security compliance checks
- CSRF-protected form ratio
- Role-guarded route ratio

7. User response latency (observational)
- Average page response for key pages: login, registration, results listing.

---

## Chapter Four: Implementation

### 4.1 System Requirement

#### 4.1.1 Functional Requirements
The system shall:
- Allow role-based login for admin, lecturer, HOD, and student.
- Maintain student records and academic structure data.
- Support semester-based course registration.
- Allow lecturers to submit results per assigned course.
- Allow HOD review and approval or rejection.
- Allow admin publication of approved results.
- Display published results and CGPA to students.
- Provide downloadable report output (PDF in supported module).
- Maintain audit records for critical actions.

#### 4.1.2 Non-Functional Requirements
- Security: session protection, role checks, CSRF token verification.
- Reliability: persistent relational storage with constraints.
- Maintainability: modular include files and role directories.
- Usability: responsive Bootstrap UI.
- Scalability: institution-aware login supports future expansion.

#### 4.1.3 Hardware Requirements
- CPU: Dual-core or better
- RAM: 4 GB minimum (8 GB recommended)
- Disk: At least 1 GB free for source, DB, and exports

#### 4.1.4 Software Requirements
- Operating system: Windows/Linux/macOS
- Web server stack: XAMPP/WAMP/LAMP (Apache + PHP + MySQL)
- PHP 8.x recommended
- MySQL/MariaDB
- Composer
- Modern web browser

### 4.2 Choice of Language
Primary choices:
- PHP for server-side logic
- SQL (MySQL) for data storage and retrieval
- HTML/CSS/JavaScript for front-end interface behavior
- Bootstrap 5 for responsive UI framework

### 4.3 Language Justification
1. PHP
- Fast for web prototyping and deployment in academic settings.
- Native support in common local server bundles (XAMPP).
- Mature ecosystem and straightforward server-rendered page flow.

2. MySQL
- Reliable relational model for normalized academic records.
- Supports constraints, joins, indexes, and transactional operations.

3. JavaScript + Bootstrap
- Enables dynamic interactions (dropdown loading, selection UI feedback).
- Improves usability and responsive layout with minimal custom overhead.

4. Composer-managed dependencies
- Facilitates package installation and reproducibility.

### 4.4 Language Implementation
Implementation in this repository reflects two tracks:
- Core SwiftGrade workflow modules (modern result processing flow)
- Legacy/compatibility modules retained for prior features and transition support

#### 4.4.1 Backend Implementation
- Configuration constants and DB connection are centralized.
- Authentication helper functions enforce role-based route access.
- CSRF functions are available for protected form submissions.
- Result publishing script enforces approved-status gate.
- Course registration script handles semester selection and carry-over logic.

#### 4.4.2 Database Implementation
- Schema uses foreign keys and unique constraints to reduce inconsistencies.
- result_batches acts as process control table for lifecycle management.
- results table stores marks and grade outcomes tied to batch and semester.

#### 4.4.3 Frontend Implementation
- Bootstrap components used for forms, cards, tables, and navigation.
- User-friendly dashboard experiences differ by role.
- Student results page provides per-semester and cumulative summaries.

#### 4.4.4 Security Implementation
- Session initialization with cookie hardening settings.
- Role guards before protected pages.
- CSRF token generation and verification in mutation endpoints.
- Prepared statements in key credential and data update paths.

### 4.5 Deployment and Runtime Configuration
Typical local deployment setup:
1. Place project folder in Apache web root.
2. Configure database in local config override or defaults.
3. Import schema and seed records.
4. Install dependencies with Composer.
5. Open login page and test role-based access.

Operational note:
- Some modules point to newer multi-institution schema assumptions. Consistent environment setup and migration scripts should be applied to avoid table mismatch.

### 4.6 Software Development and Testing Tools (if applicable)
Tools used:
- Visual Studio Code for coding and debugging
- XAMPP for Apache/PHP/MySQL local execution
- phpMyAdmin or SQL CLI for schema and data inspection
- Composer for dependency management
- Browser DevTools for client-side debugging

Libraries/frameworks:
- Bootstrap 5
- Bootstrap Icons
- dompdf/dompdf

Testing approach (practical):
- Unit-style verification of helper functions (where feasible)
- Integration testing across full role workflow
- Form validation testing (required fields, invalid inputs)
- Security testing (role access, CSRF mismatch behavior)
- Regression testing after logic changes (example: carry-over auto registration)

Sample test scenarios:
1. Valid login for each role and redirection correctness.
2. Lecturer submission transitions batch to submitted.
3. HOD approval transitions batch to hod_approved.
4. Admin publish transitions to published and unlocks student visibility.
5. Failed course appears in subsequent semester registration as outstanding.
6. GPA/CGPA displays expected values for known grade sets.

### 4.7 Results and Discussion (if applicable)
The implemented system demonstrates that a web-based academic workflow can significantly improve consistency, visibility, and control compared to fragmented manual methods.

Observed implementation outcomes:
- Role-based separation prevented unauthorized workflow actions.
- Batch status workflow improved process governance.
- Student-side result transparency improved with semester grouping and cumulative summaries.
- Audit trail capability improved accountability for administrative actions.
- Carry-over automation reduced risk of outstanding course omission.

Discussion of practical constraints:
- Coexistence of legacy and modern modules introduces migration complexity.
- Data model evolution (institution-aware login and schema changes) requires synchronized deployment scripts.
- Security posture is improved but can be strengthened further with stricter password reset controls and broader input validation review.

---

## Chapter Five: Conclusion and Recommendations

### 5.1 Conclusion
This project successfully implements a multi-role Student Management and Result Processing System using PHP and MySQL. It addresses key gaps found in manual and loosely integrated academic workflows by introducing structured process flow, role-based control, centralized data, and reporting support.

The system supports:
- Controlled result lifecycle from entry to publication
- Student-friendly result access with GPA/CGPA insights
- Administrative oversight with auditability
- Extensible institution-aware authentication

Overall, the project meets its core objective of delivering a practical, usable, and extensible academic processing platform suitable for tertiary institutions.

### 5.2 Recommendation
For production-level adoption, the following are recommended:

1. Security hardening
- Enforce strong password policy and reset token flow.
- Remove legacy plaintext-password compatibility where still present.
- Add rate-limiting and account lockout after repeated failed login attempts.

2. Data and migration consistency
- Consolidate legacy and modern schemas into one canonical migration path.
- Add automated migration/version tracking.

3. Testing and quality
- Add formal automated tests for critical workflows.
- Include load testing for high-volume result publication periods.

4. Infrastructure
- Introduce environment-based configuration and secure secret storage.
- Enable robust backup/restore and disaster recovery procedures.

5. Feature growth
- Add transcript verification QR code.
- Add analytics dashboards for retention and performance trends.
- Add notification system for result publication events.

### 5.3 Contribution to Knowledge
This work contributes to knowledge and practice in the following ways:

1. Workflow-governed result processing model
- Demonstrates a practical approval pipeline for academic records in a low-resource deployment environment.

2. Integrated role-based architecture in procedural PHP context
- Shows how role segregation, session controls, and relational constraints can be composed into a reliable institutional system.

3. Carry-over automation strategy
- Provides a directly applicable logic pattern for outstanding failed-course propagation across semesters.

4. Migration-aware design insight
- Highlights real-world coexistence of legacy and modern modules and the importance of transition-safe architecture.

---

## References (APA 7)

Bootstrap Team. (2024). Bootstrap (Version 5) [Computer software]. https://getbootstrap.com/

Dompdf Contributors. (2025). Dompdf [Computer software]. https://github.com/dompdf/dompdf

MySQL. (2025). MySQL 8.0 Reference Manual. Oracle. https://dev.mysql.com/doc/

Open Web Application Security Project. (2021). OWASP Top 10: The ten most critical web application security risks. https://owasp.org/www-project-top-ten/

PHP Documentation Group. (2025). PHP manual. https://www.php.net/docs.php

Rosenblatt, H. J. (2013). Systems analysis and design (10th ed.). Cengage Learning.

Sommerville, I. (2016). Software engineering (10th ed.). Pearson.

---

## Appendix

### Appendix A: Data Collection Instrument
The following instrument template was used conceptually for requirement gathering and process validation.

Section A: Respondent Profile
- Role (Admin/Lecturer/HOD/Student)
- Department
- Years of experience with current result process

Section B: Existing Process Assessment
- How are results currently captured?
- What are the frequent errors encountered?
- Average time from exam completion to result publication?
- How are carry-over courses tracked?

Section C: System Quality Expectations
- Required features (ranked)
- Security concerns
- Reporting and transcript needs

Section D: Post-Implementation Feedback
- Ease of use score (1-5)
- Speed improvement score (1-5)
- Confidence in result correctness (1-5)
- Suggestions for improvement

### Appendix B: Ethical Approval
Template text for institutional ethics/research committee submission:
- Project title
- Objective and scope
- Data categories collected
- Privacy and confidentiality measures
- Data retention and disposal plan
- Risk assessment and mitigation
- Principal investigator signature and date

### Appendix C: Consent Form
Suggested participant consent clauses:
- Purpose of study is clearly explained
- Participation is voluntary
- No personally harmful procedure involved
- Data will be anonymized for reporting
- Participant may withdraw at any time
- Signature, date, and contact details

### Appendix D: List of Units / Supporting Documents
Suggested supporting documents to attach in the final submission:
- Database schema file and migration scripts
- Sample seeded records (non-sensitive)
- Screenshots of each role dashboard
- Test case checklist and observed outputs
- Deployment setup guide
- Source code module map
- Change log of major fixes (including carry-over automation update)

---

## Optional Word Formatting Tips
To format this document quickly in Microsoft Word:
1. Apply Heading 1 style to chapter titles.
2. Apply Heading 2 style to section numbers.
3. Enable automatic table of contents from headings.
4. Set line spacing to 1.5 and justified alignment if required by your department.
5. Apply APA 7 formatting to references as instructed by your supervisor.

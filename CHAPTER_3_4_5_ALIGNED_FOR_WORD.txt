CHAPTER THREE
SYSTEM ANALYSIS AND DESIGN / METHODOLOGY

3.1 System Analysis
System analysis for this project was carried out to determine how student result activities are performed in practice, identify system weaknesses, and define the functional and security requirements of a modern secure result processing platform. The analysis was based on the implemented source code and database model of the Student Management System (SwiftGrade) and was guided by software engineering principles that emphasize requirement traceability, process control, and data integrity.

The analysis process considered the full academic result lifecycle: course registration, score entry, result verification, approval, publication, and student viewing. In addition to functional behavior, the analysis focused on sensitive controls such as access restriction, accountability, and confidentiality of student academic records. This approach was necessary because result processing is not only a computational activity but also a governance activity where each role has clearly defined responsibilities and limits.

The resulting analysis identified four primary user roles within the implemented system:
1. Administrator: manages users, controls publication, and oversees institutional configuration.
2. Lecturer: enters and updates course scores for assigned courses.
3. HOD: reviews lecturer submissions and approves or rejects result batches.
4. Student: registers courses and views only approved/published academic results.

From the implementation perspective, analysis of the code and schema showed that the system enforces role boundaries through session-aware authorization checks and supports workflow-state transitions through dedicated result batch statuses. These implementation choices indicate that the project is designed not as an isolated marks calculator, but as an institutional process platform where correctness, auditability, and approval hierarchy are central design objectives.

3.2 Analysis of Existing System
Before the proposed platform, existing systems in many tertiary institutions (and in older software approaches) generally consisted of either manual processing or fragmented semi-computerized workflows. In such environments, calculations may occur in spreadsheets, approvals may occur through paper signatures, and final publication may be delayed due to repeated movement between offices.

The practical characteristics of the existing system can be summarized as follows:
- Fragmented record keeping: student profiles, registration data, and scores are often spread across multiple files or operators.
- Inconsistent governance: there is usually no unified mechanism that enforces mandatory transitions from submission to approval and then publication.
- Low traceability: it can be difficult to determine who changed data, when the change occurred, and why the change was made.
- Delayed publication: result release is often slowed by manual consolidation and repeated verification cycles.
- Security insufficiency: some systems focus on automation but do not sufficiently enforce role-based boundaries or transactional accountability.

In addition, the reviewed implementation context shows a common transitional challenge in institutional software evolution: coexistence of legacy modules and newer structured modules. This often creates differences in table patterns, process assumptions, and deployment behavior unless deliberate harmonization is done.

3.3 Problem of the Existing System
The key problems identified in existing or weakly structured result-processing methods include the following:

1. Inaccuracy risk in manual or loosely controlled computation
When score processing is performed through disconnected files or repeated manual handling, arithmetic and transcription errors can occur. These errors may affect GPA/CGPA outcomes and student progression decisions.

2. Weak process governance
Without explicit workflow control, roles can overlap inappropriately, and submissions may be modified without proper review discipline. This can compromise result integrity and institutional trust.

3. Delays in result release
Manual file transfer and fragmented validation processes often delay publication. Delayed results negatively affect student planning for graduation, scholarship processing, clearance, and employment applications.

4. Limited accountability
Where audit logging is absent or incomplete, post-incident investigation becomes difficult. Institutions may struggle to prove compliance or establish responsibility when disputes arise.

5. Security vulnerabilities
Basic authentication without strict authorization, session discipline, and request-protection controls exposes sensitive records to unauthorized access and modification.

6. Outstanding course tracking problems
In some existing systems, failed courses are not consistently propagated to subsequent semesters, leading to omission of carry-over requirements and delayed student progression.

3.4 Analysis of the Proposed System
The proposed system is a secure, web-based, role-driven result processing platform that integrates functional operations with workflow governance and accountability controls. Unlike fragmented alternatives, the proposed system centralizes data and ties each critical action to role permissions and process state.

The analyzed proposed behavior includes:
- Central authentication and role-aware redirection.
- Role-restricted access to administrative and academic actions.
- Structured result lifecycle via batch statuses (draft, submitted, hod_approved, published, rejected).
- Student-side visibility limited to published results.
- Audit-capable event recording for sensitive operations.
- Semester-based course registration with support for carry-over handling.

A key improvement in the implemented logic is the automation of outstanding failed-course handling. Failed courses can be automatically registered into subsequent eligible semesters, reducing omission risk and improving continuity of academic recovery pathways. This directly addresses one of the major operational weaknesses of conventional systems where carry-over tracking depends heavily on manual follow-up.

Overall, the proposed system demonstrates process-driven automation rather than isolated data entry. It aligns computation, verification, and publication under enforceable institutional control.

3.5 System Design (Materials and Method)

3.5.1 Materials
The materials used in developing and running the system are grouped as hardware and software resources.

Hardware materials:
- A personal computer for development and testing.
- Institutional workstation/server for deployment.
- Reliable storage medium for backups.
- Network access for browser-based usage and role collaboration.

Software materials:
- PHP runtime environment.
- MySQL/MariaDB relational database server.
- Apache web server (e.g., via XAMPP stack).
- Composer package manager.
- Bootstrap 5 and Bootstrap Icons for front-end responsiveness.
- dompdf library for PDF-related output in supported modules.
- Source editor and debugging tools (e.g., Visual Studio Code).

3.5.2 Method
The project adopted a structured software development approach consistent with system analysis and design methodology:

1. Requirement analysis
Functional and security requirements were identified from institutional workflow needs and mapped to user roles.

2. Data modeling
Core entities (users, students, courses, sessions, semesters, registrations, result batches, results) were designed in a normalized relational schema with keys and constraints.

3. Process modeling
Workflow transitions were represented explicitly through result batch statuses to prevent uncontrolled publication.

4. Interface design
Role dashboards and operational pages were designed using server-rendered PHP views with Bootstrap for usability.

5. Implementation
Business logic was implemented in modular PHP files with shared includes for configuration, authentication, and utility functions.

6. Verification and refinement
Role-based flow tests and edge-case checks were performed. Improvements were integrated, including auto-registration of outstanding failed courses.

The methodological focus was therefore not only code completion but also institutional workflow integrity and data-governance reliability.

3.6 Proposed System Architecture
The architecture of the proposed system follows a three-tier web application model:

1. Presentation Layer (Client/User Interface)
This layer includes browser-facing pages for login, dashboards, registration forms, result tables, and administrative controls. It is built using HTML, CSS, JavaScript, and Bootstrap-based components.

2. Application Layer (Business Logic)
This layer is implemented in PHP and contains:
- Authentication and authorization logic.
- CSRF validation for protected requests.
- Registration and result processing rules.
- Workflow state control for submission, approval, and publication.
- GPA/CGPA computation and result rendering rules.

3. Data Layer (Relational Database)
The MySQL layer stores and enforces academic records through normalized tables and foreign key relationships. Key constraints prevent duplicate or invalid linkage and support consistency across registration, assignment, and result entities.

Architectural interaction flow:
- User request from browser -> PHP endpoint/page -> role validation and business rules -> SQL query/update -> rendered response.

This architecture ensures maintainability, role separation, and stronger process integrity compared to ad-hoc or file-based alternatives.

3.8 Data Set / Data Collection (if applicable)
This project is an information system development project, so its data context is operational rather than machine-learning-oriented. Data is collected as structured academic transactions generated through authenticated user interaction.

Data categories collected and processed include:
- User credentials and role metadata.
- Student identity and academic profile records.
- Department, program, course, and semester configuration.
- Course registration selections.
- CA and examination score entries.
- Grade, grade point, and remark outputs.
- Result workflow decisions (submission, approval, publication).
- Audit-related action records.

Data collection mechanism:
- Controlled web forms with validation.
- Session-protected access paths.
- Prepared SQL statements in critical operations.
- Referential constraints at the database level.

Data quality controls:
- Unique keys to prevent duplicates.
- Foreign keys to maintain relational correctness.
- Role-restricted mutation endpoints.
- Workflow-state checks before publication.

The data collection strategy is therefore transactional, rule-governed, and institution-oriented.

3.9 Performance Evaluation Metrics (if applicable)
Because the project is a secure transaction system, evaluation emphasizes correctness, reliability, security, and process efficiency rather than predictive-model accuracy.

Recommended evaluation metrics are:

1. Login correctness rate
Definition: proportion of valid credentials that authenticate successfully under correct role and institution context.
Formula:
Login Correctness Rate (%) = (Successful Valid Logins / Total Valid Login Attempts) x 100

2. Workflow compliance rate
Definition: proportion of result records that pass through required states before student visibility.
Formula:
Workflow Compliance (%) = (Published Results with Full Approval Path / Total Published Results) x 100

3. Registration integrity rate
Definition: percentage of registration entries with valid student-course-semester linkage and no duplicate key violations.

4. Computation consistency rate
Definition: percentage match between system-computed GPA/CGPA and independent verification samples.
Formula:
Computation Consistency (%) = (Verified Correct Computations / Total Sampled Computations) x 100

5. Carry-over propagation success rate
Definition: proportion of failed/outstanding courses correctly auto-registered into eligible subsequent semesters.

6. Audit traceability coverage
Definition: proportion of sensitive operations that generate usable audit records.
Formula:
Traceability Coverage (%) = (Sensitive Actions with Audit Record / Total Sensitive Actions) x 100

7. Response-time observations
Definition: average server response times for high-use workflows (login, registration, result retrieval, publication).

When measured together, these metrics provide a balanced institutional view of effectiveness, governance, and security quality.


CHAPTER FOUR
IMPLEMENTATION

4.1 System Requirement
System requirements are grouped into functional and non-functional classes.

4.1.1 Functional Requirements
The implemented system shall:
- Authenticate users by valid credentials and role context.
- Redirect authenticated users to role-specific dashboards.
- Allow students to register semester courses.
- Allow lecturers to enter/upload scores for assigned courses.
- Allow HOD to review and approve/reject submitted result batches.
- Allow administrators to publish only approved result batches.
- Allow students to view only published results.
- Compute and display semester GPA and cumulative CGPA.
- Maintain action logs for traceability.
- Support institution-aware selection in login flow.

4.1.2 Non-Functional Requirements
- Security: session hardening, role checks, and CSRF controls.
- Reliability: relational integrity and constrained updates.
- Maintainability: modular include structure and role-focused directories.
- Usability: responsive interface for desktop and mobile browsers.
- Extensibility: institutional growth and schema evolution support.

4.1.3 Hardware Requirement
- Processor: dual-core minimum.
- RAM: minimum 4 GB (8 GB recommended).
- Storage: sufficient space for source code, database, logs, and document exports.
- Network: local or internet connectivity depending on deployment model.

4.1.4 Software Requirement
- Operating system: Windows/Linux/macOS.
- Web stack: Apache + PHP + MySQL (e.g., XAMPP).
- Composer for dependency installation.
- Browser supporting modern HTML/CSS/JS features.

4.2 Choice of Language
The project uses:
- PHP for server-side processing.
- SQL (MySQL dialect) for persistent relational data management.
- JavaScript for dynamic client-side interactions.
- HTML/CSS with Bootstrap for front-end rendering and responsiveness.

4.3 Language Justification
1. PHP
PHP is suitable for rapid development of server-rendered institutional systems. It integrates naturally with Apache/MySQL environments commonly available in educational deployments and allows direct implementation of role-aware web workflows.

2. MySQL
MySQL provides mature relational capabilities including foreign keys, unique constraints, indexing, transactions, and join performance needed for academic records.

3. JavaScript and Bootstrap
JavaScript supports interaction quality (dynamic form behavior, selection handling), while Bootstrap standardizes responsive interface behavior and improves usability across devices.

4. Composer and package ecosystem
Composer enables dependency management and reproducible setup. In this project, it supports integration of external utilities such as dompdf.

Collectively, these technologies provide a practical balance of accessibility, maintainability, and deployment feasibility for tertiary institutions.

4.4 Language Implementation
The implementation combines shared infrastructure modules and role-specific operational modules.

4.4.1 Core configuration and session security
A shared configuration layer initializes database connectivity, application constants, and session parameters. Session cookie hardening and strict mode support improve baseline request security.

4.4.2 Authentication and authorization implementation
Authentication logic validates user credentials (including role and institution context). Authorization is implemented through helper guards that enforce route-level role restrictions. Unauthorized users are redirected away from restricted pages.

4.4.3 Workflow implementation
The result-processing lifecycle is managed through status transitions in result batch records. This ensures publication can only happen after defined approval milestones. This design minimizes accidental exposure of unapproved results.

4.4.4 Registration and carry-over implementation
Course registration is semester-based. The system supports carry-over behavior by ensuring failed/outstanding courses can be automatically propagated to subsequent eligible semesters and included in registration visibility. This improves compliance with academic progression rules.

4.4.5 Student result presentation implementation
Student result pages fetch only published entries and compute GPA/CGPA summaries from validated result records and course credit units. Grouped semester display improves readability and progression tracking.

4.4.6 Security controls implementation
State-changing forms use CSRF token validation. Prepared statements are used in sensitive data operations to reduce query manipulation risks. Session identity is regenerated on successful login to reduce fixation exposure.

4.6 Software Development and Testing Tools (if applicable)
Development and testing tools used include:
- Visual Studio Code for coding and debugging.
- XAMPP stack for local Apache/PHP/MySQL runtime.
- Composer for package management.
- Browser developer tools for frontend testing.
- SQL query tools (phpMyAdmin/CLI) for schema and data inspection.

Testing strategy applied:
1. Functional workflow testing
- Validate each role’s core operations and restrictions.

2. Integration testing
- Validate cross-role workflow: lecturer submission -> HOD approval -> admin publication -> student visibility.

3. Data integrity testing
- Verify foreign-key relationships and duplicate-prevention constraints.

4. Security testing
- Verify role-denied paths.
- Verify CSRF rejection on invalid token requests.

5. Regression testing
- Re-test major flows after logic updates (including outstanding course auto-registration behavior).

6. Output verification
- Confirm GPA/CGPA calculations against sample manual checks.

4.7 Results and Discussion (if applicable)
The implementation outcomes indicate that the proposed architecture substantially improves result process control, visibility, and accountability.

Observed outcomes:
- Better process discipline: publication is controlled by approval-state checks.
- Improved user separation: each role sees and executes only designated actions.
- Reliable student visibility: only published records appear in student result views.
- Enhanced traceability: key operations can be tracked through audit-related logging.
- Reduced carry-over omission: failed courses can be auto-propagated to next semester.

Discussion:
The project demonstrates that combining workflow-state governance with role-based access control yields stronger institutional confidence than standalone automation tools. Rather than only speeding calculations, the platform formalizes responsibility boundaries and publication accountability.

However, the discussion also reveals practical transition challenges where legacy modules coexist with newer process-driven modules. Future harmonization efforts should consolidate schema assumptions and remove obsolete compatibility paths to further improve maintainability and security posture.


CHAPTER FIVE
CONCLUSION AND RECOMMENDATIONS

5.1 Conclusion
This project has successfully developed and implemented a secure web-based student result processing system that addresses major weaknesses observed in manual and fragmented alternatives. The system is not limited to score computation; it establishes an institutional workflow model where result data passes through controlled phases before publication.

The implementation demonstrates that:
- Role-based architecture can protect academic operations from unauthorized activity.
- Workflow-state control improves governance and publication reliability.
- Centralized relational modeling improves consistency and reporting quality.
- Student-facing transparency can be improved while preserving institutional controls.
- Security-aware development (session controls, request validation, guarded routes) can be integrated in practical academic software.

The system therefore meets its fundamental objective of delivering an efficient, secure, and accountable result-processing platform for tertiary institutions.

5.2 Recommendation
To strengthen institutional deployment and long-term quality, the following recommendations are made:

1. Security enhancement recommendations
- Implement tokenized password reset workflows end-to-end.
- Enforce stronger password complexity and rotation policies.
- Add login throttling and account lockout protections.
- Expand security monitoring and anomaly alerts.

2. Architecture and data recommendations
- Unify legacy and modern modules into a single canonical schema path.
- Adopt migration versioning to standardize upgrades.
- Improve backup automation and tested recovery procedures.

3. Quality and testing recommendations
- Introduce automated test suites for critical workflows.
- Add performance testing for peak publication periods.
- Strengthen static analysis and coding-standard checks.

4. Governance and usability recommendations
- Add policy-configurable approval routes for institutions with multiple reviewers.
- Improve dashboard analytics for at-risk students and performance trends.
- Provide configurable notification channels for key workflow events.

5. Scalability recommendations
- Prepare for multi-institution scaling with stronger tenant isolation patterns.
- Introduce API-first integration support for external education platforms.

5.3 Contribution to Knowledge
The project contributes to knowledge in software engineering and educational information systems in the following ways:

1. Practical workflow-governed model
It provides a practical example of translating institutional approval policy into enforceable software states, showing how governance can be encoded into platform behavior.

2. Security-integrated result processing design
It demonstrates that confidentiality, integrity, and accountability can be addressed within a deployable academic processing platform, not just in conceptual models.

3. Role-based operational architecture
It contributes an implementable role-separation pattern for tertiary result systems where each actor has constrained authority and traceable actions.

4. Outstanding-course propagation mechanism
It presents an applied solution for automated carry-over registration of failed courses, reducing omission risk in academic progression management.

5. Migration-aware development insight
It highlights real-world transition issues between legacy and modern modules and offers a basis for future harmonization frameworks in institutional systems.


REFERENCES (APA 7)

Bootstrap Team. (2024). Bootstrap (Version 5) [Computer software]. https://getbootstrap.com/

Dompdf Contributors. (2025). Dompdf [Computer software]. https://github.com/dompdf/dompdf

MySQL. (2025). MySQL 8.0 reference manual. Oracle. https://dev.mysql.com/doc/

Open Web Application Security Project. (2021). OWASP Top 10: The ten most critical web application security risks. https://owasp.org/www-project-top-ten/

PHP Documentation Group. (2025). PHP manual. https://www.php.net/docs.php

Rosenblatt, H. J. (2013). Systems analysis and design (10th ed.). Cengage Learning.

Sommerville, I. (2016). Software engineering (10th ed.). Pearson.


APPENDIX

Data Collection Instrument
Section A: Respondent Information
- Role in institution
- Department or unit
- Years of service

Section B: Existing Result Process
- Method currently used for score entry and compilation
- Average result publication timeline
- Frequent operational and security issues observed

Section C: Proposed System Assessment
- Perceived usability
- Perceived reliability
- Perceived security and trust level
- Suggestions for improvement

Ethical Approval
Include institutional ethical/research approval details where required:
- Project title
- Applicant details
- Supervisor details
- Data type and privacy statement
- Approval reference number and date

Consent Form
Include a participant consent template with:
- Study purpose
- Voluntary participation declaration
- Data confidentiality statement
- Withdrawal right statement
- Signature and date fields

List of Units / Supporting Documents
- Database schema and setup scripts
- Module screenshots (Admin, Lecturer, HOD, Student)
- Test scenarios and observed outputs
- Deployment notes
- Change log and enhancement notes
- Any institutional authorization letters

END OF CHAPTER THREE, FOUR, AND FIVE DRAFT

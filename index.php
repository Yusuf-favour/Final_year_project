<?php
/* ================================================================
   SwiftGrade – Landing Page
   ================================================================ */
session_start();
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/auth.php';
    header('Location: ' . dashboardURL());
    exit();
}
$base = '/Student-Management-System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SwiftGrade – Result Processing System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/swiftgrade.css" rel="stylesheet">
</head>
<body>

<!-- ========== NAVIGATION ========== -->
<nav class="landing-nav navbar navbar-expand-lg" id="landingNav">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>/">
            <i class="bi bi-mortarboard-fill me-1"></i>Swift<span>Grade</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#departments">Departments</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sg px-4" href="swiftgrade_login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-lightning-fill"></i> Fast &bull; Secure &bull; Reliable
                </div>
                <h1 class="hero-title">
                    <span class="text-gold">SwiftGrade</span><br>
                    <span style="font-size:2.2rem; font-weight:600; color:#222;">Revolutionizing Academic Results</span>
                </h1>
                <p class="lead" style="font-size:1.35rem; color:#555; margin-top:0.5rem;">
                    Experience the future of result processing:<br>
                    <span style="color:#bfa046; font-weight:500;">Fast. Secure. Effortless.</span>
                </p>
                <p class="hero-subtitle">
                    SwiftGrade streamlines academic result management with role-based access,
                    automated GPA computation, multi-level approval workflows, and complete audit trails.
                </p>
                <div class="hero-btns">
                    <a href="swiftgrade_login.php" class="btn btn-gold btn-lg px-4 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Get Started
                    </a>
                    <a href="#features" class="btn btn-sg-outline btn-lg px-4 py-2">
                        <i class="bi bi-play-circle me-2"></i>Learn More
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <strong>5+</strong>
                        <span>Departments</span>
                    </div>
                    <div class="hero-stat-item">
                        <strong>1,000+</strong>
                        <span>Students</span>
                    </div>
                    <div class="hero-stat-item">
                        <strong>99.9%</strong>
                        <span>Uptime</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-img-wrapper">
                    <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?w=700&h=480&fit=crop&q=80"
                         alt="Students studying health technology"
                         loading="eager">

                    <!-- Floating Cards -->
                    <div class="hero-float-card card-1">
                        <div class="fc-icon fc-green"><i class="bi bi-graph-up-arrow"></i></div>
                        <div>
                            <div class="fc-label">Average GPA</div>
                            <div class="fc-value">3.72</div>
                        </div>
                    </div>
                    <div class="hero-float-card card-2">
                        <div class="fc-icon fc-gold"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="fc-label">Results Published</div>
                            <div class="fc-value">2,451</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FEATURES ========== -->
<section class="features-section" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">Why SwiftGrade</span>
            <h2 class="section-title">Powerful Features for Modern Institutions</h2>
            <p class="section-subtitle mx-auto">
                Everything you need to manage academic results with precision, transparency, and speed.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-green"><i class="bi bi-people-fill"></i></div>
                    <h5>Role-Based Access</h5>
                    <p>Admin, Lecturer, HOD, and Student portals — each with tailored permissions and workflows.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-gold"><i class="bi bi-calculator-fill"></i></div>
                    <h5>Automatic GPA</h5>
                    <p>Real-time GPA and CGPA computation with institutional grading scales and academic standing.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-blue"><i class="bi bi-check2-all"></i></div>
                    <h5>Approval Workflow</h5>
                    <p>Multi-level result approval: Lecturer uploads → HOD reviews → Admin publishes.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-purple"><i class="bi bi-shield-lock-fill"></i></div>
                    <h5>Audit Trail</h5>
                    <p>Complete activity logging with timestamps for accountability and compliance.</p>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-blue"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                    <h5>PDF Transcripts</h5>
                    <p>Generate and download official academic transcripts as beautifully formatted PDFs.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-green"><i class="bi bi-speedometer2"></i></div>
                    <h5>Live Dashboards</h5>
                    <p>At-a-glance analytics with real-time statistics for every user role.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-gold"><i class="bi bi-search"></i></div>
                    <h5>Quick Search</h5>
                    <p>Find any student, course, or result instantly with smart search filters.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon fi-purple"><i class="bi bi-phone-fill"></i></div>
                    <h5>Fully Responsive</h5>
                    <p>Works seamlessly on desktops, tablets, and mobile phones — access anywhere.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== DEPARTMENTS ========== -->
<section class="depts-section" id="departments">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">Academic Departments</span>
            <h2 class="section-title">Serving Leading Programs</h2>
            <p class="section-subtitle mx-auto">
                SwiftGrade covers result processing across all departments in the institution.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1579154204601-01588f351e67?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">SLT</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Science Laboratory Technology</h6>
                        <p>Training skilled laboratory scientists for industry and research institutions.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">PHARM</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Pharmaceutical Technology</h6>
                        <p>Developing competent pharmacy technicians for healthcare delivery.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1551076805-e1869033e561?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">HIM</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Health Information Management</h6>
                        <p>Managing health data systems for improved patient care and outcomes.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">EHT</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Environmental Health Technology</h6>
                        <p>Promoting public health through environmental monitoring and sanitation.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">CHE</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Community Health Extension</h6>
                        <p>Building primary healthcare capacity in communities across Nigeria.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dept-card">
                    <div class="dept-card-img" style="background-image:url('https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=600&h=360&fit=crop&q=80')">
                        <span class="dept-badge">DT</span>
                    </div>
                    <div class="dept-card-body">
                        <h6>Dental Technology</h6>
                        <p>Crafting dental prosthetics and appliances for oral healthcare.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section class="how-section" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">Workflow</span>
            <h2 class="section-title">How SwiftGrade Works</h2>
            <p class="section-subtitle mx-auto">
                A streamlined 4-step process from result entry to student access.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Lecturer Uploads</h5>
                    <p>Lecturers enter scores for their assigned courses through a simple interface.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>HOD Reviews</h5>
                    <p>Department heads review uploaded results and approve or return for corrections.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Admin Publishes</h5>
                    <p>The admin performs final verification and publishes results institution-wide.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5>Students View</h5>
                    <p>Students log in to view their results, GPA, CGPA and download transcripts.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== CTA ========== -->
<section class="cta-section rounded-section">
    <div class="container text-center position-relative" style="z-index:2">
        <h2 class="mb-3">Ready to Modernize Result Processing?</h2>
        <p class="mb-4 mx-auto" style="max-width:540px">
            Join SwiftGrade — the fast, secure, and transparent way to manage academic results.
        </p>
        <a href="swiftgrade_login.php" class="btn btn-white btn-lg px-5 py-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In Now
        </a>
    </div>
</section>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sticky nav scroll effect
const nav = document.getElementById('landingNav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 50);
});
// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const t = document.querySelector(a.getAttribute('href'));
        if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>
</body>
</html>

<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'lecturer';
$_SESSION['full_name'] = 'Mr Sam Okeke';

define('BASE_URL', 'http://localhost');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Header Test</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
<style>
body { margin: 0; padding: 0; background: #f5f5f5; }
.demo-content { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }
.demo-card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem; }
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="demo-content">
<div class="demo-card">
<h2>Modern Navbar Preview</h2>
<p>The header above shows the new modern design with:</p>
<ul>
<li>✓ Green gradient background (SwiftGrade branding)</li>
<li>✓ Horizontal layout with logo, system info, and user section</li>
<li>✓ Responsive design for mobile/tablet</li>
<li>✓ Logout button with icon</li>
<li>✓ Modern styling with shadows and animations</li>
</ul>
</div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>

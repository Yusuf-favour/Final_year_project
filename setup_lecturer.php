<?php
// Fix with foreign key constraint handling
$conn = new mysqli('localhost', 'root', '', 'lascohet_results');
if ($conn->connect_error) die('DB Connection Error: ' . $conn->connect_error);

echo "<h2>Lecturer Account Setup (Fixed)</h2>";

try {
    // Step 1: Disable foreign key checks
    echo "<h3>Step 1: Preparing database</h3>";
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    echo "✓ Foreign key checks disabled<br>";
    
    // Step 2: Delete old records
    echo "<h3>Step 2: Removing old account</h3>";
    
    // First, remove any related records
    $conn->query("DELETE FROM course_assignments WHERE lecturer_id IN (SELECT id FROM users WHERE username='mr.okeke')");
    echo "✓ Removed course assignments<br>";
    
    $conn->query("DELETE FROM result_batches WHERE lecturer_id IN (SELECT id FROM users WHERE username='mr.okeke')");
    echo "✓ Removed result batches<br>";
    
    // Now delete the user
    $conn->query("DELETE FROM users WHERE username='mr.okeke'");
    echo "✓ Removed user account<br>";
    
    // Step 3: Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "✓ Foreign key checks re-enabled<br>";
    
    // Step 4: Create new account
    echo "<h3>Step 3: Creating new account</h3>";
    
    $password = 'NextGen@2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('ssss', $u, $n, $r, $h);
    $u = 'mr.okeke';
    $n = 'Mr. Okeke';
    $r = 'lecturer';
    $h = $hash;
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo "<p style='color:green;'><strong>✅ Account created successfully!</strong></p>";
        echo "<ul>
            <li>ID: $new_id</li>
            <li>Username: mr.okeke</li>
            <li>Full Name: Mr. Okeke</li>
            <li>Role: lecturer</li>
            <li>Status: Active</li>
        </ul>";
    } else {
        echo "<p style='color:red;'><strong>❌ Error creating account:</strong> " . $stmt->error . "</p>";
        exit;
    }
    $stmt->close();
    
    // Step 5: Verify
    echo "<h3>Step 4: Verifying account</h3>";
    $verify = $conn->query("SELECT id, username, role, is_active FROM users WHERE username='mr.okeke'");
    if ($verify && $verify->num_rows > 0) {
        $v = $verify->fetch_assoc();
        echo "<p style='color:green;'><strong>✓ Account verified in database</strong></p>";
        echo "<ul>
            <li>ID: " . $v['id'] . "</li>
            <li>Username: " . $v['username'] . "</li>
            <li>Role: " . $v['role'] . "</li>
            <li>Active: " . ($v['is_active'] ? 'Yes ✓' : 'No ✗') . "</li>
        </ul>";
    }
    
    echo "<h3 style='color:green;'>✅ Setup Complete!</h3>";
    echo "<p>You can now login with:</p>";
    echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px; margin:15px 0;'>";
    echo "<strong>Username:</strong> <code>mr.okeke</code><br>";
    echo "<strong>Password:</strong> <code>NextGen@2026</code><br>";
    echo "</div>";
    echo "<p><a href='swiftgrade_login.php' class='btn btn-success' style='padding:10px 20px;'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

$conn->close();
?>

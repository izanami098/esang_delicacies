<?php
/**
 * Fix script for rider login issues
 * This will help you approve existing riders or create new test riders
 */

require_once 'app/views/_bootstrap.php';

echo "<h2>🔧 Rider Login Fix Tool</h2>";

try {
    $mysqli = db();
    
    echo "<h3>Current Status:</h3>";
    
    // Check modern riders table
    $result = $mysqli->query("SELECT COUNT(*) as approved_count FROM riders WHERE is_approved = 1");
    if ($result) {
        $count = $result->fetch_assoc();
        echo "<p><strong>Modern riders table:</strong> " . $count['approved_count'] . " approved rider(s)</p>";
    }
    
    // Check legacy RIDER table
    $result = $mysqli->query("SELECT COUNT(*) as verified_count FROM RIDER WHERE verified = 1");
    if ($result) {
        $count = $result->fetch_assoc();
        echo "<p><strong>Legacy RIDER table:</strong> " . $count['verified_count'] . " verified rider(s)</p>";
    }
    
    echo "<hr>";
    
    // Option 1: Approve all existing riders in modern table
    if (isset($_POST['approve_modern_riders'])) {
        echo "<h3>Approving all riders in modern table...</h3>";
        $result = $mysqli->query("UPDATE riders SET is_approved = 1 WHERE is_approved = 0");
        if ($result) {
            echo "<p style='color: green;'>✅ All riders in modern table have been approved!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $mysqli->error . "</p>";
        }
    }
    
    // Option 2: Verify all existing riders in legacy table
    if (isset($_POST['verify_legacy_riders'])) {
        echo "<h3>Verifying all riders in legacy table...</h3>";
        $result = $mysqli->query("UPDATE RIDER SET verified = 1 WHERE verified = 0");
        if ($result) {
            echo "<p style='color: green;'>✅ All riders in legacy table have been verified!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $mysqli->error . "</p>";
        }
    }
    
    // Option 3: Create a new test rider
    if (isset($_POST['create_test_rider'])) {
        echo "<h3>Creating new test rider...</h3>";
        
        $testEmail = 'testrider@esang.com';
        $testPassword = 'rider123';
        
        // Check if test rider exists
        $stmt = $mysqli->prepare("SELECT rider_id FROM riders WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param('s', $testEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo "<p style='color: orange;'>⚠️ Test rider already exists. Updating approval status...</p>";
                $updateStmt = $mysqli->prepare("UPDATE riders SET is_approved = 1 WHERE email = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param('s', $testEmail);
                    if ($updateStmt->execute()) {
                        echo "<p style='color: green;'>✅ Test rider has been approved!</p>";
                    }
                    $updateStmt->close();
                }
            } else {
                // Create new test rider
                $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
                $profileHash = 'rider_test_' . time();
                
                $insertStmt = $mysqli->prepare("INSERT INTO riders (name, email, phone, password_hash, profile_hash, is_approved, status) VALUES (?, ?, ?, ?, ?, 1, 'offline')");
                if ($insertStmt) {
                    $insertStmt->bind_param('sssss', 
                        $name = 'Test Rider',
                        $testEmail,
                        $phone = '09123456789',
                        $hashedPassword,
                        $profileHash
                    );
                    
                    if ($insertStmt->execute()) {
                        echo "<p style='color: green;'>✅ Test rider created and approved!</p>";
                        echo "<p><strong>Email:</strong> $testEmail</p>";
                        echo "<p><strong>Password:</strong> $testPassword</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to create test rider: " . $insertStmt->error . "</p>";
                    }
                    $insertStmt->close();
                }
            }
            $stmt->close();
        }
    }
    
    // Show available riders to test with
    echo "<h3>Available Riders for Testing:</h3>";
    
    // Modern riders table
    $result = $mysqli->query("SELECT rider_id, name, email, is_approved FROM riders WHERE is_approved = 1");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Modern Riders Table (approved riders):</h4>";
        echo "<ul>";
        while ($rider = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($rider['name']) . "</strong> - " . htmlspecialchars($rider['email']) . "</li>";
        }
        echo "</ul>";
        echo "<p style='color: blue;'>💡 For modern riders, you need to know their actual passwords (likely hashed)</p>";
    }
    
    // Legacy RIDER table
    $result = $mysqli->query("SELECT empId, name, email FROM RIDER WHERE verified = 1");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Legacy RIDER Table (verified riders):</h4>";
        echo "<ul>";
        while ($rider = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($rider['name']) . "</strong> - " . htmlspecialchars($rider['email']) . "</li>";
        }
        echo "</ul>";
        echo "<p style='color: blue;'>💡 For legacy riders, try the passwords you used during registration</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>🛠️ Fix Options:</h3>

<form method="POST" style="margin-bottom: 20px;">
    <h4>Option 1: Approve All Modern Riders</h4>
    <p>This will approve all riders in the modern 'riders' table so they can log in.</p>
    <button type="submit" name="approve_modern_riders" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;">
        Approve All Modern Riders
    </button>
</form>

<form method="POST" style="margin-bottom: 20px;">
    <h4>Option 2: Verify All Legacy Riders</h4>
    <p>This will verify all riders in the legacy 'RIDER' table so they can log in.</p>
    <button type="submit" name="verify_legacy_riders" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
        Verify All Legacy Riders
    </button>
</form>

<form method="POST" style="margin-bottom: 20px;">
    <h4>Option 3: Create Test Rider</h4>
    <p>This will create a new test rider account that you can use for testing.</p>
    <p><strong>Credentials:</strong> testrider@esang.com / rider123</p>
    <button type="submit" name="create_test_rider" style="padding: 10px 20px; background: #ffc107; color: black; border: none; cursor: pointer;">
        Create Test Rider
    </button>
</form>

<hr>
<p><strong>After fixing:</strong> <a href="app/views/auth/LogIn.php" target="_blank">Test the login form</a></p>
<p><strong>Debug again:</strong> <a href="debug_rider_login.php" target="_blank">Run debug script</a></p>
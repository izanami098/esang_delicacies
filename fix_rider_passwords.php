<?php
/**
 * Fix script to add passwords to riders who have NULL password_hash
 */

require_once 'app/views/_bootstrap.php';

echo "<h2>🔐 Fix Rider Passwords</h2>";

try {
    $mysqli = db();
    
    // Check riders with NULL passwords
    $result = $mysqli->query("SELECT rider_id, name, email, password_hash FROM riders WHERE password_hash IS NULL OR password_hash = ''");
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Riders with Missing Passwords:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password Status</th></tr>";
        
        $ridersWithoutPassword = [];
        while ($rider = $result->fetch_assoc()) {
            $ridersWithoutPassword[] = $rider;
            echo "<tr>";
            echo "<td>" . $rider['rider_id'] . "</td>";
            echo "<td>" . htmlspecialchars($rider['name']) . "</td>";
            echo "<td>" . htmlspecialchars($rider['email']) . "</td>";
            echo "<td style='color: red;'>❌ Missing</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        
        // Fix individual rider passwords
        if (isset($_POST['fix_rider_password']) && isset($_POST['rider_id']) && isset($_POST['new_password'])) {
            $riderId = (int)$_POST['rider_id'];
            $newPassword = $_POST['new_password'];
            
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $mysqli->prepare("UPDATE riders SET password_hash = ? WHERE rider_id = ?");
                if ($stmt) {
                    $stmt->bind_param('si', $hashedPassword, $riderId);
                    if ($stmt->execute()) {
                        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                        echo "<h4>✅ Password Updated Successfully!</h4>";
                        echo "<p>Rider ID: $riderId</p>";
                        echo "<p>New Password: " . htmlspecialchars($newPassword) . "</p>";
                        echo "<p>You can now login with this password.</p>";
                        echo "</div>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update password: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                }
            } else {
                echo "<p style='color: red;'>❌ Password cannot be empty</p>";
            }
        }
        
        // Fix all riders with default password
        if (isset($_POST['fix_all_passwords'])) {
            $defaultPassword = $_POST['default_password'] ?? 'rider123';
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            echo "<h3>🔧 Setting Default Password for All Riders...</h3>";
            
            $stmt = $mysqli->prepare("UPDATE riders SET password_hash = ? WHERE password_hash IS NULL OR password_hash = ''");
            if ($stmt) {
                $stmt->bind_param('s', $hashedPassword);
                if ($stmt->execute()) {
                    $affected = $mysqli->affected_rows;
                    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>✅ All Rider Passwords Updated!</h4>";
                    echo "<p><strong>Riders affected:</strong> $affected</p>";
                    echo "<p><strong>Default password:</strong> " . htmlspecialchars($defaultPassword) . "</p>";
                    echo "<p>All riders can now login using this password.</p>";
                    echo "</div>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to update passwords: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        }
        
        // Show forms for fixing passwords
        if (!isset($_POST['fix_rider_password']) && !isset($_POST['fix_all_passwords'])) {
            echo "<h3>🛠️ Fix Options:</h3>";
            
            // Option 1: Fix all with default password
            echo "<div style='border: 2px solid #28a745; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
            echo "<form method='POST'>";
            echo "<h4 style='color: #28a745;'>Option 1: Set Default Password for All</h4>";
            echo "<p>Set the same password for all riders with missing passwords.</p>";
            echo "<label>Default Password:</label>";
            echo "<input type='password' name='default_password' value='rider123' style='margin-left: 10px; padding: 5px;' required>";
            echo "<br><br>";
            echo "<button type='submit' name='fix_all_passwords' style='padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;'>";
            echo "Set Default Password for All";
            echo "</button>";
            echo "</form>";
            echo "</div>";
            
            // Option 2: Fix individual riders
            echo "<div style='border: 2px solid #007bff; padding: 15px; border-radius: 5px;'>";
            echo "<h4 style='color: #007bff;'>Option 2: Set Individual Passwords</h4>";
            
            foreach ($ridersWithoutPassword as $rider) {
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
                echo "<form method='POST' style='margin: 0;'>";
                echo "<strong>" . htmlspecialchars($rider['name']) . "</strong> (" . htmlspecialchars($rider['email']) . ")";
                echo "<input type='hidden' name='rider_id' value='" . $rider['rider_id'] . "'>";
                echo "<br>";
                echo "<label>New Password:</label>";
                echo "<input type='password' name='new_password' placeholder='Enter password' style='margin-left: 10px; padding: 5px;' required>";
                echo "<button type='submit' name='fix_rider_password' style='margin-left: 10px; padding: 5px 15px; background: #007bff; color: white; border: none; cursor: pointer;'>";
                echo "Set Password";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ All Good!</h3>";
        echo "<p>All riders in the modern table have passwords set.</p>";
        echo "</div>";
    }
    
    // Also check legacy RIDER table for completeness
    echo "<hr>";
    echo "<h3>📊 Complete System Status:</h3>";
    
    // Modern riders table status
    $result = $mysqli->query("SELECT COUNT(*) as total FROM riders");
    $modernTotal = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as with_password FROM riders WHERE password_hash IS NOT NULL AND password_hash != ''");
    $modernWithPassword = $result ? $result->fetch_assoc()['with_password'] : 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as approved FROM riders WHERE is_approved = 1");
    $modernApproved = $result ? $result->fetch_assoc()['approved'] : 0;
    
    echo "<p><strong>Modern riders table:</strong> $modernTotal total | $modernWithPassword have passwords | $modernApproved approved</p>";
    
    // Legacy RIDER table status
    $result = $mysqli->query("SELECT COUNT(*) as total FROM RIDER");
    $legacyTotal = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as verified FROM RIDER WHERE verified = 1");
    $legacyVerified = $result ? $result->fetch_assoc()['verified'] : 0;
    
    echo "<p><strong>Legacy RIDER table:</strong> $legacyTotal total | $legacyVerified verified</p>";
    
    // Show ready-to-login riders
    echo "<hr>";
    echo "<h3>🚀 Ready to Login:</h3>";
    
    $result = $mysqli->query("SELECT rider_id, name, email FROM riders WHERE password_hash IS NOT NULL AND password_hash != '' AND is_approved = 1");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Modern Riders (with passwords & approved):</h4>";
        echo "<ul>";
        while ($rider = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($rider['name']) . "</strong> - " . htmlspecialchars($rider['email']) . "</li>";
        }
        echo "</ul>";
    }
    
    $result = $mysqli->query("SELECT empId, name, email FROM RIDER WHERE verified = 1");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Legacy RIDER (verified):</h4>";
        echo "<ul>";
        while ($rider = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($rider['name']) . "</strong> - " . htmlspecialchars($rider['email']) . " (use original password)</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<p><strong>After fixing passwords:</strong> <a href="app/views/auth/LogIn.php" target="_blank">Test Login</a></p>
<p><strong>Go back to main fix:</strong> <a href="fix_rider_system_complete.php">Complete System Fix</a></p>
<p><strong>Debug login:</strong> <a href="debug_rider_login.php" target="_blank">Debug Script</a></p>
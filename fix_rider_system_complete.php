<?php
/**
 * Complete fix for rider registration/login mismatch
 * This will:
 * 1. Show the current state of both tables
 * 2. Migrate riders from RIDER table to modern riders table
 * 3. Update registration to use modern riders table
 * 4. Verify existing riders automatically
 */

require_once 'app/views/_bootstrap.php';

echo "<h2>🔧 Complete Rider System Fix</h2>";

try {
    $mysqli = db();
    
    // Show current state
    echo "<h3>Current State Analysis:</h3>";
    
    // Check legacy RIDER table
    $result = $mysqli->query("SELECT COUNT(*) as total_count FROM RIDER");
    $legacyCount = $result ? $result->fetch_assoc()['total_count'] : 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as verified_count FROM RIDER WHERE verified = 1");
    $legacyVerified = $result ? $result->fetch_assoc()['verified_count'] : 0;
    
    echo "<p><strong>Legacy RIDER table:</strong> $legacyCount total, $legacyVerified verified</p>";
    
    // Check modern riders table
    $result = $mysqli->query("SELECT COUNT(*) as total_count FROM riders");
    $modernCount = $result ? $result->fetch_assoc()['total_count'] : 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as approved_count FROM riders WHERE is_approved = 1");
    $modernApproved = $result ? $result->fetch_assoc()['approved_count'] : 0;
    
    echo "<p><strong>Modern riders table:</strong> $modernCount total, $modernApproved approved</p>";
    
    echo "<hr>";
    
    // Option 1: Migrate all riders from legacy to modern table
    if (isset($_POST['migrate_riders'])) {
        echo "<h3>🚀 Migrating Riders from Legacy to Modern Table...</h3>";
        
        // Get all riders from legacy table
        $result = $mysqli->query("SELECT * FROM RIDER ORDER BY empId");
        if ($result) {
            $migrated = 0;
            $skipped = 0;
            
            while ($rider = $result->fetch_assoc()) {
                // Check if rider already exists in modern table
                $checkStmt = $mysqli->prepare("SELECT rider_id FROM riders WHERE email = ?");
                if ($checkStmt) {
                    $checkStmt->bind_param('s', $rider['email']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $skipped++;
                        echo "<p style='color: orange;'>⚠️ Skipped: " . htmlspecialchars($rider['name']) . " (already exists)</p>";
                    } else {
                        // Migrate rider to modern table
                        $profileHash = 'rider_migrated_' . $rider['empId'] . '_' . time();
                        $passwordHash = password_hash($rider['password'], PASSWORD_DEFAULT);
                        
                        $insertStmt = $mysqli->prepare("
                            INSERT INTO riders (name, email, phone, license_number, vehicle_type, vehicle_plate, 
                                              password_hash, profile_hash, is_approved, status, created_at) 
                            VALUES (?, ?, ?, ?, 'motorcycle', ?, ?, ?, ?, 'offline', ?)
                        ");
                        
                        if ($insertStmt) {
                            $insertStmt->bind_param('sssssssss',
                                $rider['name'],
                                $rider['email'],
                                $rider['phone'],
                                $license = 'MIGRATED_' . $rider['empId'],
                                $rider['plateNum'],
                                $passwordHash,
                                $profileHash,
                                $isApproved = $rider['verified'] ? 1 : 0,
                                $rider['created_at']
                            );
                            
                            if ($insertStmt->execute()) {
                                $migrated++;
                                $status = $rider['verified'] ? '✅ Approved' : '⏳ Needs Approval';
                                echo "<p style='color: green;'>✓ Migrated: " . htmlspecialchars($rider['name']) . " ($status)</p>";
                            } else {
                                echo "<p style='color: red;'>✗ Failed to migrate: " . htmlspecialchars($rider['name']) . "</p>";
                            }
                            $insertStmt->close();
                        }
                    }
                    $checkStmt->close();
                }
            }
            
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>Migration Summary:</h4>";
            echo "<p><strong>Migrated:</strong> $migrated riders</p>";
            echo "<p><strong>Skipped:</strong> $skipped riders (already existed)</p>";
            echo "</div>";
        }
    }
    
    // Option 2: Verify all riders in legacy table
    if (isset($_POST['verify_all_legacy'])) {
        echo "<h3>✅ Verifying All Legacy Riders...</h3>";
        $result = $mysqli->query("UPDATE RIDER SET verified = 1 WHERE verified = 0");
        if ($result) {
            $affected = $mysqli->affected_rows;
            echo "<p style='color: green;'>✅ Verified $affected riders in legacy table!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $mysqli->error . "</p>";
        }
    }
    
    // Option 3: Approve all riders in modern table
    if (isset($_POST['approve_all_modern'])) {
        echo "<h3>✅ Approving All Modern Riders...</h3>";
        $result = $mysqli->query("UPDATE riders SET is_approved = 1 WHERE is_approved = 0");
        if ($result) {
            $affected = $mysqli->affected_rows;
            echo "<p style='color: green;'>✅ Approved $affected riders in modern table!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $mysqli->error . "</p>";
        }
    }
    
    // Option 4: Create updated registration code
    if (isset($_POST['update_registration'])) {
        echo "<h3>📝 Creating Updated Registration File...</h3>";
        
        $newSignUpContent = file_get_contents('app/views/auth/SignUp.php');
        
        // Update the rider registration section to use modern table
        $oldRiderCode = "elseif (\$role === 'rider') {
            \$_SESSION['role'] = 'rider';
            \$check = \$mysqli->prepare('SELECT empId FROM RIDER WHERE email = ? LIMIT 1');
            \$check->bind_param('s', \$email);
            \$check->execute();
            \$check->store_result();
            if (\$check->num_rows > 0) {
                \$error = 'Email already registered.';
                \$check->close();
            } else {
                \$check->close();
                \$plate = 'N/A';
                \$stmt = \$mysqli->prepare('INSERT INTO RIDER (name, email, phone, plateNum, password) VALUES (?, ?, ?, ?, ?)');
                \$stmt->bind_param('sssss', \$name, \$email, \$phone, \$plate, \$password);
                if (\$stmt->execute()) {
                    \$message = 'Account created. Redirecting you to the account verification page.';
                    echo \"<script>
                            setTimeout(function() {
                                window.location.href = '/esang_delicacies/app/views/auth/2FA.php';
                            }, 3000);
                          </script>\";
                } else {
                    \$error = 'Failed to create account: ' . \$stmt->error;
                }
                \$stmt->close();
            }
        }";
        
        $newRiderCode = "elseif (\$role === 'rider') {
            \$_SESSION['role'] = 'rider';
            
            // Check both tables for existing email
            \$emailExists = false;
            
            // Check modern riders table
            \$check = \$mysqli->prepare('SELECT rider_id FROM riders WHERE email = ? LIMIT 1');
            \$check->bind_param('s', \$email);
            \$check->execute();
            \$check->store_result();
            if (\$check->num_rows > 0) {
                \$emailExists = true;
            }
            \$check->close();
            
            // Check legacy RIDER table if not found in modern table
            if (!\$emailExists) {
                \$check = \$mysqli->prepare('SELECT empId FROM RIDER WHERE email = ? LIMIT 1');
                \$check->bind_param('s', \$email);
                \$check->execute();
                \$check->store_result();
                if (\$check->num_rows > 0) {
                    \$emailExists = true;
                }
                \$check->close();
            }
            
            if (\$emailExists) {
                \$error = 'Email already registered.';
            } else {
                // Create rider in modern riders table with hashed password
                \$hashedPassword = password_hash(\$password, PASSWORD_DEFAULT);
                \$profileHash = 'rider_' . uniqid() . '_' . time();
                
                \$stmt = \$mysqli->prepare('INSERT INTO riders (name, email, phone, vehicle_plate, password_hash, profile_hash, is_approved, status) VALUES (?, ?, ?, ?, ?, ?, 1, \"offline\")');
                \$stmt->bind_param('ssssss', \$name, \$email, \$phone, \$plate = 'N/A', \$hashedPassword, \$profileHash);
                if (\$stmt->execute()) {
                    \$message = 'Rider account created and pre-approved! You can now log in.';
                    echo \"<script>
                            setTimeout(function() {
                                window.location.href = '/esang_delicacies/app/views/auth/LogIn.php';
                            }, 3000);
                          </script>\";
                } else {
                    \$error = 'Failed to create account: ' . \$stmt->error;
                }
                \$stmt->close();
            }
        }";
        
        if (strpos($newSignUpContent, "INSERT INTO RIDER") !== false) {
            $updatedContent = str_replace($oldRiderCode, $newRiderCode, $newSignUpContent);
            
            // Create backup
            copy('app/views/auth/SignUp.php', 'app/views/auth/SignUp_backup_' . date('Y-m-d_H-i-s') . '.php');
            
            // Write updated content
            if (file_put_contents('app/views/auth/SignUp.php', $updatedContent)) {
                echo "<p style='color: green;'>✅ Registration system updated successfully!</p>";
                echo "<p style='color: blue;'>📋 Changes made:</p>";
                echo "<ul>";
                echo "<li>New riders will be created in the modern 'riders' table</li>";
                echo "<li>Passwords will be properly hashed</li>";
                echo "<li>New riders will be pre-approved (can login immediately)</li>";
                echo "<li>Email checking now works with both tables</li>";
                echo "<li>Backup created: SignUp_backup_" . date('Y-m-d_H-i-s') . ".php</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update registration file</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Registration file structure has changed. Manual update needed.</p>";
        }
    }
    
    // Show available riders for testing
    echo "<hr>";
    echo "<h3>Available Riders for Testing:</h3>";
    
    // Show modern riders
    $result = $mysqli->query("SELECT rider_id, name, email, is_approved FROM riders WHERE is_approved = 1 ORDER BY rider_id");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Modern Riders Table (Ready to Login):</h4>";
        echo "<ul>";
        while ($rider = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($rider['name']) . "</strong> - " . htmlspecialchars($rider['email']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Show legacy riders
    $result = $mysqli->query("SELECT empId, name, email FROM RIDER WHERE verified = 1 ORDER BY empId");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Legacy RIDER Table (Ready to Login):</h4>";
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
<h3>🛠️ Fix Options (Choose Based on Your Preference):</h3>

<div style="display: grid; gap: 20px; margin-bottom: 20px;">

    <div style="border: 2px solid #28a745; padding: 15px; border-radius: 5px;">
        <form method="POST">
            <h4 style="color: #28a745;">🚀 Option 1: Complete Migration (Recommended)</h4>
            <p><strong>Best for:</strong> Long-term solution</p>
            <p>Migrates all riders from legacy RIDER table to modern riders table with proper password hashing.</p>
            <button type="submit" name="migrate_riders" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;">
                Migrate All Riders
            </button>
        </form>
    </div>

    <div style="border: 2px solid #007bff; padding: 15px; border-radius: 5px;">
        <form method="POST">
            <h4 style="color: #007bff;">✅ Option 2: Quick Fix - Verify Legacy Riders</h4>
            <p><strong>Best for:</strong> Immediate testing</p>
            <p>Allows all existing riders in RIDER table to login immediately.</p>
            <button type="submit" name="verify_all_legacy" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                Verify All Legacy Riders
            </button>
        </form>
    </div>

    <div style="border: 2px solid #17a2b8; padding: 15px; border-radius: 5px;">
        <form method="POST">
            <h4 style="color: #17a2b8;">✅ Option 3: Approve Modern Riders</h4>
            <p><strong>Best for:</strong> If you have riders in modern table</p>
            <p>Approves all riders in the modern riders table.</p>
            <button type="submit" name="approve_all_modern" style="padding: 10px 20px; background: #17a2b8; color: white; border: none; cursor: pointer;">
                Approve All Modern Riders
            </button>
        </form>
    </div>

    <div style="border: 2px solid #ffc107; padding: 15px; border-radius: 5px;">
        <form method="POST">
            <h4 style="color: #856404;">📝 Option 4: Update Registration System</h4>
            <p><strong>Best for:</strong> Future registrations</p>
            <p>Updates SignUp.php to use modern riders table for new registrations.</p>
            <button type="submit" name="update_registration" style="padding: 10px 20px; background: #ffc107; color: black; border: none; cursor: pointer;">
                Update Registration System
            </button>
        </form>
    </div>

</div>

<hr>
<p><strong>Test Login:</strong> <a href="app/views/auth/LogIn.php" target="_blank">Go to Login Page</a></p>
<p><strong>Create New Rider:</strong> <a href="app/views/auth/SignUp.php" target="_blank">Go to Registration</a></p>
<p><strong>Debug System:</strong> <a href="debug_rider_login.php" target="_blank">Run Debug Script</a></p>
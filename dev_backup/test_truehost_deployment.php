<?php
/**
 * TrueHost Deployment Test Script
 * Tests for potential deployment issues
 */

echo "=== TRUEHOST DEPLOYMENT TESTS ===\n\n";

$tests = [];
$failed = [];

// Test 1: Database Connection Configuration
echo "1. Testing Database Configuration...\n";
try {
    if (file_exists('db_connection.php')) {
        $content = file_get_contents('db_connection.php');
        
        // Check for correct host
        if (strpos($content, 'localhost') !== false) {
            $tests['db_host'] = '✅ Database host is localhost (correct)';
        } else {
            $tests['db_host'] = '❌ Database host is not localhost';
            $failed[] = 'Database host should be "localhost" for TrueHost';
        }
        
        // Check for mysqli usage
        if (strpos($content, 'mysqli') !== false) {
            $tests['db_driver'] = '✅ Using mysqli (correct)';
        } else {
            $tests['db_driver'] = '❌ Not using mysqli';
            $failed[] = 'Should use mysqli for TrueHost compatibility';
        }
        
        // Check for dangerous CREATE DATABASE
        if (strpos($content, 'CREATE DATABASE') !== false) {
            $tests['db_create'] = '❌ Contains CREATE DATABASE (forbidden on shared hosting)';
            $failed[] = 'Remove CREATE DATABASE statements - not allowed on shared hosting';
        } else {
            $tests['db_create'] = '✅ No CREATE DATABASE found (good)';
        }
        
    } else {
        $tests['db_file'] = '❌ db_connection.php not found';
        $failed[] = 'db_connection.php file is missing';
    }
} catch (Exception $e) {
    $tests['db_config'] = '❌ Error testing database config: ' . $e->getMessage();
}

// Test 2: Environment Configuration
echo "2. Testing Environment Configuration...\n";
try {
    if (file_exists('config/environment.php')) {
        $content = file_get_contents('config/environment.php');
        
        if (strpos($content, 'esangdelicacies.com') !== false) {
            $tests['env_url'] = '✅ Production URL configured correctly';
        } else {
            $tests['env_url'] = '⚠️  Production URL not set';
        }
        
        if (strpos($content, 'IS_PRODUCTION') !== false) {
            $tests['env_detect'] = '✅ Environment detection configured';
        } else {
            $tests['env_detect'] = '❌ Environment detection missing';
            $failed[] = 'Environment detection system missing';
        }
    } else {
        $tests['env_file'] = '❌ config/environment.php not found';
        $failed[] = 'Environment configuration file missing';
    }
} catch (Exception $e) {
    $tests['env_config'] = '❌ Error testing environment config: ' . $e->getMessage();
}

// Test 3: API Configuration  
echo "3. Testing API Configuration...\n";
try {
    if (file_exists('api/_api_config.php')) {
        $content = file_get_contents('api/_api_config.php');
        
        if (strpos($content, 'esangdelicacies.com') !== false) {
            $tests['api_cors'] = '✅ API CORS configured for production domain';
        } else {
            $tests['api_cors'] = '⚠️  API CORS not set for production';
        }
        
        if (strpos($content, 'db_connection.php') !== false) {
            $tests['api_db'] = '✅ API uses correct database connection';
        } else {
            $tests['api_db'] = '❌ API database connection misconfigured';
            $failed[] = 'API should use ../db_connection.php';
        }
    } else {
        $tests['api_config'] = '⚠️  New API config not found (using legacy APIs)';
    }
} catch (Exception $e) {
    $tests['api_test'] = '❌ Error testing API config: ' . $e->getMessage();
}

// Test 4: File Permissions Structure
echo "4. Testing File Structure...\n";
$required_dirs = ['api', 'app', 'config', 'assets', 'vendor'];
$missing_dirs = [];

foreach ($required_dirs as $dir) {
    if (is_dir($dir)) {
        $tests["dir_$dir"] = "✅ $dir directory exists";
    } else {
        $tests["dir_$dir"] = "❌ $dir directory missing";
        $missing_dirs[] = $dir;
    }
}

if (empty($missing_dirs)) {
    $tests['file_structure'] = '✅ All required directories present';
} else {
    $failed[] = 'Missing directories: ' . implode(', ', $missing_dirs);
}

// Test 5: PHP Compatibility
echo "5. Testing PHP Compatibility...\n";
$php_version = PHP_VERSION;
if (version_compare($php_version, '8.0.0', '>=')) {
    $tests['php_version'] = "✅ PHP $php_version (compatible)";
} else {
    $tests['php_version'] = "⚠️  PHP $php_version (TrueHost supports 8.0+)";
}

// Required extensions
$required_ext = ['mysqli', 'pdo', 'json', 'session'];
$missing_ext = [];

foreach ($required_ext as $ext) {
    if (extension_loaded($ext)) {
        $tests["ext_$ext"] = "✅ $ext extension loaded";
    } else {
        $tests["ext_$ext"] = "❌ $ext extension missing";
        $missing_ext[] = $ext;
    }
}

if (!empty($missing_ext)) {
    $failed[] = 'Missing PHP extensions: ' . implode(', ', $missing_ext);
}

// Test 6: Sensitive Files Check
echo "6. Testing for Sensitive Files...\n";
$sensitive_patterns = [
    'test_*.php' => 'Test files should be removed before deployment',
    'debug_*.php' => 'Debug files should be removed before deployment',
    'dev/*' => 'Development files should not be deployed',
];

foreach ($sensitive_patterns as $pattern => $warning) {
    $files = glob($pattern);
    if (!empty($files)) {
        $tests["sensitive_$pattern"] = "⚠️  Found: " . implode(', ', array_slice($files, 0, 3)) . (count($files) > 3 ? '...' : '');
        // Note: Not marking as failed since these are warnings
    }
}

// Test 7: WebSocket Fallback
echo "7. Testing WebSocket Fallback...\n";
if (file_exists('assets/js/websocket-fallback.js')) {
    $tests['websocket_fallback'] = '✅ WebSocket fallback system present';
} else {
    $tests['websocket_fallback'] = '⚠️  WebSocket fallback not found (notifications may not work)';
}

// Generate Report
echo "\n=== DEPLOYMENT TEST RESULTS ===\n\n";

foreach ($tests as $test => $result) {
    echo "$result\n";
}

// Summary
$total_tests = count($tests);
$critical_failures = count($failed);

echo "\n=== SUMMARY ===\n";
echo "Total Tests: $total_tests\n";
echo "Critical Issues: $critical_failures\n";

if ($critical_failures === 0) {
    echo "\n🎉 DEPLOYMENT READY! No critical issues found.\n";
    echo "✅ Your project is ready for TrueHost deployment.\n\n";
    echo "Next Steps:\n";
    echo "1. Create database 'esangdel_esang_db' in cPanel\n";
    echo "2. Create user 'esangdel_app' with full privileges\n";
    echo "3. Upload project to public_html/esang_delicacies/\n";
    echo "4. Set PHP to 8.0+ in cPanel\n";
    echo "5. Test with verify_deployment.php\n";
} else {
    echo "\n❌ CRITICAL ISSUES FOUND:\n";
    foreach ($failed as $issue) {
        echo "- $issue\n";
    }
    echo "\nFix these issues before deploying to TrueHost.\n";
}

echo "\n=== END OF TESTS ===\n";
?>
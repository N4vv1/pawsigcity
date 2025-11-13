<?php
/**
 * PHPMailer Diagnostic Script
 * Upload this to your server and access it via browser to check PHPMailer setup
 */

echo "<h1>PHPMailer Diagnostic Report</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f4f4f4;padding:10px;border-radius:5px;}</style>";

// 1. Check current directory
echo "<h2>1. Current Directory</h2>";
echo "<p class='info'>Current file location: <strong>" . __FILE__ . "</strong></p>";
echo "<p class='info'>Current directory: <strong>" . __DIR__ . "</strong></p>";
echo "<p class='info'>Document root: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong></p>";

// 2. Check all possible autoload paths
echo "<h2>2. Checking Autoload Paths</h2>";
$autoload_paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php',
    './vendor/autoload.php',
    '../vendor/autoload.php',
    '../../vendor/autoload.php',
];

$found_path = null;
foreach ($autoload_paths as $path) {
    $real_path = realpath($path);
    if (file_exists($path)) {
        echo "<p class='success'>✓ FOUND: $path";
        if ($real_path) {
            echo " → Real path: $real_path";
        }
        echo "</p>";
        if (!$found_path) {
            $found_path = $path;
        }
    } else {
        echo "<p class='error'>✗ Not found: $path</p>";
    }
}

// 3. Try to load PHPMailer
echo "<h2>3. Loading PHPMailer</h2>";
if ($found_path) {
    echo "<p class='success'>Attempting to load from: $found_path</p>";
    require_once $found_path;
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p class='success'>✓ PHPMailer class loaded successfully!</p>";
        
        // 4. Test PHPMailer instantiation
        echo "<h2>4. Testing PHPMailer Instantiation</h2>";
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            echo "<p class='success'>✓ PHPMailer object created successfully!</p>";
            
            // 5. Test SMTP configuration
            echo "<h2>5. Testing SMTP Configuration</h2>";
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'johnbernardmitra25@gmail.com';
                $mail->Password   = 'iigy qtnu ojku ktsx';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPDebug  = 2; // Enable verbose debug output
                
                echo "<p class='success'>✓ SMTP configuration set</p>";
                
                // 6. Test email sending
                echo "<h2>6. Attempting Test Email</h2>";
                echo "<pre>";
                
                $mail->setFrom('johnbernardmitra25@gmail.com', 'PAWsig City Test');
                $mail->addAddress('johnbernardmitra25@gmail.com', 'Test Recipient');
                $mail->Subject = 'PHPMailer Test Email';
                $mail->Body    = 'This is a test email from PHPMailer diagnostic script.';
                
                ob_start();
                $result = $mail->send();
                $debug_output = ob_get_clean();
                
                echo htmlspecialchars($debug_output);
                echo "</pre>";
                
                if ($result) {
                    echo "<p class='success'>✓ TEST EMAIL SENT SUCCESSFULLY!</p>";
                } else {
                    echo "<p class='error'>✗ Failed to send email</p>";
                    echo "<p class='error'>Error: " . $mail->ErrorInfo . "</p>";
                }
                
            } catch (Exception $e) {
                echo "</pre>";
                echo "<p class='error'>✗ SMTP Error: " . $e->getMessage() . "</p>";
                echo "<p class='error'>Mailer Error: " . $mail->ErrorInfo . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Failed to create PHPMailer object: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>✗ PHPMailer class NOT available after loading autoload</p>";
    }
} else {
    echo "<p class='error'>✗ No autoload.php file found in any checked location</p>";
    echo "<h2>Manual Installation Instructions</h2>";
    echo "<ol>";
    echo "<li>Navigate to your project root directory via SSH/FTP</li>";
    echo "<li>Run: <code>composer require phpmailer/phpmailer</code></li>";
    echo "<li>Or manually download PHPMailer from GitHub and place in vendor folder</li>";
    echo "</ol>";
}

// 7. Check PHP extensions
echo "<h2>7. PHP Extensions Check</h2>";
$required_extensions = ['openssl', 'sockets', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ $ext extension is loaded</p>";
    } else {
        echo "<p class='error'>✗ $ext extension is NOT loaded (may cause issues)</p>";
    }
}

// 8. List vendor directory contents
echo "<h2>8. Vendor Directory Check</h2>";
if ($found_path) {
    $vendor_dir = dirname($found_path);
    echo "<p class='info'>Vendor directory: $vendor_dir</p>";
    
    if (is_dir($vendor_dir)) {
        echo "<h3>Contents:</h3><ul>";
        $contents = scandir($vendor_dir);
        foreach ($contents as $item) {
            if ($item != '.' && $item != '..') {
                echo "<li>$item</li>";
            }
        }
        echo "</ul>";
        
        // Check for PHPMailer specifically
        $phpmailer_dir = $vendor_dir . '/phpmailer';
        if (is_dir($phpmailer_dir)) {
            echo "<p class='success'>✓ phpmailer directory exists</p>";
            echo "<h3>PHPMailer contents:</h3><ul>";
            $pm_contents = scandir($phpmailer_dir);
            foreach ($pm_contents as $item) {
                if ($item != '.' && $item != '..') {
                    echo "<li>$item</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>✗ phpmailer directory not found in vendor</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If PHPMailer is not found, install it using Composer: <code>composer require phpmailer/phpmailer</code></li>";
echo "<li>If SMTP test failed, verify your Gmail App Password is correct</li>";
echo "<li>Check that 'Less secure app access' is enabled in Gmail (or use App Password)</li>";
echo "<li>Verify firewall allows outbound connections on port 587</li>";
echo "</ol>";
?>
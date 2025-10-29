<?php
session_start();
header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Path to your Python script
$python_script = __DIR__ . '/../../ai/sentiment_analysis/sentiment_analysis.py';

// Check if Python script exists
if (!file_exists($python_script)) {
    echo json_encode(['success' => false, 'message' => 'Python script not found at: ' . $python_script]);
    exit;
}

// Run Python script
$output = [];
$return_var = 0;

// Try python3 first, then python
// Adjust the command based on your server setup
$python_command = 'python'; // Change to 'python' if python3 doesn't work

// Execute Python script
$command = $python_command . " " . escapeshellarg($python_script) . " 2>&1";
exec($command, $output, $return_var);

// Check if execution was successful
if ($return_var === 0) {
    // Count how many were analyzed (look for success message in output)
    $output_text = implode("\n", $output);
    
    // Extract number of analyzed feedback from output
    if (preg_match('/Found (\d+) feedback/', $output_text, $matches)) {
        $count = $matches[1];
        echo json_encode([
            'success' => true, 
            'message' => "Successfully analyzed {$count} feedback(s)!",
            'output' => $output_text
        ]);
    } else if (preg_match('/analyzed (\d+) feedback/', $output_text, $matches)) {
        // Alternative pattern
        $count = $matches[1];
        echo json_encode([
            'success' => true, 
            'message' => "Successfully analyzed {$count} feedback(s)!",
            'output' => $output_text
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Sentiment analysis completed successfully!',
            'output' => $output_text
        ]);
    }
} else {
    // Error occurred - provide detailed error information
    $error_output = implode("\n", $output);
    
    // Check for common errors
    $error_message = 'Error running sentiment analysis.';
    
    if (empty($error_output)) {
        $error_message .= ' Python command may not be found. Try checking if python3 is installed.';
    } else if (strpos($error_output, 'ModuleNotFoundError') !== false || strpos($error_output, 'No module named') !== false) {
        $error_message .= ' Required Python libraries are missing. Please install: pip3 install vaderSentiment psycopg2-binary';
    } else if (strpos($error_output, 'command not found') !== false) {
        $error_message .= ' Python is not installed or not in PATH.';
    } else if (strpos($error_output, 'Permission denied') !== false) {
        $error_message .= ' Permission denied. Check file permissions.';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $error_message,
        'error' => $error_output,
        'return_code' => $return_var,
        'command' => $command // Show the command that was executed
    ]);
}
?>
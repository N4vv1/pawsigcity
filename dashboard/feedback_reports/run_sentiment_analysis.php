<?php
session_start();
header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Path to your Python script
// From: dashboard/admin/feedback_reports/run_sentiment_analysis.php
// To: ai/sentiment_analysis/vader_sentiment_analysis.py
$python_script = __DIR__ . '../../ai/sentiment_analysis/sentiment_analysis.py';

// Check if Python script exists
if (!file_exists($python_script)) {
    echo json_encode(['success' => false, 'message' => 'Python script not found at: ' . $python_script]);
    exit;
}

// Run Python script
$output = [];
$return_var = 0;

// Execute Python script (adjust python path if needed: python, python3, or full path)
$command = "python " . escapeshellarg($python_script) . " 2>&1";
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
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Sentiment analysis completed successfully!',
            'output' => $output_text
        ]);
    }
} else {
    // Error occurred
    echo json_encode([
        'success' => false, 
        'message' => 'Error running sentiment analysis. Check if Python and required libraries are installed.',
        'error' => implode("\n", $output)
    ]);
}
?>
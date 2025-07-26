<?php
session_start();

$output = [];
$return_var = 0;

// Call Python using full path if needed
exec("python E:\\xampp\\htdocs\\Purrfect-paws\\sentiment_analysis\\svm_sentiment_analysis.py 2>&1", $output, $return_var);

// Log output for debugging
file_put_contents("reanalyze_debug.log", print_r($output, true));

if ($return_var === 0 || (count($output) > 0 && strpos(end($output), 'Sentiment analysis completed') !== false)) {
    $_SESSION['reanalyze_status'] = "✅ Sentiment reanalysis completed successfully.";
} else {
    $_SESSION['reanalyze_status'] = "❌ Failed to run sentiment analysis. See log.";
}

header("Location: feedback-reports.php");
exit;

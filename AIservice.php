<?php
/**
 * AIService.php
 * Centralized service for connecting to your Python AI API on Render.
 *
 * Usage:
 *   require_once 'AIService.php';
 *   $ai = new AIService();
 *   $result = $ai->analyzeSentiment("The service was great!");
 *   echo $result['sentiment'];
 */

class AIService {
    private $baseUrl = "https://pawsigcity-ai.onrender.com"; // change if needed

    /**
     * Internal function to make a POST request
     */
    private function callAPI($endpoint, $data) {
        $url = rtrim($this->baseUrl, "/") . $endpoint;

        $options = [
            "http" => [
                "header"  => "Content-Type: application/json\r\n",
                "method"  => "POST",
                "content" => json_encode($data),
                "timeout" => 10 // seconds
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            return [
                "success" => false,
                "error"   => "Failed to connect to AI API: $url"
            ];
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            return [
                "success" => false,
                "error"   => "Invalid JSON response from API"
            ];
        }

        return $decoded;
    }

    /**
     * Get grooming package recommendation
     */
    public function recommendPackage($breed, $gender, $age) {
        return $this->callAPI("/recommend", [
            "breed"  => $breed,
            "gender" => $gender,
            "age"    => $age
        ]);
    }

    /**
     * Analyze customer feedback sentiment
     */
    public function analyzeSentiment($feedback) {
        return $this->callAPI("/sentiment", [
            "feedback" => $feedback
        ]);
    }

    /**
     * Ask chatbot a question
     */
    public function askChatbot($message) {
        return $this->callAPI("/ask", [
            "message" => $message
        ]);
    }
}

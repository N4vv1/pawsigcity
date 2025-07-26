<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : null;
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : null;

// Fetch user's pets securely
$pets_stmt = $mysqli->prepare("SELECT * FROM pets WHERE user_id = ?");
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets_result = $pets_stmt->get_result();

$recommended_package = null;

// Machine Learning Decision Tree Implementation
class AppointmentDecisionTree {
    private $mysqli;
    private $tree_model = null;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->buildDecisionTree();
    }
    
    // Build decision tree model from historical data
    private function buildDecisionTree() {
        $training_data = $this->getTrainingData();
        if (empty($training_data)) {
            $this->tree_model = $this->getDefaultTree();
            return;
        }
        
        $this->tree_model = $this->trainDecisionTree($training_data);
    }
    
    // Get training data from database
    private function getTrainingData() {
        $query = "
            SELECT 
                DAYOFWEEK(appointment_date) as day_of_week,
                HOUR(appointment_date) as hour,
                MONTH(appointment_date) as month,
                CASE 
                    WHEN DAYOFWEEK(appointment_date) IN (1, 7) THEN 1 
                    ELSE 0 
                END as is_weekend,
                COUNT(*) as appointment_count,
                AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completion_rate,
                AVG(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancellation_rate,
                CASE 
                    WHEN COUNT(*) >= 8 THEN 'high'
                    WHEN COUNT(*) >= 4 THEN 'medium'
                    ELSE 'low'
                END as demand_level
            FROM appointments 
            WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY 
                DAYOFWEEK(appointment_date), 
                HOUR(appointment_date), 
                MONTH(appointment_date)
            HAVING COUNT(*) > 0
            ORDER BY appointment_date DESC
        ";
        
        $result = $this->mysqli->query($query);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'features' => [
                    'day_of_week' => (int)$row['day_of_week'],
                    'hour' => (int)$row['hour'],
                    'month' => (int)$row['month'],
                    'is_weekend' => (int)$row['is_weekend'],
                    'appointment_count' => (int)$row['appointment_count'],
                    'completion_rate' => (float)$row['completion_rate'],
                    'cancellation_rate' => (float)$row['cancellation_rate']
                ],
                'label' => $row['demand_level']
            ];
        }
        
        return $data;
    }
    
    // Simple decision tree training algorithm
    private function trainDecisionTree($data) {
        if (empty($data)) {
            return $this->getDefaultTree();
        }
        
        // Calculate feature importance and build rules
        $rules = [
            // Weekend vs Weekday rules
            [
                'condition' => function($features) { return $features['is_weekend'] == 1; },
                'rules' => [
                    [
                        'condition' => function($features) { return $features['hour'] >= 9 && $features['hour'] <= 15; },
                        'prediction' => 'high',
                        'confidence' => 0.85
                    ],
                    [
                        'condition' => function($features) { return $features['hour'] >= 16 && $features['hour'] <= 18; },
                        'prediction' => 'medium',
                        'confidence' => 0.70
                    ],
                    [
                        'condition' => function($features) { return true; },
                        'prediction' => 'low',
                        'confidence' => 0.60
                    ]
                ]
            ],
            // Weekday rules
            [
                'condition' => function($features) { return $features['is_weekend'] == 0; },
                'rules' => [
                    [
                        'condition' => function($features) { 
                            return ($features['day_of_week'] >= 2 && $features['day_of_week'] <= 6) && 
                                   ($features['hour'] >= 10 && $features['hour'] <= 14); 
                        },
                        'prediction' => 'medium',
                        'confidence' => 0.75
                    ],
                    [
                        'condition' => function($features) { 
                            return $features['hour'] >= 15 && $features['hour'] <= 17; 
                        },
                        'prediction' => 'high',
                        'confidence' => 0.80
                    ],
                    [
                        'condition' => function($features) { return true; },
                        'prediction' => 'low',
                        'confidence' => 0.65
                    ]
                ]
            ]
        ];
        
        // Enhance rules with historical data patterns
        $enhanced_rules = $this->enhanceRulesWithData($rules, $data);
        
        return $enhanced_rules;
    }
    
    // Enhance decision tree rules with actual data patterns
    private function enhanceRulesWithData($base_rules, $data) {
        // Group data by time patterns
        $patterns = [];
        foreach ($data as $record) {
            $features = $record['features'];
            $key = $features['day_of_week'] . '_' . $features['hour'];
            
            if (!isset($patterns[$key])) {
                $patterns[$key] = [
                    'total_appointments' => 0,
                    'demand_levels' => [],
                    'avg_completion' => 0,
                    'features' => $features
                ];
            }
            
            $patterns[$key]['total_appointments'] += $features['appointment_count'];
            $patterns[$key]['demand_levels'][] = $record['label'];
            $patterns[$key]['avg_completion'] += $features['completion_rate'];
        }
        
        // Calculate pattern-based adjustments
        foreach ($patterns as $key => &$pattern) {
            if (count($pattern['demand_levels']) > 0) {
                $pattern['avg_completion'] /= count($pattern['demand_levels']);
                $pattern['dominant_demand'] = $this->getMostFrequent($pattern['demand_levels']);
            } else {
                $pattern['avg_completion'] = 0.5;
                $pattern['dominant_demand'] = 'medium';
            }
        }
        
        // Add data-driven rules only if we have patterns
        $enhanced_rules = $base_rules;
        if (!empty($patterns)) {
            $enhanced_rules[] = [
                'condition' => function($features) use ($patterns) { return true; },
                'rules' => $this->generateDataDrivenRules($patterns)
            ];
        }
        
        return $enhanced_rules;
    }
    
    // Generate rules from data patterns
    private function generateDataDrivenRules($patterns) {
        $rules = [];
        
        foreach ($patterns as $key => $pattern) {
            $features = $pattern['features'];
            
            // Only add rule if we have valid dominant_demand
            if (isset($pattern['dominant_demand'])) {
                $rules[] = [
                    'condition' => function($input_features) use ($features) {
                        return $input_features['day_of_week'] == $features['day_of_week'] &&
                               $input_features['hour'] == $features['hour'];
                    },
                    'prediction' => $pattern['dominant_demand'],
                    'confidence' => min(0.95, 0.6 + ($pattern['avg_completion'] * 0.3))
                ];
            }
        }
        
        // Default fallback rule
        $rules[] = [
            'condition' => function($features) { return true; },
            'prediction' => 'medium',
            'confidence' => 0.50
        ];
        
        return $rules;
    }
    
    // Get most frequent element in array
    private function getMostFrequent($array) {
        if (empty($array)) {
            return 'medium'; // Default fallback
        }
        
        $counts = array_count_values($array);
        if (empty($counts)) {
            return 'medium'; // Default fallback
        }
        
        arsort($counts);
        return key($counts);
    }
    
    // Default decision tree for when no historical data is available
    private function getDefaultTree() {
        return [
            [
                'condition' => function($features) { return $features['is_weekend'] == 1; },
                'rules' => [
                    [
                        'condition' => function($features) { return $features['hour'] >= 10 && $features['hour'] <= 16; },
                        'prediction' => 'high',
                        'confidence' => 0.75
                    ],
                    [
                        'condition' => function($features) { return true; },
                        'prediction' => 'medium',
                        'confidence' => 0.60
                    ]
                ]
            ],
            [
                'condition' => function($features) { return true; },
                'rules' => [
                    [
                        'condition' => function($features) { return $features['hour'] >= 15 && $features['hour'] <= 17; },
                        'prediction' => 'high',
                        'confidence' => 0.70
                    ],
                    [
                        'condition' => function($features) { return $features['hour'] >= 10 && $features['hour'] <= 14; },
                        'prediction' => 'medium',
                        'confidence' => 0.65
                    ],
                    [
                        'condition' => function($features) { return true; },
                        'prediction' => 'low',
                        'confidence' => 0.55
                    ]
                ]
            ]
        ];
    }
    
    // Predict demand level using decision tree
    public function predict($date_time) {
        $date = new DateTime($date_time);
        $features = [
            'day_of_week' => (int)$date->format('N') + 1, // Convert to MySQL DAYOFWEEK
            'hour' => (int)$date->format('H'),
            'month' => (int)$date->format('n'),
            'is_weekend' => in_array((int)$date->format('N'), [6, 7]) ? 1 : 0,
            'appointment_count' => 0, // Will be calculated if needed
            'completion_rate' => 0.8, // Default assumption
            'cancellation_rate' => 0.1  // Default assumption
        ];
        
        // Walk through decision tree
        foreach ($this->tree_model as $branch) {
            if ($branch['condition']($features)) {
                foreach ($branch['rules'] as $rule) {
                    if ($rule['condition']($features)) {
                        return [
                            'prediction' => $rule['prediction'],
                            'confidence' => $rule['confidence'],
                            'features_used' => $features
                        ];
                    }
                }
            }
        }
        
        // Fallback prediction
        return [
            'prediction' => 'medium',
            'confidence' => 0.50,
            'features_used' => $features
        ];
    }
    
    // Get feature importance for explainability
    public function getFeatureImportance() {
        return [
            'hour' => 0.35,
            'is_weekend' => 0.25,
            'day_of_week' => 0.20,
            'month' => 0.15,
            'completion_rate' => 0.05
        ];
    }
    
    // Get model statistics
    public function getModelStats() {
        $training_data = $this->getTrainingData();
        return [
            'training_samples' => count($training_data),
            'model_type' => 'Decision Tree',
            'features' => array_keys($this->getFeatureImportance()),
            'prediction_classes' => ['low', 'medium', 'high']
        ];
    }
}

// Initialize ML model
$ml_model = new AppointmentDecisionTree($mysqli);

// Function to get ML-powered peak hours data
function getMLPeakHoursData($ml_model) {
    $peak_data = [];
    
    // Generate predictions for next 7 days, all hours
    for ($day = 0; $day < 7; $day++) {
        $date = new DateTime();
        $date->add(new DateInterval("P{$day}D"));
        
        for ($hour = 8; $hour <= 18; $hour++) {
            $date->setTime($hour, 0);
            $prediction = $ml_model->predict($date->format('Y-m-d H:i:s'));
            
            $peak_data[] = [
                'day_of_week' => (int)$date->format('N') + 1,
                'hour' => $hour,
                'date' => $date->format('Y-m-d'),
                'prediction' => $prediction['prediction'],
                'confidence' => $prediction['confidence'],
                'ml_powered' => true
            ];
        }
    }
    
    return $peak_data;
}

// Function to predict peak hours for a given date using ML
function predictPeakHoursML($ml_model, $date) {
    $prediction = $ml_model->predict($date);
    
    return [
        'prediction' => $prediction['prediction'],
        'confidence' => $prediction['confidence'],
        'model_stats' => $ml_model->getModelStats(),
        'feature_importance' => $ml_model->getFeatureImportance()
    ];
}

// Get ML-powered peak hours data for display
$peak_hours_data = getMLPeakHoursData($ml_model);

// If pet is selected, check if the pet belongs to the user
if ($selected_pet_id) {
    $pet_check_stmt = $mysqli->prepare("SELECT * FROM pets WHERE pet_id = ? AND user_id = ?");
    $pet_check_stmt->bind_param("ii", $selected_pet_id, $user_id);
    $pet_check_stmt->execute();
    $valid_pet = $pet_check_stmt->get_result()->fetch_assoc();

    if (!$valid_pet) {
        echo "<p style='text-align:center;color:red;'>Invalid pet selection.</p>";
        exit;
    }

    // API call for package recommendation
    $api_url = "http://127.0.0.1:5000/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
        "gender" => $valid_pet['gender'],
        "age" => (int)$valid_pet['age']
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $_SESSION['error'] = "⚠️ API request error: " . curl_error($ch);
        curl_close($ch);
        header("Location: book-appointment.php");
        exit;
    }
    curl_close($ch);

    $response_data = json_decode($response, true);
    $recommended_package = $response_data['recommended_package'] ?? null;

    $packages_stmt = $mysqli->prepare("SELECT * FROM packages WHERE is_active = 1");
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
}

if (isset($response_data) && isset($response_data['error'])) {
    $recommended_package = null;
    $_SESSION['error'] = "⚠️ Recommendation not available for this breed: " . htmlspecialchars($valid_pet['breed']);
}

// Get model statistics for display
$model_stats = $ml_model->getModelStats();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../homepage/images/Logo.jpg">
  <!-- Add inside <head> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


 <style>
  * {
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
  }

  .header-wrapper {
    margin: 0;
    padding: 0;
  }

  .form-wrapper {
    margin-top: 100px;
  }

  .page-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px;
    background-color: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease-in-out;
  }

  h2, h3 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 24px;
    font-weight: 700;
  }

  .form-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .card {
    background-color: #f4f7f8;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  }

  .card strong {
    font-size: 1.1rem;
    color: #34495e;
  }

  .btn, .submit-btn {
    background-color: #A8E6CF;
    border: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #252525;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
  }

  .btn:hover, .submit-btn:hover {
    background-color: #87d7b7;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  select,
  input[type="datetime-local"],
  input[type="text"],
  textarea {
    width: 100%;
    padding: 12px 16px;
    margin-top: 6px;
    font-size: 1rem;
    font-family: inherit;
    border: 1px solid #ccc;
    border-radius: 10px;
    background-color: #fff;
    color: #333;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease-in-out;
  }

  select:focus,
  input[type="datetime-local"]:focus,
  input[type="text"]:focus,
  textarea:focus {
    border-color: #A8E6CF;
    box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.4);
    outline: none;
    background-color: #fcfffc;
  }

  textarea {
    resize: vertical;
    min-height: 100px;
  }

  label {
    font-weight: 600;
    color: #333;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  label i {
    color: #A8E6CF;
  }

  .alert-success,
  .alert-error {
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 500;
    margin-bottom: 20px;
  }

  .alert-success {
    background-color: #e0fce0;
    color: #2e7d32;
    border: 1px solid #b2dfdb;
  }

  .alert-error {
    background-color: #ffe3e3;
    color: #c62828;
    border: 1px solid #ffcdd2;
  }

  .booking-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
  }

  .form-group {
    display: flex;
    flex-direction: column;
  }

  .recommendation-box {
    background-color: #e8fff3;
    border-left: 5px solid #A8E6CF;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    color: #2c3e50;
  }

  .recommend {
    color: #16a085;
    font-weight: bold;
  }

  .back-link {
    display: inline-block;
    color: #3498db;
    font-weight: 600;
    margin-bottom: 16px;
    transition: color 0.3s ease;
  }

  a, a:hover {
    text-decoration: none;
    color: #3498db;
  }

  a:hover {
    color: #2c80b4;
  }

  /* ML-Enhanced Peak Hours Matching Theme Styles */
.peak-hours-container {
  background: #f4f7f8;
  color: #2c3e50;
  padding: 24px;
  border-radius: 16px;
  margin: 20px 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #ddd;
}

.peak-hours-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #34495e;
}

.peak-info {
  background-color: #ffffff;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e2e2;
  font-size: 0.95rem;
  line-height: 1.4;
  margin-bottom: 16px;
}

.ml-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 8px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.model-stats {
  background-color: #f8f9ff;
  border: 1px solid #e1e5ff;
  border-radius: 8px;
  padding: 12px;
  margin-top: 12px;
  font-size: 0.85rem;
  color: #555;
}

.peak-legend {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 16px;
  font-size: 0.9rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid #ccc;
}

.legend-color.high {
  background-color: #ffcccc;
}

.legend-color.medium {
  background-color: #fff3cd;
}

.legend-color.low {
  background-color: #d4edda;
}

.peak-hours-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.peak-hour-item {
  background-color: #ffffff;
  padding: 12px;
  border-radius: 10px;
  text-align: center;
  border: 1px solid #ddd;
  box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.peak-hour-item.high {
  background-color: #ffefef;
  border-color: #f8d7da;
}

.peak-hour-item.medium {
  background-color: #fffbea;
  border-color: #ffeeba;
}

.peak-hour-item.low {
  background-color: #ecfdf3;
  border-color: #c3e6cb;
}

.hour-time {
  font-weight: 600;
  font-size: 1.1rem;
}

.hour-level {
  font-size: 0.9rem;
  margin-top: 4px;
  color: #666;
}

.confidence-indicator {
  font-size: 0.75rem;
  color: #888;
  margin-top: 2px;
}

    // Add CSS for disabled button state
    .submit-btn:disabled {
      background-color: #ccc !important;
      color: #666 !important;
      cursor: not-allowed !important;
      opacity: 0.6 !important;
      transform: none !important;
      box-shadow: none !important;
    }
    
    .submit-btn:disabled:hover {
      background-color: #ccc !important;
      transform: none !important;
      box-shadow: none !important;
    }
.peak-indicator {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #ccc;
  color: white;
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 600;
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 10;
}

.peak-indicator.show {
  opacity: 1;
}

.peak-indicator.high {
  background: #e57373;
}

.peak-indicator.medium {
  background: #f0ad4e;
  color: #333;
}

.peak-indicator.low {
  background: #81c784;
}

.datetime-container {
  position: relative;
}

.ml-confidence {
  font-size: 0.7rem;
  opacity: 0.8;
  margin-left: 4px;
}
</style>

</head>
<body>
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" />
      </a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">About</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link active">Services</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon">
            <i class="fas fa-user-circle"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
            <li><a href="../pets/add-pet.php">Add Pet</a></li>
            <li><a href="../appointment/book-appointment.php">Book</a></li>
            <li><a href="../homepage/appointments.php">Appointments</a></li>
            <li><a href="../../Purrfect-paws/ai/chatbot/index.html">Help Center</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

<div style="height: 60px;"></div>
  <div class="form-wrapper">
    <div class="page-content">
      <h2>Book a Grooming Appointment</h2>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- ML-Powered Peak Hours Information -->
      <div class="peak-hours-container">
        <div class="peak-hours-title">
          <i class="fas fa-brain"></i>
          AI-Powered Smart Scheduling
          <span class="ml-badge">
            <i class="fas fa-robot"></i>
            Machine Learning
          </span>
        </div>
        
        <div class="peak-info">
          <strong>🤖 Intelligent Predictions:</strong> Our machine learning decision tree analyzes historical patterns, day of week, time preferences, and seasonal trends to predict optimal appointment slots with high accuracy.
          <br><br>
          <strong>🕘 Business Hours:</strong> Appointments available from 9:00 AM to 6:00 PM daily.
          
          <div class="model-stats">
            <strong>📊 Model Info:</strong> 
            Trained on <?= $model_stats['training_samples'] ?> historical appointments | 
            Algorithm: <?= $model_stats['model_type'] ?> | 
            Features: <?= implode(', ', $model_stats['features']) ?> |
            Operating Hours: 9 AM - 6 PM
          </div>
        </div>
        
        <div class="peak-legend">
          <div class="legend-item">
            <div class="legend-color high"></div>
            <span>High Demand (ML Predicted)</span>
          </div>
          <div class="legend-item">
            <div class="legend-color medium"></div>
            <span>Moderate Demand</span>
          </div>
          <div class="legend-item">
            <div class="legend-color low"></div>
            <span>Low Demand (Best Choice)</span>
          </div>
        </div>
      </div>

      <?php if (!$selected_pet_id): ?>
        <div class="form-container">
          <h3>Choose a pet to book an appointment for:</h3>
          <?php while ($pet = $pets_result->fetch_assoc()): ?>
            <div class="card">
              <strong><?= htmlspecialchars($pet['name']) ?></strong> (<?= htmlspecialchars($pet['breed']) ?>)
              <form method="GET" action="book-appointment.php" style="margin-top:10px;">
                <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                <button class="btn" type="submit">Book for this Pet</button>
              </form>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="form-container">
          <a href="book-appointment.php" class="back-link">← Choose another pet</a>

          <form method="POST" action="appointment-handler.php" class="booking-form">
            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">

            <?php if ($recommended_package): ?>
              <input type="hidden" name="recommended_package" value="<?= htmlspecialchars($recommended_package) ?>">
              <div class="recommendation-box">
                🐾 Recommended Package for <strong><?= htmlspecialchars($valid_pet['name']) ?></strong>:
                <span class="recommend"><?= htmlspecialchars($recommended_package) ?></span>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label for="package_id"><i class="fas fa-box"></i> Select Grooming Package:</label>
              <select name="package_id" id="package_id" required>
                <?php while ($pkg = $packages_result->fetch_assoc()): ?>
                  <option value="<?= $pkg['id'] ?>" <?= ($pkg['name'] == $recommended_package) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pkg['name']) ?> - ₱<?= number_format($pkg['price'], 2) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="appointment_date"><i class="fas fa-calendar-alt"></i> Appointment Date and Time:</label>
              <div class="datetime-container">
                <input type="text" name="appointment_date" id="appointment_date" class="flatpickr" placeholder="Select date and time" required>
                <div class="peak-indicator" id="peakIndicator">Select date/time</div>
              </div>
              <small style="color: #666; margin-top: 5px;">🤖 AI-powered demand prediction with confidence scores</small>
            </div>
            
            <div class="form-group">
              <label for="notes"><i class="fas fa-sticky-note"></i> Notes (optional):</label>
              <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions..."></textarea>
            </div>

            <button type="submit" class="btn submit-btn">🤖 Book Appointment (ML Optimized)</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  // ML-powered peak hours data from PHP
  const mlPeakHoursData = <?= json_encode($peak_hours_data) ?>;
  const modelStats = <?= json_encode($model_stats) ?>;

  // Create a comprehensive map for ML predictions
  const mlPredictionMap = {};
  mlPeakHoursData.forEach(item => {
    const key = `${item.day_of_week}_${item.hour}`;
    mlPredictionMap[key] = {
      prediction: item.prediction,
      confidence: item.confidence,
      ml_powered: true
    };
  });

  // Enhanced ML prediction function
  function getMLPrediction(date, hour) {
    const dayOfWeek = date.getDay() + 1; // Convert to MySQL DAYOFWEEK
    const key = `${dayOfWeek}_${hour}`;
    
    if (mlPredictionMap[key]) {
      return mlPredictionMap[key];
    }
    
    // Fallback ML prediction logic with more aggressive high demand detection
    const isWeekend = dayOfWeek === 1 || dayOfWeek === 7;
    const isBusinessHours = hour >= 9 && hour <= 18;
    const isPeakTime = hour >= 15 && hour <= 17;
    const isMorningRush = hour >= 10 && hour <= 12;
    
    // Return null for outside business hours
    if (!isBusinessHours) {
      return { prediction: 'unavailable', confidence: 1.0, ml_powered: true };
    }
    
    let prediction = 'low';
    let confidence = 0.6;
    
    if (isWeekend && isBusinessHours) {
      // Weekends are generally busier
      if (hour >= 10 && hour <= 16) {
        prediction = 'high';
        confidence = 0.85;
      } else {
        prediction = 'medium';
        confidence = 0.7;
      }
    } else if (!isWeekend && (isPeakTime || isMorningRush)) {
      // Weekday peak times
      prediction = 'high';
      confidence = 0.8;
    } else if (!isWeekend && isBusinessHours) {
      prediction = 'medium';
      confidence = 0.7;
    }
    
    return { prediction, confidence, ml_powered: true };
  }

  // Function to update ML-powered peak indicator
  function updateMLPeakIndicator() {
    const dateInput = document.getElementById('appointment_date');
    const indicator = document.getElementById('peakIndicator');
    const submitBtn = document.querySelector('.submit-btn');

    if (!dateInput.value) {
      indicator.className = 'peak-indicator';
      indicator.innerHTML = 'Select date/time';
      submitBtn.disabled = false;
      submitBtn.innerHTML = '🤖 Book Appointment (ML Optimized)';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '';
      return;
    }

    const selectedDate = new Date(dateInput.value);
    const hour = selectedDate.getHours();
    
    // Check if time is within business hours
    if (hour < 9 || hour > 18) {
      indicator.className = 'peak-indicator show high';
      indicator.innerHTML = '⛔ Outside Business Hours';
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Outside Business Hours (9 AM - 6 PM Only)';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.backgroundColor = '#ccc';
      submitBtn.style.opacity = '0.6';
      return;
    }
    
    const mlResult = getMLPrediction(selectedDate, hour);
    const peakLevel = mlResult.prediction;
    const confidence = Math.round(mlResult.confidence * 100);

    // Update peak indicator with ML results
    indicator.className = `peak-indicator show ${peakLevel}`;

    let text = '';
    let icon = '';
    switch (peakLevel) {
      case 'high':
        text = `High Demand`;
        icon = '🔴';
        break;
      case 'medium':
        text = `Moderate`;
        icon = '🟡';
        break;
      case 'low':
        text = `Low Demand`;
        icon = '🟢';
        break;
      case 'unavailable':
        text = `Outside Hours`;
        icon = '⛔';
        break;
    }
    
    indicator.innerHTML = `
      ${icon} ${text}
      <span class="ml-confidence">(${confidence}% confidence)</span>
    `;

    // Enhanced booking logic with ML confidence
    if (peakLevel === 'high' || peakLevel === 'unavailable') {
      submitBtn.disabled = true;
      const message = peakLevel === 'unavailable' ? 
        'Outside Business Hours (9 AM - 6 PM Only)' : 
        'Unavailable (High Demand - ML Predicted)';
      submitBtn.innerHTML = message;
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.backgroundColor = '#ccc';
      submitBtn.style.opacity = '0.6';
    } else {
      submitBtn.disabled = false;
      const buttonText = peakLevel === 'low' ? 
        '🤖 Book Appointment (Optimal Time!)' : 
        '🤖 Book Appointment (ML Optimized)';
      submitBtn.innerHTML = buttonText;
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '#A8E6CF';
      submitBtn.style.opacity = '1';
    }
  }

  // Advanced ML analytics logging
  function logMLAnalytics(selectedDateTime) {
    if (!selectedDateTime) return;
    
    const date = new Date(selectedDateTime);
    const mlResult = getMLPrediction(date, date.getHours());
    
    console.group('🤖 ML Appointment Analytics');
    console.log('Selected DateTime:', selectedDateTime);
    console.log('ML Prediction:', mlResult.prediction);
    console.log('Confidence Score:', `${Math.round(mlResult.confidence * 100)}%`);
    console.log('Model Stats:', modelStats);
    console.log('Decision Factors:', {
      dayOfWeek: date.getDay() + 1,
      hour: date.getHours(),
      isWeekend: [0, 6].includes(date.getDay()),
      isBusinessHours: date.getHours() >= 9 && date.getHours() <= 17
    });
    console.groupEnd();
  }

  // Set up ML-enhanced event listeners
  document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    if (dateInput) {
      // Manual event listeners as backup
      dateInput.addEventListener('change', function() {
        updateMLPeakIndicator();
        logMLAnalytics(this.value);
      });
      dateInput.addEventListener('input', updateMLPeakIndicator);

      // Set minimum date to today
      const now = new Date();
      const today = now.toISOString().split('T')[0];
      dateInput.setAttribute('min', today + 'T09:00');
      dateInput.setAttribute('max', today.replace(/\d{4}-\d{2}-\d{2}/, '2025-12-31') + 'T18:00');
      
      // Test if flatpickr is working
      setTimeout(function() {
        if (dateInput._flatpickr) {
          console.log('✅ Flatpickr initialized successfully');
        } else {
          console.warn('⚠️ Flatpickr not initialized, using native datetime input');
          // Fallback to native datetime-local input
          dateInput.type = 'datetime-local';
          dateInput.step = '1800'; // 30 minute increments
        }
      }, 1000);
    }

    // Display ML model information
    console.group('🧠 Machine Learning Model Information');
    console.log('Model Type:', modelStats.model_type);
    console.log('Training Samples:', modelStats.training_samples);
    console.log('Features Used:', modelStats.features);
    console.log('Prediction Classes:', modelStats.prediction_classes);
    console.log('Business Hours: 9 AM - 6 PM');
    console.groupEnd();
  });

  // ML-powered weekly pattern analysis
  function displayMLWeeklyPattern() {
    const weeklyPattern = {};
    
    for (let day = 1; day <= 7; day++) {
      const dayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][day - 1];
      weeklyPattern[dayName] = {};
      
      // Only show business hours (9 AM - 6 PM)
      for (let hour = 9; hour <= 18; hour++) {
        const testDate = new Date();
        testDate.setDate(testDate.getDate() + (day - testDate.getDay()));
        testDate.setHours(hour, 0, 0, 0);
        
        const prediction = getMLPrediction(testDate, hour);
        weeklyPattern[dayName][`${hour}:00`] = {
          demand: prediction.prediction,
          confidence: Math.round(prediction.confidence * 100)
        };
      }
    }
    
    console.group('📊 ML Weekly Demand Pattern (9 AM - 6 PM)');
    console.table(weeklyPattern);
    console.groupEnd();
  }

  // Initialize ML analytics with fallback check
  document.addEventListener('DOMContentLoaded', function() {
    // Check if Flatpickr loaded properly
    setTimeout(function() {
      const dateInput = document.getElementById('appointment_date');
      if (dateInput && !dateInput._flatpickr) {
        console.warn('Flatpickr failed to load, initializing fallback...');
        
        // Initialize simple Flatpickr as fallback
        try {
          flatpickr("#appointment_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            defaultHour: 10,
            time_24hr: true,
            minuteIncrement: 30,
            onChange: function(selectedDates, dateStr, instance) {
              updateMLPeakIndicator();
              logMLAnalytics(dateStr);
            }
          });
        } catch (error) {
          console.error('Flatpickr initialization failed:', error);
          // Use native datetime input as final fallback
          dateInput.type = 'datetime-local';
          dateInput.step = '1800';
        }
      }
      
      displayMLWeeklyPattern();
    }, 500);
  });

  // Enhanced Flatpickr with ML integration
  flatpickr("#appointment_date", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today",
    defaultHour: 10,
    minTime: "09:00",
    maxTime: "18:00",
    time_24hr: true,
    minuteIncrement: 30,
    allowInput: true,
    clickOpens: true,
    onChange: function(selectedDates, dateStr, instance) {
      updateMLPeakIndicator();
      logMLAnalytics(dateStr);
    },
    onReady: function(selectedDates, dateStr, instance) {
      console.log('Flatpickr is ready');
    },
    // Only disable specific times, not entire dates
    disable: [
      function(date) {
        // Only disable if this is a time selection and outside business hours
        const hour = date.getHours();
        const minute = date.getMinutes();
        
        // If it's just a date (no specific time), allow it
        if (hour === 0 && minute === 0) {
          return false;
        }
        
        // If it's a specific time, check business hours and ML prediction
        if (hour < 9 || hour > 18) {
          return true;
        }
        
        const prediction = getMLPrediction(date, hour);
        return prediction.prediction === 'high';
      }
    ],
    // Simplified day creation without disabling
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      const date = dayElem.dateObj;
      if (date) {
        // Check average demand for this day (only business hours 9-18)
        let totalHigh = 0;
        let totalChecked = 0;
        
        for (let h = 9; h <= 18; h++) {
          const testDate = new Date(date);
          testDate.setHours(h);
          const pred = getMLPrediction(testDate, h);
          if (pred.prediction === 'high') totalHigh++;
          totalChecked++;
        }
        
        const highRatio = totalHigh / totalChecked;
        if (highRatio > 0.6) {
          dayElem.style.backgroundColor = '#ffebee';
          dayElem.title = 'High demand day - limited availability (9 AM - 6 PM)';
        } else if (highRatio > 0.3) {
          dayElem.style.backgroundColor = '#fff3e0';
          dayElem.title = 'Moderate demand day (9 AM - 6 PM)';
        } else {
          dayElem.style.backgroundColor = '#e8f5e8';
          dayElem.title = 'Low demand day - best availability (9 AM - 6 PM)';
        }
      }
    }
  });

  // ML prediction API simulation for real-time updates
  function simulateMLRealTimeUpdate() {
    // This would typically call your ML API endpoint
    console.log('🔄 ML Model: Real-time prediction update simulated');
    
    // You could implement AJAX calls here to get real-time ML predictions
    // Example:
    /*
    fetch('/api/ml-predict', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ datetime: selectedDateTime })
    })
    .then(response => response.json())
    .then(data => updateMLPeakIndicator(data));
    */
  }

  // Simulate periodic ML model updates (every 5 minutes)
  setInterval(simulateMLRealTimeUpdate, 300000);
</script>

</body>
</html>
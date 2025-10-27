"""
train_analytics.py - ML Model Training for Pawsig City Analytics

This script trains decision tree models for:
1. Peak hour prediction
2. No-show prediction

Place this file in: /dashboard/session_notes/
Models will be saved to: /dashboard/session_notes/models/
"""

import numpy as np
import matplotlib.pyplot as plt
import json
import os
from datetime import datetime
import requests

class TrainingVisualizer:
    """Real-time training visualization"""
    
    def __init__(self, epochs):
        self.epochs = epochs
        self.history = {
            'epoch': [],
            'loss': [],
            'accuracy': []
        }
        
        plt.ion()
        self.fig, (self.ax1, self.ax2) = plt.subplots(1, 2, figsize=(14, 6))
        self.fig.suptitle('Training Progress', fontsize=16, fontweight='bold')
        
    def update(self, epoch, loss, accuracy):
        """Update metrics and plot"""
        self.history['epoch'].append(epoch)
        self.history['loss'].append(loss)
        self.history['accuracy'].append(accuracy)
        self._plot()
    
    def _plot(self):
        """Redraw the plots"""
        self.ax1.clear()
        self.ax2.clear()
        
        epochs = self.history['epoch']
        
        # Plot Loss
        self.ax1.plot(epochs, self.history['loss'], 'r-', linewidth=2, 
                     label='Training Loss', marker='o', markersize=8)
        self.ax1.set_xlabel('Epoch', fontsize=12, fontweight='bold')
        self.ax1.set_ylabel('Loss', fontsize=12, fontweight='bold')
        self.ax1.set_title('Model Loss', fontsize=14, fontweight='bold')
        self.ax1.legend(loc='upper right')
        self.ax1.grid(True, alpha=0.3)
        self.ax1.set_ylim(bottom=0)
        
        # Plot Accuracy
        self.ax2.plot(epochs, self.history['accuracy'], 'g-', linewidth=2, 
                     label='Training Accuracy', marker='o', markersize=8)
        self.ax2.set_xlabel('Epoch', fontsize=12, fontweight='bold')
        self.ax2.set_ylabel('Accuracy (%)', fontsize=12, fontweight='bold')
        self.ax2.set_title('Model Accuracy', fontsize=14, fontweight='bold')
        self.ax2.legend(loc='lower right')
        self.ax2.grid(True, alpha=0.3)
        self.ax2.set_ylim([0, 100])
        
        self.fig.tight_layout()
        plt.pause(0.01)
    
    def save(self, filename='training_history.png'):
        """Save the final plot"""
        self.fig.savefig(filename, dpi=300, bbox_inches='tight')
        print(f"\n✓ Training plot saved to {filename}")
    
    def close(self):
        """Close the plot"""
        plt.ioff()
        plt.close(self.fig)


class DecisionTree:
    """Decision Tree Classifier"""
    
    def __init__(self, max_depth=5, min_samples_split=2):
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.tree = None
        
    def gini_impurity(self, y):
        """Calculate Gini impurity"""
        if len(y) == 0:
            return 0
        classes, counts = np.unique(y, return_counts=True)
        probabilities = counts / len(y)
        return 1 - np.sum(probabilities ** 2)
    
    def split_data(self, X, y, feature, threshold):
        """Split data based on feature and threshold"""
        left_mask = X[:, feature] <= threshold
        right_mask = ~left_mask
        return X[left_mask], X[right_mask], y[left_mask], y[right_mask]
    
    def find_best_split(self, X, y):
        """Find the best feature and threshold to split on"""
        best_gain = -1
        best_feature = None
        best_threshold = None
        
        parent_impurity = self.gini_impurity(y)
        n_features = X.shape[1]
        
        for feature in range(n_features):
            thresholds = np.unique(X[:, feature])
            for threshold in thresholds:
                X_left, X_right, y_left, y_right = self.split_data(X, y, feature, threshold)
                
                if len(y_left) == 0 or len(y_right) == 0:
                    continue
                
                n = len(y)
                weighted_impurity = (len(y_left) / n) * self.gini_impurity(y_left) + \
                                   (len(y_right) / n) * self.gini_impurity(y_right)
                gain = parent_impurity - weighted_impurity
                
                if gain > best_gain:
                    best_gain = gain
                    best_feature = feature
                    best_threshold = threshold
        
        return best_feature, best_threshold
    
    def build_tree(self, X, y, depth=0):
        """Recursively build the decision tree"""
        n_samples, n_features = X.shape
        n_classes = len(np.unique(y))
        
        # Stopping criteria
        if depth >= self.max_depth or n_classes == 1 or n_samples < self.min_samples_split:
            leaf_value = np.bincount(y.astype(int)).argmax()
            return {'leaf': True, 'value': int(leaf_value)}
        
        # Find best split
        best_feature, best_threshold = self.find_best_split(X, y)
        
        if best_feature is None:
            leaf_value = np.bincount(y.astype(int)).argmax()
            return {'leaf': True, 'value': int(leaf_value)}
        
        # Split and recurse
        X_left, X_right, y_left, y_right = self.split_data(X, y, best_feature, best_threshold)
        
        left_subtree = self.build_tree(X_left, y_left, depth + 1)
        right_subtree = self.build_tree(X_right, y_right, depth + 1)
        
        return {
            'leaf': False,
            'feature': int(best_feature),
            'threshold': float(best_threshold),
            'left': left_subtree,
            'right': right_subtree
        }
    
    def fit(self, X, y, epochs=5, visualizer=None):
        """Train the decision tree"""
        print(f"\n{'='*60}")
        print(f"Training Decision Tree")
        print(f"{'='*60}")
        print(f"Samples: {len(X)}, Features: {X.shape[1]}, Epochs: {epochs}")
        print(f"Max Depth: {self.max_depth}, Min Samples Split: {self.min_samples_split}")
        print(f"{'='*60}\n")
        
        for epoch in range(1, epochs + 1):
            # Build tree
            self.tree = self.build_tree(X, y)
            
            # Calculate metrics
            predictions = np.array([self.predict_single(x) for x in X])
            accuracy = np.mean(predictions == y) * 100
            loss = self.gini_impurity(y)
            
            # Console output
            print(f"Epoch {epoch}/{epochs}")
            print(f"  {'─'*50}")
            print(f"  Loss: {loss:.4f} | Accuracy: {accuracy:.2f}%")
            
            # Update visualization
            if visualizer:
                visualizer.update(epoch, loss, accuracy)
            
            # Simulate computation time
            import time
            time.sleep(0.3)
        
        print(f"\n{'='*60}")
        print(f"✓ Training Complete!")
        print(f"  Final Accuracy: {accuracy:.2f}%")
        print(f"  Final Loss: {loss:.4f}")
        print(f"{'='*60}\n")
        
        return self
    
    def predict_single(self, x):
        """Predict single sample"""
        node = self.tree
        while not node['leaf']:
            if x[node['feature']] <= node['threshold']:
                node = node['left']
            else:
                node = node['right']
        return node['value']
    
    def predict(self, X):
        """Predict multiple samples"""
        return np.array([self.predict_single(x) for x in X])
    
    def save_model(self, filename):
        """Save model to JSON file"""
        with open(filename, 'w') as f:
            json.dump(self.tree, f, indent=2)
        print(f"✓ Model saved to {filename}")


def fetch_appointments_from_supabase():
    """Fetch appointment data from Supabase"""
    SUPABASE_URL = 'https://pgapbbukmyitwuvfbgho.supabase.co'
    SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A'
    
    print("\n📡 Fetching appointments from Supabase...")
    
    try:
        response = requests.get(
            f'{SUPABASE_URL}/rest/v1/appointments?select=*',
            headers={
                'apikey': SUPABASE_KEY,
                'Authorization': f'Bearer {SUPABASE_KEY}'
            }
        )
        
        if response.status_code == 200:
            appointments = response.json()
            print(f"✓ Fetched {len(appointments)} appointments")
            return appointments
        else:
            print(f"✗ Error fetching data: {response.status_code}")
            return []
    except Exception as e:
        print(f"✗ Error: {e}")
        return []


def prepare_peak_hour_data(appointments):
    """Prepare data for peak hour prediction"""
    X = []
    y = []
    
    for apt in appointments:
        try:
            date = datetime.fromisoformat(apt['appointment_date'].replace('Z', '+00:00'))
            day_of_week = date.weekday()  # 0=Monday, 6=Sunday
            day_of_month = date.day
            month = date.month
            hour = date.hour
            
            X.append([day_of_week, day_of_month, month])
            y.append(hour)
        except:
            continue
    
    return np.array(X), np.array(y)


def prepare_noshow_data(appointments):
    """Prepare data for no-show prediction"""
    X = []
    y = []
    
    for apt in appointments:
        try:
            date = datetime.fromisoformat(apt['appointment_date'].replace('Z', '+00:00'))
            day_of_week = date.weekday()
            hour = date.hour
            day_of_month = date.day
            
            X.append([day_of_week, hour, day_of_month])
            
            # 1 if no-show, 0 otherwise
            is_noshow = 1 if apt.get('status') == 'no_show' else 0
            y.append(is_noshow)
        except:
            continue
    
    return np.array(X), np.array(y)


def main():
    """Main training pipeline"""
    print("\n" + "="*60)
    print("🧠 ML MODEL TRAINING PIPELINE")
    print("   Pawsig City Analytics")
    print("="*60)
    
    # Fetch data
    appointments = fetch_appointments_from_supabase()
    
    if len(appointments) < 5:
        print("\n✗ Not enough data to train models (need at least 5 appointments)")
        input("\nPress Enter to exit...")
        return
    
    # Get script directory and create models folder
    script_dir = os.path.dirname(os.path.abspath(__file__))
    models_dir = os.path.join(script_dir, 'models')
    os.makedirs(models_dir, exist_ok=True)
    print(f"\n📁 Models will be saved to: {models_dir}")
    
    # ==========================================
    # Train Peak Hour Prediction Model
    # ==========================================
    print("\n" + "="*60)
    print("📊 TRAINING PEAK HOUR PREDICTION MODEL")
    print("="*60)
    
    X_peak, y_peak = prepare_peak_hour_data(appointments)
    print(f"\nDataset: {len(X_peak)} samples")
    print(f"Features: [day_of_week, day_of_month, month]")
    print(f"Target: hour (0-23)")
    
    viz_peak = TrainingVisualizer(epochs=5)
    peak_model = DecisionTree(max_depth=5, min_samples_split=2)
    peak_model.fit(X_peak, y_peak, epochs=5, visualizer=viz_peak)
    
    peak_model_path = os.path.join(models_dir, 'peak_hour_model.json')
    peak_model.save_model(peak_model_path)
    
    peak_plot_path = os.path.join(models_dir, 'peak_hour_training.png')
    viz_peak.save(peak_plot_path)
    
    # ==========================================
    # Train No-Show Prediction Model
    # ==========================================
    print("\n" + "="*60)
    print("🚫 TRAINING NO-SHOW PREDICTION MODEL")
    print("="*60)
    
    X_noshow, y_noshow = prepare_noshow_data(appointments)
    print(f"\nDataset: {len(X_noshow)} samples")
    print(f"Features: [day_of_week, hour, day_of_month]")
    print(f"Target: no_show (0 or 1)")
    print(f"No-shows: {np.sum(y_noshow)} ({np.mean(y_noshow)*100:.1f}%)")
    
    viz_noshow = TrainingVisualizer(epochs=5)
    noshow_model = DecisionTree(max_depth=5, min_samples_split=2)
    noshow_model.fit(X_noshow, y_noshow, epochs=5, visualizer=viz_noshow)
    
    noshow_model_path = os.path.join(models_dir, 'noshow_model.json')
    noshow_model.save_model(noshow_model_path)
    
    noshow_plot_path = os.path.join(models_dir, 'noshow_training.png')
    viz_noshow.save(noshow_plot_path)
    
    # Save training metadata
    metadata = {
        'trained_at': datetime.now().isoformat(),
        'total_appointments': len(appointments),
        'peak_hour_samples': len(X_peak),
        'noshow_samples': len(X_noshow),
        'noshow_rate': float(np.mean(y_noshow) * 100)
    }
    
    metadata_path = os.path.join(models_dir, 'training_metadata.json')
    with open(metadata_path, 'w') as f:
        json.dump(metadata, f, indent=2)
    
    print("\n" + "="*60)
    print("✅ ALL MODELS TRAINED SUCCESSFULLY!")
    print("="*60)
    print(f"\n📁 Saved files in: {models_dir}")
    print("  • peak_hour_model.json")
    print("  • peak_hour_training.png")
    print("  • noshow_model.json")
    print("  • noshow_training.png")
    print("  • training_metadata.json")
    print("\n🎉 Models are ready to be used by your PHP application!")
    print("\n📌 Next steps:")
    print("   1. Refresh your PHP analytics page")
    print("   2. Models will be automatically loaded")
    print("   3. Predictions will appear on future dates")
    print("="*60 + "\n")
    
    # Keep plots open
    input("Press Enter to close the plots and exit...")
    viz_peak.close()
    viz_noshow.close()


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n⚠️  Training interrupted by user")
    except Exception as e:
        print(f"\n\n❌ Error during training: {e}")
        import traceback
        traceback.print_exc()
        input("\nPress Enter to exit...")
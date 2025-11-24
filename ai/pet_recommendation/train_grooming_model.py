import pandas as pd
from sklearn.tree import DecisionTreeClassifier
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import pickle
import os

print("Running from:", os.getcwd())

# Load dataset
df = pd.read_csv("pet_grooming_dataset.csv")

print("Columns in dataset:", df.columns.tolist())
print(f"Total samples: {len(df)}\n")

# Encode categorical features
le_breed = LabelEncoder()
le_package = LabelEncoder()

df["breed_encoded"] = le_breed.fit_transform(df["Breed"])
df["package_encoded"] = le_package.fit_transform(df["Package"])

# Features and target
X = df[["breed_encoded"]]   # Only breed as feature
y = df["package_encoded"]

# Split data into training and testing sets (80-20 split)
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

print(f"Training samples: {len(X_train)}")
print(f"Testing samples: {len(X_test)}\n")

# Train model
clf = DecisionTreeClassifier(random_state=42)
clf.fit(X_train, y_train)

# ===== TRAINING ACCURACY =====
train_predictions = clf.predict(X_train)
train_accuracy = accuracy_score(y_train, train_predictions)
print(f" Training Accuracy: {train_accuracy * 100:.2f}%")

# ===== TESTING ACCURACY =====
test_predictions = clf.predict(X_test)
test_accuracy = accuracy_score(y_test, test_predictions)
print(f" Testing Accuracy: {test_accuracy * 100:.2f}%\n")

# ===== CROSS-VALIDATION ACCURACY =====
cv_scores = cross_val_score(clf, X, y, cv=5)
print(f"  Cross-Validation Accuracy (5-fold):")
print(f"   Mean: {cv_scores.mean() * 100:.2f}%")
print(f"   Std Dev: {cv_scores.std() * 100:.2f}%")
print(f"   All folds: {[f'{score*100:.2f}%' for score in cv_scores]}\n")

# ===== CLASSIFICATION REPORT =====
print(" Classification Report (Test Set):")
print(classification_report(
    y_test, 
    test_predictions, 
    target_names=le_package.classes_,
    zero_division=0
))

# ===== CONFUSION MATRIX =====
print(" Confusion Matrix (Test Set):")
cm = confusion_matrix(y_test, test_predictions)
print(cm)
print(f"\nPackage labels: {le_package.classes_.tolist()}\n")

# ===== BREED-PACKAGE MAPPING ANALYSIS =====
print(" Breed to Package Mapping:")
breed_package_map = df.groupby('Breed')['Package'].agg(lambda x: x.mode()[0] if not x.mode().empty else x.iloc[0])
for breed, package in breed_package_map.items():
    print(f"   {breed} → {package}")

# ===== SAVE MODEL AND ENCODERS =====
with open("grooming_model.pkl", "wb") as f:
    pickle.dump(clf, f)

with open("le_breed.pkl", "wb") as f:
    pickle.dump(le_breed, f)

with open("le_package.pkl", "wb") as f:
    pickle.dump(le_package, f)

# Save accuracy metrics
accuracy_metrics = {
    'train_accuracy': train_accuracy,
    'test_accuracy': test_accuracy,
    'cv_mean_accuracy': cv_scores.mean(),
    'cv_std_accuracy': cv_scores.std(),
    'cv_scores': cv_scores.tolist()
}

with open("model_accuracy.pkl", "wb") as f:
    pickle.dump(accuracy_metrics, f)

print(" Model trained and saved successfully with accuracy metrics!")
print(f" Model accuracy: {test_accuracy * 100:.2f}%")
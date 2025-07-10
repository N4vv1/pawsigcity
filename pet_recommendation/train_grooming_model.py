import pandas as pd
from sklearn.tree import DecisionTreeClassifier
from sklearn.preprocessing import LabelEncoder
import pickle
import os

print("Running from:", os.getcwd())

# Load dataset
df = pd.read_csv("pet_grooming_dataset.csv")

print("Columns in dataset:", df.columns.tolist())

# Encode categorical features
le_breed = LabelEncoder()
le_gender = LabelEncoder()
le_package = LabelEncoder()

df["breed_encoded"] = le_breed.fit_transform(df["Breed"])
df["gender_encoded"] = le_gender.fit_transform(df["Gender"])
df["package_encoded"] = le_package.fit_transform(df["Package"])

# Features and target
X = df[["breed_encoded", "gender_encoded"]].assign(age=df["Age"])
y = df["package_encoded"]

# Train model
clf = DecisionTreeClassifier()
clf.fit(X, y)

# Save model and encoders
with open("grooming_model.pkl", "wb") as f:
    pickle.dump(clf, f)

with open("le_breed.pkl", "wb") as f:
    pickle.dump(le_breed, f)

with open("le_gender.pkl", "wb") as f:
    pickle.dump(le_gender, f)

with open("le_package.pkl", "wb") as f:
    pickle.dump(le_package, f)

print("Model and encoders saved.")

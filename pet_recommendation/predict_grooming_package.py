import pickle

# Load model and encoders
with open("grooming_model.pkl", "rb") as f:
    model = pickle.load(f)

with open("le_breed.pkl", "rb") as f:
    le_breed = pickle.load(f)

with open("le_gender.pkl", "rb") as f:
    le_gender = pickle.load(f)

with open("le_package.pkl", "rb") as f:
    le_package = pickle.load(f)

# Encode inputs
try:
    breed_encoded = le_breed.transform([input_breed])[0]
    gender_encoded = le_gender.transform([input_gender])[0]
except ValueError as e:
    print("Input value not found in training data:", e)
    exit()

# Predict
prediction = model.predict([[breed_encoded, gender_encoded, input_age]])
predicted_package = le_package.inverse_transform(prediction)[0]

print(f"Recommended grooming package: {predicted_package}")

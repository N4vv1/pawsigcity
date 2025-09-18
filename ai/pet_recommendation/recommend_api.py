from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import logging

# Initialize app
app = Flask(__name__)
CORS(app)  # Allow cross-origin requests if calling from browser or external server

# Set up basic logging
logging.basicConfig(filename="recommendation_api.log", level=logging.INFO, format="%(asctime)s - %(message)s")

# Load model and encoders
try:
    with open("grooming_model.pkl", "rb") as f:
        model = pickle.load(f)
    with open("le_breed.pkl", "rb") as f:
        le_breed = pickle.load(f)
    with open("le_gender.pkl", "rb") as f:
        le_gender = pickle.load(f)
    with open("le_package.pkl", "rb") as f:
        le_package = pickle.load(f)
except Exception as e:
    logging.error(f"Failed to load model or encoders: {e}")
    raise

@app.route("/recommend", methods=["POST"])
def recommend_package():
    data = request.get_json()

    breed = data.get("breed")
    gender = data.get("gender")
    age = data.get("age")

    if not breed or not gender or age is None:
        return jsonify({"error": "Missing breed, gender, or age."}), 400

    try:
        breed_encoded = le_breed.transform([breed])[0]
        gender_encoded = le_gender.transform([gender])[0]
        age = int(age)

        prediction = model.predict([[breed_encoded, gender_encoded, age]])
        recommended = le_package.inverse_transform(prediction)[0]

        # Log prediction
        logging.info(f"Input: {data}, Recommended: {recommended}")

        return jsonify({"recommended_package": recommended})

    except Exception as e:
        logging.error(f"Prediction error: {str(e)}")
        return jsonify({"error": f"Could not predict: {str(e)}"}), 500

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000)

from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import logging

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Set up logging
logging.basicConfig(filename="recommendation_api.log", level=logging.INFO, format="%(asctime)s - %(message)s")

# Load model and encoders
try:
    with open("grooming_model.pkl", "rb") as f:
        model = pickle.load(f)
    with open("le_breed.pkl", "rb") as f:
        le_breed = pickle.load(f)
    with open("le_package.pkl", "rb") as f:
        le_package = pickle.load(f)
except Exception as e:
    logging.error(f"Failed to load model or encoders: {e}")
    raise

@app.route("/recommend", methods=["POST"])
def recommend_package():
    data = request.get_json()
    breed = data.get("breed")

    if not breed:
        return jsonify({"error": "Missing breed."}), 400

    try:
        # Encode breed
        breed_encoded = le_breed.transform([breed])[0]

        # Predict grooming package
        prediction = model.predict([[breed_encoded]])
        recommended = le_package.inverse_transform(prediction)[0]

        # Log prediction
        logging.info(f"Input: {data}, Recommended: {recommended}")

        return jsonify({"recommended_package": recommended})

    except ValueError as e:
        # This happens if the breed isn't in the encoder
        logging.error(f"Unknown breed: {breed}")
        return jsonify({"error": f"Unknown breed: {breed}"}), 400

    except Exception as e:
        logging.error(f"Prediction error: {str(e)}")
        return jsonify({"error": f"Could not predict: {str(e)}"}), 500

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000)

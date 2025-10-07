from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import os
import logging

app = Flask(__name__)
CORS(app)

# --------------------------------------------------------
# Setup logging
# --------------------------------------------------------
logging.basicConfig(
    filename="ai_api.log",
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

# --------------------------------------------------------
# Load models (from ai/models/)
# --------------------------------------------------------
base_dir = os.path.dirname(os.path.abspath(__file__))
models_dir = os.path.join(base_dir, "pet_recommendation")

# Grooming recommendation model
try:
    with open(os.path.join(models_dir, "grooming_model.pkl"), "rb") as f:
        grooming_model = pickle.load(f)
    with open(os.path.join(models_dir, "le_breed.pkl"), "rb") as f:
        le_breed = pickle.load(f)
    with open(os.path.join(models_dir, "le_package.pkl"), "rb") as f:
        le_package = pickle.load(f)
except Exception as e:
    logging.error(f"Failed to load grooming model or encoders: {e}")
    grooming_model = None

# Sentiment analysis model
try:
    with open(os.path.join(models_dir, "svm_sentiment_model.pkl"), "rb") as f:
        sentiment_model = pickle.load(f)
    with open(os.path.join(models_dir, "tfidf_vectorizer.pkl"), "rb") as f:
        vectorizer = pickle.load(f)
except Exception as e:
    logging.error(f"Failed to load sentiment model/vectorizer: {e}")
    sentiment_model, vectorizer = None, None

# --------------------------------------------------------
# Routes
# --------------------------------------------------------

# Grooming Package Recommendation
@app.route("/recommend", methods=["POST"])
def recommend_package():
    if grooming_model is None:
        return jsonify({"error": "Recommendation model not available"}), 500

    data = request.get_json()
    breed = data.get("breed")

    if not breed:
        return jsonify({"error": "Missing breed."}), 400

    try:
        breed_encoded = le_breed.transform([breed])[0]

        prediction = grooming_model.predict([[breed_encoded]])
        recommended = le_package.inverse_transform(prediction)[0]

        logging.info(f"Input: {data}, Recommended: {recommended}")
        return jsonify({"recommended_package": recommended})

    except Exception as e:
        logging.error(f"Prediction error: {str(e)}")
        return jsonify({"error": f"Could not predict: {str(e)}"}), 500


# Sentiment Analysis
@app.route("/sentiment", methods=["POST"])
def sentiment_analysis():
    if sentiment_model is None or vectorizer is None:
        return jsonify({"error": "Sentiment model not available"}), 500

    data = request.get_json()
    text = data.get("feedback", "")

    if not text.strip():
        return jsonify({"error": "No feedback text provided"}), 400

    try:
        X = vectorizer.transform([text])
        prediction = sentiment_model.predict(X)[0].strip().lower()

        if prediction not in ["positive", "neutral", "negative"]:
            prediction = "neutral"

        logging.info(f"Feedback: {text} => Sentiment: {prediction}")
        return jsonify({"sentiment": prediction})

    except Exception as e:
        logging.error(f"Sentiment error: {str(e)}")
        return jsonify({"error": f"Could not analyze sentiment: {str(e)}"}), 500


# Chatbot (placeholder)
@app.route("/ask", methods=["POST"])
def ask():
    data = request.get_json()
    msg = data.get("message", "").lower()
    # TODO: integrate chatbot_model.pkl later
    return jsonify({"response": "I'm still learning, but I will try to help!"})


# --------------------------------------------------------
# Run the app
# --------------------------------------------------------
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)

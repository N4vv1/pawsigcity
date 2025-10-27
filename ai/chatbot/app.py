from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import os

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Load trained model
model = None
vectorizer = None

def load_model():
    global model, vectorizer
    try:
        if os.path.exists('chatbot_model.pkl'):
            with open('chatbot_model.pkl', 'rb') as f:
                model, vectorizer = pickle.load(f)
            print("✅ Model loaded successfully!")
        else:
            print("❌ Model file not found. Please run train_chatbot.py first.")
    except Exception as e:
        print(f"❌ Error loading model: {e}")

@app.route('/ask', methods=['POST'])
def ask():
    try:
        data = request.json
        user_message = data.get('message', '')
        
        if not user_message:
            return jsonify({'response': 'Please send a message.'})
        
        if model is None or vectorizer is None:
            return jsonify({'response': 'Model not loaded. Please train the model first.'})
        
        # Vectorize and predict
        X = vectorizer.transform([user_message])
        response = model.predict(X)[0]
        
        return jsonify({'response': response})
    except Exception as e:
        return jsonify({'response': f'Error: {str(e)}'})

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'model_loaded': model is not None})

if __name__ == '__main__':
    load_model()
    print("🚀 Starting Flask server on http://127.0.0.1:5000")
    app.run(debug=True, port=5000, host='127.0.0.1')
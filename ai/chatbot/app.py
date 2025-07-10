from flask import Flask, request, jsonify, render_template
import pickle

app = Flask(__name__)

# Load model and vectorizer
with open('chatbot_model.pkl', 'rb') as f:
    model, vectorizer = pickle.load(f)

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/ask', methods=['POST'])
def ask():
    user_msg = request.json['message']
    vec_msg = vectorizer.transform([user_msg])
    response = model.predict(vec_msg)[0]
    return jsonify({'response': response})

if __name__ == '__main__':
    app.run(debug=True)

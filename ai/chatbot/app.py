from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route("/ask", methods=["POST"])
def ask():
    data = request.get_json()
    msg = data.get("message", "").lower()
    # Fallback logic
    return jsonify(response="I'm still learning, but I will try to help!")

if __name__ == "__main__":
    app.run(debug=True)

from flask import Flask, request, jsonify
import pickle

app = Flask(__name__)

# Load model and encoders
with open("grooming_model.pkl", "rb") as f:
    model = pickle.load(f)
with open("le_breed.pkl", "rb") as f:
    le_breed = pickle.load(f)
with open("le_gender.pkl", "rb") as f:
    le_gender = pickle.load(f)
with open("le_package.pkl", "rb") as f:
    le_package = pickle.load(f)

@app.route("/recommend", methods=["POST"])
def recommend_package():
    data = request.get_json()
    breed = data["breed"]
    gender = data["gender"]
    age = int(data["age"])

    try:
        breed_encoded = le_breed.transform([breed])[0]
        gender_encoded = le_gender.transform([gender])[0]
        prediction = model.predict([[breed_encoded, gender_encoded, age]])
        recommended = le_package.inverse_transform(prediction)[0]
        return jsonify({"recommended_package": recommended})
    except Exception as e:
        return jsonify({"error": str(e)}), 400

if __name__ == "__main__":
    app.run(port=5000)

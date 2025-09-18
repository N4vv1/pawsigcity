import requests

url = "http://localhost:5000/recommend"

data = {
    "breed": "Shih Tzu",
    "gender": "Male",
    "age": 6
}

try:
    response = requests.post(url, json=data)
    response.raise_for_status()
    print("Recommended package:", response.json()["recommended_package"])
except requests.exceptions.RequestException as e:
    print("Error:", e)

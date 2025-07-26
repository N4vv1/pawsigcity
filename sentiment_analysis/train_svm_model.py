import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import SVC
import pickle

# Load labeled training data
df = pd.read_csv("feedback_dataset.csv")  # has 'feedback' and 'sentiment' columns

# Vectorize text
vectorizer = TfidfVectorizer()
X = vectorizer.fit_transform(df["feedback"])
y = df["sentiment"]

# Train SVM
model = SVC(kernel='linear')
model.fit(X, y)

# Save model and vectorizer
with open("svm_sentiment_model.pkl", "wb") as f:
    pickle.dump(model, f)
with open("tfidf_vectorizer.pkl", "wb") as f:
    pickle.dump(vectorizer, f)

print("Model and vectorizer saved.")

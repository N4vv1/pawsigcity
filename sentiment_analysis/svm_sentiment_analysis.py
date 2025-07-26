import os
import mysql.connector
import pickle

# Get absolute path of the current script directory
base_dir = os.path.dirname(os.path.abspath(__file__))

# Load SVM model and TF-IDF vectorizer using full paths
with open(os.path.join(base_dir, "svm_sentiment_model.pkl"), "rb") as f:
    model = pickle.load(f)
with open(os.path.join(base_dir, "tfidf_vectorizer.pkl"), "rb") as f:
    vectorizer = pickle.load(f)

# Connect to the database
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='pet_grooming_system'
)
cursor = conn.cursor()

# Fetch feedback where sentiment is NULL or invalid
cursor.execute("""
    SELECT appointment_id, feedback, rating 
    FROM appointments 
    WHERE feedback IS NOT NULL AND (sentiment IS NULL OR sentiment IN ('pending', '', ' '))
""")
feedback_data = cursor.fetchall()
print(f"Found {len(feedback_data)} feedback(s) to analyze.")

# Analyze each feedback using SVM
for appointment_id, feedback, rating in feedback_data:
    X_test = vectorizer.transform([feedback])
    sentiment = model.predict(X_test)[0].strip().lower()

    if sentiment in ['positive', 'neutral', 'negative']:
        cursor.execute(
            "UPDATE appointments SET sentiment = %s WHERE appointment_id = %s",
            (sentiment, appointment_id)
        )

        print(f"[Appointment #{appointment_id}] Feedback: \"{feedback}\"")
        print(f"=> SVM Sentiment: {sentiment}")
        print("-" * 60)

conn.commit()
cursor.close()
conn.close()
print("Sentiment analysis completed and database updated.")

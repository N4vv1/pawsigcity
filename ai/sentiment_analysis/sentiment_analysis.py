import psycopg2
import os
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer

# Connect to the database
conn = psycopg2.connect(
    host=os.getenv("DB_HOST"),
    dbname=os.getenv("DB_NAME"),
    user=os.getenv("DB_USER"),
    password=os.getenv("DB_PASS"),
    port=os.getenv("DB_PORT", "5432")  # fallback to 5432 if not set
)

cursor = conn.cursor()

# Fetch feedback with NULL sentiment
cursor.execute("SELECT appointment_id, feedback, rating FROM appointments WHERE feedback IS NOT NULL AND sentiment IS NULL")
feedback_data = cursor.fetchall()

# Initialize analyzer
analyzer = SentimentIntensityAnalyzer()

# Process each feedback
for appointment_id, feedback, rating in feedback_data:
    scores = analyzer.polarity_scores(feedback)
    compound = scores['compound']
    pos = scores['pos']
    neu = scores['neu']
    neg = scores['neg']

    # Classification logic
    if compound >= 0.05:
        sentiment = 'positive'
    elif compound <= -0.05:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'

    # Override based on rating if needed
    if rating == 1 and sentiment == 'neutral':
        sentiment = 'negative'

    print(f"[Appointment #{appointment_id}] Feedback: \"{feedback}\"")
    print(f"Scores => Compound: {compound}, Pos: {pos}, Neu: {neu}, Neg: {neg}, Rating: {rating}")
    print(f"=> Sentiment: {sentiment}")
    print("-" * 60)

    # Update database
    cursor.execute(
        "UPDATE appointments SET sentiment = %s WHERE appointment_id = %s",
        (sentiment, appointment_id)
    )

# Commit and close
conn.commit()
cursor.close()
conn.close()

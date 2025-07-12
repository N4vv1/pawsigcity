import mysql.connector
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer

# Connect to the database
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='pet_grooming_system'
)
cursor = conn.cursor()

# Fetch feedback with NULL sentiment (to avoid reprocessing)
cursor.execute("SELECT appointment_id, feedback, rating FROM appointments WHERE feedback IS NOT NULL AND sentiment IS NULL")
feedback_data = cursor.fetchall()

# Initialize VADER
analyzer = SentimentIntensityAnalyzer()

# Analyze and update sentiment
for appointment_id, feedback, rating in feedback_data:
    score = analyzer.polarity_scores(feedback)
    compound = score['compound']

    # Classify sentiment using VADER
    if compound >= 0.05:
        sentiment = 'positive'
    elif compound <= -0.05:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'

    # Override if feedback is neutral but rating is very low
    if rating == 1 and sentiment == 'neutral':
        sentiment = 'negative'

    # Debug output
    print(f"Appointment #{appointment_id}: {sentiment} (Score: {compound}, Rating: {rating})")

    # Update sentiment in database
    cursor.execute(
        "UPDATE appointments SET sentiment = %s WHERE appointment_id = %s",
        (sentiment, appointment_id)
    )

# Commit changes and close connection
conn.commit()
cursor.close()
conn.close()

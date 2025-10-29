import psycopg2
import os
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer

# Connect to the database (Supabase/PostgreSQL)
conn = psycopg2.connect(
    host="aws-0-us-east-2.pooler.supabase.com",
    port="6543",
    dbname="postgres",
    user="postgres.pgapbbukmyitwuvfbgho",
    password="pawsigcity2025",
    sslmode="require"
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

# Initialize VADER analyzer
analyzer = SentimentIntensityAnalyzer()

# Analyze each feedback using VADER
for feedback_id, comment, rating in feedback_data:
    # Get sentiment scores
    scores = analyzer.polarity_scores(comment)
    compound = scores['compound']
    pos = scores['pos']
    neu = scores['neu']
    neg = scores['neg']
    
    # Classification logic based on compound score
    if compound >= 0.05:
        sentiment = 'positive'
    elif compound <= -0.05:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'
    
    # Optional: Override based on rating if available
    # Adjust sentiment if rating strongly disagrees with VADER
    if rating is not None:
        if rating <= 2 and sentiment == 'positive':
            sentiment = 'negative'
        elif rating >= 4 and sentiment == 'negative':
            sentiment = 'positive'
    
    # Update database
    cursor.execute(
        "UPDATE feedback SET sentiment = %s WHERE id = %s",
        (sentiment, feedback_id)
    )
    
    print(f"[Feedback #{feedback_id}] Comment: \"{comment}\"")
    print(f"Scores => Compound: {compound:.3f}, Pos: {pos:.3f}, Neu: {neu:.3f}, Neg: {neg:.3f}, Rating: {rating}")
    print(f"=> VADER Sentiment: {sentiment}")
    print("-" * 60)

# Commit and close
conn.commit()
cursor.close()
conn.close()
print("✅ Sentiment analysis completed and database updated.")
import psycopg2
import os
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer


conn = psycopg2.connect(
    host="aws-0-us-east-2.pooler.supabase.com",
    port="6543",
    dbname="postgres",
    user="postgres.pgapbbukmyitwuvfbgho",
    password="pawsigcity2025",
    sslmode="require"
)
cursor = conn.cursor()


cursor.execute("""
    SELECT appointment_id, feedback, rating 
    FROM appointments 
    WHERE feedback IS NOT NULL AND (sentiment IS NULL OR sentiment IN ('pending', '', ' '))
""")
feedback_data = cursor.fetchall()
print(f"Found {len(feedback_data)} feedback(s) to analyze.")


analyzer = SentimentIntensityAnalyzer()


for feedback_id, comment, rating in feedback_data:
    
    scores = analyzer.polarity_scores(comment)
    compound = scores['compound']
    pos = scores['pos']
    neu = scores['neu']
    neg = scores['neg']
    
    
    if compound >= 0.05:
        sentiment = 'positive'
    elif compound <= -0.05:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'
    
    
    if rating is not None:
        if rating <= 2 and sentiment == 'positive':
            sentiment = 'negative'
        elif rating >= 4 and sentiment == 'negative':
            sentiment = 'positive'
    
    
    cursor.execute(
        "UPDATE appointments SET sentiment = %s WHERE appointment_id = %s",
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
print("Sentiment analysis completed and database updated.")
import pandas as pd
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.naive_bayes import MultinomialNB
import pickle

# Load dataset
df = pd.read_csv('intents.csv')

# Vectorize questions
vectorizer = CountVectorizer()
X = vectorizer.fit_transform(df['question'])
y = df['answer']

# Train model
model = MultinomialNB()
model.fit(X, y)

# Save model & vectorizer
with open('chatbot_model.pkl', 'wb') as f:
    pickle.dump((model, vectorizer), f)

print("✅ Chatbot trained and saved!")

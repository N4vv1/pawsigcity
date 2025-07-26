import mysql.connector
import pandas as pd

# Connect to the database
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='pet_grooming_system'
)
cursor = conn.cursor()

# Fetch feedback with manually labeled sentiment (non-NULL)
cursor.execute("""
    SELECT feedback, sentiment 
    FROM appointments 
    WHERE feedback IS NOT NULL AND sentiment IS NOT NULL
""")

# Fetch all rows
rows = cursor.fetchall()

# Create DataFrame
df = pd.DataFrame(rows, columns=["feedback", "sentiment"])

# Optional: Clean up whitespace, drop duplicates or empty rows
df["feedback"] = df["feedback"].str.strip()
df.drop_duplicates(subset="feedback", inplace=True)
df.dropna(subset=["feedback", "sentiment"], inplace=True)

# Save to CSV
df.to_csv("feedback_dataset.csv", index=False)
print("✅ Exported to feedback_dataset.csv")

# Close DB
cursor.close()
conn.close()

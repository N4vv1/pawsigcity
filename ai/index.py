import pandas as pd
from sklearn.ensemble import RandomForestRegressor
import numpy as np

inventory = pd.DataFrame(
    [
        {
            "pet": "Dog",
            "size": 5,
            "energy": 5,
            "lifespan": 13,
            "trainability": 5,
            "in_stock": False,
        },
        {
            "pet": "Cat",
            "size": 3,
            "energy": 3,
            "lifespan": 15,
            "trainability": 3,
            "in_stock": True,
        },
        {
            "pet": "Rabbit",
            "size": 2,
            "energy": 2,
            "lifespan": 8,
            "trainability": 2,
            "in_stock": True,
        },
        {
            "pet": "Parrot",
            "size": 1,
            "energy": 3,
            "lifespan": 20,
            "trainability": 4,
            "in_stock": True,
        },
        {
            "pet": "Hamster",
            "size": 1,
            "energy": 4,
            "lifespan": 3,
            "trainability": 1,
            "in_stock": True,
        },
    ]
)

preferred_pet = inventory[inventory["pet"] == "Dog"].iloc[0]
preferred_features = (
    preferred_pet[["size", "energy", "lifespan", "trainability"]]
    .to_numpy()
    .reshape(1, -1)
)

X = inventory[["size", "energy", "lifespan", "trainability"]]
y = range(len(X))  # Use index as dummy regression target
model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)

inventory["score"] = model.predict(
    inventory[["size", "energy", "lifespan", "trainability"]]
)
preferred_score = model.predict(preferred_features)[0]
inventory["similarity"] = np.abs(inventory["score"] - preferred_score)

recommendations = (
    inventory[inventory["in_stock"] & (inventory["pet"] != "Dog")]
    .sort_values("similarity")
    .head(3)
)

print("Recommended alternatives based on your preferred pet:")
for i, row in recommendations.iterrows():
    print(f"🐾 {row['pet']} (Energy: {row['energy']}, Lifespan: {row['lifespan']} yrs)")

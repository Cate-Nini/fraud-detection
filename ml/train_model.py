import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import StandardScaler
import joblib
import os

# =====================
# Generate simulated transaction data
# =====================
np.random.seed(42)
n_samples = 1000

# Normal transactions
normal_data = pd.DataFrame({
    'amount':           np.random.normal(5000, 2000, n_samples),
    'transaction_type': np.random.choice([0, 1, 2], n_samples),  # 0=deposit, 1=withdrawal, 2=transfer
    'hour_of_day':      np.random.randint(8, 20, n_samples),     # normal banking hours
    'frequency':        np.random.randint(1, 10, n_samples),     # transactions per day
    'account_age_days': np.random.randint(30, 1000, n_samples),  # account age
})

# Anomalous transactions (fraud)
n_anomalies = 50
anomaly_data = pd.DataFrame({
    'amount':           np.random.uniform(50000, 200000, n_anomalies),  # unusually large
    'transaction_type': np.random.choice([0, 1, 2], n_anomalies),
    'hour_of_day':      np.random.choice([0, 1, 2, 3, 23], n_anomalies),  # odd hours
    'frequency':        np.random.randint(20, 50, n_anomalies),            # too frequent
    'account_age_days': np.random.randint(1, 10, n_anomalies),             # very new account
})

# Combine data
data = pd.concat([normal_data, anomaly_data], ignore_index=True)

# =====================
# Preprocess
# =====================
scaler = StandardScaler()
X = scaler.fit_transform(data)

# =====================
# Train Isolation Forest
# =====================
model = IsolationForest(
    n_estimators=100,
    contamination=0.05,  # 5% expected anomaly rate
    random_state=42
)
model.fit(X)

# =====================
# Save model and scaler
# =====================
joblib.dump(model,  'model.pkl')
joblib.dump(scaler, 'scaler.pkl')

print("✅ Model trained and saved successfully!")
print(f"   Training samples: {len(data)}")
print(f"   Normal: {n_samples} | Anomalies: {n_anomalies}")
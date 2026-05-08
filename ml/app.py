from flask import Flask, request, jsonify
import joblib
import numpy as np

app = Flask(__name__)

# Load model and scaler
model  = joblib.load('model.pkl')
scaler = joblib.load('scaler.pkl')

@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.get_json()

        # Extract features
        features = np.array([[
            data['amount'],
            data['transaction_type'],  # 0=deposit, 1=withdrawal, 2=transfer
            data['hour_of_day'],
            data['frequency'],
            data['account_age_days'],
        ]])

        # Scale features
        features_scaled = scaler.transform(features)

        # Predict
        prediction    = model.predict(features_scaled)   # 1=Normal, -1=Anomaly
        anomaly_score = model.decision_function(features_scaled)[0]

        result = 'Suspicious' if prediction[0] == -1 else 'Normal'

        return jsonify({
            'success'       : True,
            'prediction'    : result,
            'anomaly_score' : round(float(anomaly_score), 4),
            'is_fraud'      : bool(prediction[0] == -1),
        })

    except Exception as e:
        return jsonify({
            'success' : False,
            'error'   : str(e),
        }), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'success' : True,
        'message' : 'ML API is running',
    })

if __name__ == '__main__':
    app.run(debug=True, port=5000)
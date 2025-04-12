# import the inference-sdk
from inference_sdk import InferenceHTTPClient

# initialize the client
CLIENT = InferenceHTTPClient(
    api_url="https://detect.roboflow.com",
    api_key="ug33Q66g3g7GZEsiCOOB"
)

# infer on a local image
result = CLIENT.infer(r"Garbage classification\trash\trash76.jpg", model_id="major-project-zlue6/2")

print(result)
result = result['predictions']
result = result[0]

print("Accuracy:",result['confidence'])
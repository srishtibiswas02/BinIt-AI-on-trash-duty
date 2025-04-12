# import the inference-sdk
from inference_sdk import InferenceHTTPClient

# initialize the client
CLIENT = InferenceHTTPClient(
    api_url="https://detect.roboflow.com",
    api_key="ug33Q66g3g7GZEsiCOOB"
)

# infer on a local image
result = CLIENT.infer(r"Combined_garbage\pile_5.jpg", model_id="major-project-zlue6/4")

print(result)
result = result['predictions']
result = result[0]
# classes = [pred['class'] for pred in data['predictions']]
print("Accuracy:",result['confidence'])





# import the required libraries
# from inference_sdk import InferenceHTTPClient
# import supervision as sv
# import cv2
# import numpy as np

# # initialize the client
# CLIENT = InferenceHTTPClient(
#     api_url="https://detect.roboflow.com",
#     api_key="ug33Q66g3g7GZEsiCOOB"
# )

# # path to the image
# image_path = r"Combined_garbage\pile_5.jpg"

# # read the image with OpenCV
# image = cv2.imread(image_path)

# # infer on the image
# result = CLIENT.infer(image_path, model_id="major-project-zlue6/4")

# # extract predictions
# predictions = result['predictions']

# # Print sample prediction to understand structure
# if predictions:
#     print("Sample prediction format:", predictions[0])
    
#     # Ensure the required keys exist
#     required_keys = ['x', 'y', 'width', 'height', 'confidence', 'class']
#     missing_keys = [key for key in required_keys if key not in predictions[0]]
    
#     if missing_keys:
#         print(f"Warning: Missing keys in prediction: {missing_keys}")
#         # If some keys are missing, try to adapt to the format provided by Roboflow
#         if 'bbox' in predictions[0]:
#             print("Found 'bbox' key, adapting to this format.")
#             # Adapt the predictions to have the required keys
#             for pred in predictions:
#                 x_min, y_min, width, height = pred['bbox'].values()
#                 pred['x'] = x_min + width/2
#                 pred['y'] = y_min + height/2
#                 pred['width'] = width
#                 pred['height'] = height

# # create supervision Detections object
# # Extract class names and assign numeric indices
# class_names = [pred['class'] for pred in predictions]
# class_dict = {name: idx for idx, name in enumerate(set(class_names))}

# detections = sv.Detections(
#     xyxy=np.array([
#         [pred['x'] - pred['width']/2, 
#          pred['y'] - pred['height']/2, 
#          pred['x'] + pred['width']/2, 
#          pred['y'] + pred['height']/2] 
#         for pred in predictions
#     ]),
#     confidence=np.array([pred['confidence'] for pred in predictions]),
#     class_id=np.array([class_dict[pred['class']] for pred in predictions]),
# )

# # create supervision annotators
# box_annotator = sv.BoxAnnotator()
# label_annotator = sv.LabelAnnotator()

# # Map numeric indices back to class names for display
# class_id_to_name = {idx: name for name, idx in class_dict.items()}

# # annotate the image with bounding boxes and labels
# annotated_image = box_annotator.annotate(scene=image.copy(), detections=detections)
# annotated_image = label_annotator.annotate(
#     scene=annotated_image, 
#     detections=detections,
#     labels=[f"{class_id_to_name[i]}: {confidence:.2f}" for i, confidence in zip(detections.class_id, detections.confidence)]
# )

# # display the image
# cv2.imshow("Boundary Box Image", annotated_image)
# cv2.waitKey(0)
# cv2.destroyAllWindows()

# # save the annotated image
# # output_path = "annotated_" + image_path.split("\\")[-1]
# # cv2.imwrite(output_path, annotated_image)

# # print(f"Annotated image saved as {output_path}")

# # print the accuracy of the first detection (if available)
# if predictions:
#     print("Accuracy:", predictions[0]['confidence'])
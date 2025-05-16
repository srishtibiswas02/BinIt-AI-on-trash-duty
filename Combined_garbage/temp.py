import os
import shutil

# List of filenames to find and copy
target_files = [
    "1126d22c-679e-11e5-8121-40f2e96c8ad8.jpg",
    "WhatsApp Image 2022-05-14 at 11.38.52 AM.jpeg",
    "9d53e8b2-679d-11e5-90f4-40f2e96c8ad8.JPG",
    "371baa3a-6798-11e5-8c9e-40f2e96c8ad8.jpg",
    "2ff27346-679e-11e5-8121-40f2e96c8ad8.jpg",
    "25d10ec2-679e-11e5-8121-40f2e96c8ad8.jpg"
]

# Define source and destination directories
source_dir = r"D:\xampp\htdocs\Major_Project\Combined_garbage"
destination_dir = os.path.join(source_dir, "mixed_garbage")

# Create destination folder if it doesn't exist
os.makedirs(destination_dir, exist_ok=True)

# Walk through the directory tree
for root, dirs, files in os.walk(source_dir):
    for file in files:
        if file in target_files:
            src_file_path = os.path.join(root, file)
            dst_file_path = os.path.join(destination_dir, file)
            shutil.copy2(src_file_path, dst_file_path)
            print(f"Copied: {file}")

print("Done.")

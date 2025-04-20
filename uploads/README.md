# Uploads Directory

This directory is used to store uploaded files from service reports and other system functions.

## Directory Structure

- Images from service reports are stored with the naming convention: `report_[timestamp]_[index]_[original_filename]`

## Security Note

- The upload process verifies file types and sizes before storing them
- Only image files (jpg, jpeg, png, gif) are permitted
- Maximum file size is 2MB per file

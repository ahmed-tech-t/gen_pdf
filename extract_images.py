#!/usr/bin/env python3
"""
Script to extract images from PDF files without Imagick
This script uses PyMuPDF (fitz) to extract images from PDF files
"""

import os
import sys
import base64
import fitz  # PyMuPDF

def extract_images_from_pdf(pdf_path, output_dir=None):
    """
    Extract all images from a PDF file
    
    Args:
        pdf_path (str): Path to the PDF file
        output_dir (str): Directory to save extracted images (optional)
        
    Returns:
        list: List of base64 encoded images
    """
    if not os.path.exists(pdf_path):
        print(f"PDF file not found: {pdf_path}")
        return []
    
    # Create output directory if needed
    if output_dir and not os.path.exists(output_dir):
        os.makedirs(output_dir)
    
    images = []
    
    try:
        # Open the PDF file
        pdf = fitz.open(pdf_path)
        
        print(f"PDF has {len(pdf)} pages")
        
        # Iterate through each page
        for page_num in range(len(pdf)):
            page = pdf[page_num]
            
            # Get image list
            image_list = page.get_images()
            
            if not image_list:
                print(f"No images found on page {page_num + 1}")
                continue
            
            print(f"Found {len(image_list)} images on page {page_num + 1}")
            
            # Process each image on the page
            for img_index, img in enumerate(image_list):
                # Get image reference
                xref = img[0]
                base_image = pdf.extract_image(xref)
                image_bytes = base_image["image"]
                
                # Convert to base64
                base64_str = base64.b64encode(image_bytes).decode('utf-8')
                
                # Save to file if output directory is specified
                if output_dir:
                    image_filename = os.path.join(output_dir, f"page_{page_num + 1}_img_{img_index + 1}.jpg")
                    with open(image_filename, "wb") as img_file:
                        img_file.write(image_bytes)
                    print(f"Saved: {image_filename}")
                
                images.append({
                    'page': page_num + 1,
                    'image_index': img_index + 1,
                    'base64': base64_str
                })
        
        pdf.close()
        print(f"Extracted {len(images)} images total")
        return images
        
    except Exception as e:
        print(f"Error extracting images: {e}")
        return []

if __name__ == "__main__":
    # Check if PDF path is provided
    if len(sys.argv) < 2:
        print("Usage: python extract_images.py <pdf_path> [output_dir]")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    output_dir = sys.argv[2] if len(sys.argv) > 2 else None
    
    # Extract images
    images = extract_images_from_pdf(pdf_path, output_dir)
    
    # Print base64 of first image for testing
    if images:
        print(f"\nBase64 of first image (page {images[0]['page']}):")
        print(images[0]['base64'][:100] + "...")
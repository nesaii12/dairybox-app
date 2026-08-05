import os
from PIL import Image, ImageDraw

def generate_favicons():
    # Create a 512x512 base image with transparency
    size = 512
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Draw sky-blue rounded background (#0ea5e9)
    # Bounding box for rounded square
    box = [32, 32, size - 32, size - 32]
    # Draw rounded rectangle
    draw.rounded_rectangle(box, radius=110, fill=(14, 165, 233, 255))

    # Draw white droplet shape
    # Center is at 256
    # Let's draw the bottom circle
    circle_center = (256, 300)
    circle_radius = 80
    draw.ellipse(
        [circle_center[0] - circle_radius, circle_center[1] - circle_radius,
         circle_center[0] + circle_radius, circle_center[1] + circle_radius],
        fill=(255, 255, 255, 255)
    )
    
    # Draw the top triangle pointing up to (256, 160)
    triangle_points = [
        (256, 150),
        (256 - circle_radius, 300),
        (256 + circle_radius, 300)
    ]
    draw.polygon(triangle_points, fill=(255, 255, 255, 255))

    # Ensure public folder exists
    public_path = "public"
    os.makedirs(public_path, exist_ok=True)

    # Save multi-resolution favicon.ico (16, 32, 48)
    sizes = [16, 32, 48]
    resized_imgs = [img.resize((s, s), Image.Resampling.LANCZOS) for s in sizes]
    # Save as ICO
    resized_imgs[0].save(
        os.path.join(public_path, "favicon.ico"),
        format="ICO",
        sizes=[(s, s) for s in sizes],
        append_images=resized_imgs[1:]
    )

    # Save other sizes
    img.resize((96, 96), Image.Resampling.LANCZOS).save(os.path.join(public_path, "favicon-96x96.png"))
    img.resize((180, 180), Image.Resampling.LANCZOS).save(os.path.join(public_path, "apple-touch-icon.png"))
    img.resize((192, 192), Image.Resampling.LANCZOS).save(os.path.join(public_path, "web-app-manifest-192x192.png"))
    img.resize((512, 512), Image.Resampling.LANCZOS).save(os.path.join(public_path, "web-app-manifest-512x512.png"))

    print("Favicon files generated successfully in public/")

if __name__ == "__main__":
    generate_favicons()

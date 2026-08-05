import os
import time
from playwright.sync_api import sync_playwright

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()
        
        # Navigate to login
        page.goto("http://127.0.0.1:8000/login")
        page.wait_for_load_state("networkidle")
        
        # Login
        page.fill("input[name='email']", "maria@example.com")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")
        
        # Go to shop
        page.goto("http://127.0.0.1:8000/customer/products")
        page.wait_for_load_state("networkidle")
        
        # Initial products count
        initial_products = page.locator(".grid > div").count()
        print(f"Initial products count: {initial_products}")
        
        # Type "Chocolate" in search box
        print("Typing 'Chocolate' in search...")
        page.fill("#search", "Chocolate")
        
        # Wait for debounce and AJAX load (wait up to 2 seconds or for AJAX opacity class changes)
        time.sleep(1.5)
        
        # Products count after filter
        filtered_products = page.locator(".grid > div").count()
        print(f"Filtered products count: {filtered_products}")
        
        # Verify first product contains Chocolate
        first_product_text = page.locator(".grid > div").first.inner_text()
        first_product_text_clean = first_product_text.replace("₱", "PHP")
        print(f"First product text:\n{first_product_text_clean}")
        
        assert "Chocolate" in first_product_text, "Search filter did not work!"
        print("Live search successfully filtered results dynamically!")
        
        # Test category pill click
        # Find the Ice Cream pill
        print("Clicking 'Ice Cream' category pill...")
        page.click("button:has-text('Ice Cream')")
        time.sleep(1.5)
        
        ice_cream_products = page.locator(".grid > div").count()
        print(f"Products count after clicking Ice Cream pill: {ice_cream_products}")
        
        # Capture screenshot of search results
        screenshot_path = r"C:\Users\basco\.gemini\antigravity-ide\brain\4254ec68-cf79-4d7f-8fff-d240611905d5\scratch\live_search_test.png"
        page.screenshot(path=screenshot_path)
        print(f"Live search screenshot saved to: {screenshot_path}")
        
        browser.close()

if __name__ == "__main__":
    main()

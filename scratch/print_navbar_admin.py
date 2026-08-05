from playwright.sync_api import sync_playwright

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()
        
        # Login as admin
        page.goto("http://127.0.0.1:8000/login")
        page.wait_for_load_state("networkidle")
        page.fill("input[name='email']", "admin@dairybox.com")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")
        
        # Navigate to home
        page.goto("http://127.0.0.1:8000/")
        page.wait_for_load_state("networkidle")
        print("=== LOGGED IN ADMIN HEADER NAVIGATION ===")
        header_html = page.eval_on_selector("header", "el => el.outerHTML")
        print(header_html)
        
        browser.close()

if __name__ == "__main__":
    main()

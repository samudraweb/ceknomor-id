import asyncio
import re
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        # Start a Playwright session in asynchronous mode
        pw = await async_api.async_playwright().start()

        # Launch a Chromium browser in headless mode with custom arguments
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",
                "--disable-dev-shm-usage",
                "--ipc=host",
                "--single-process"
            ],
        )

        # Create a new browser context (like an incognito window)
        context = await browser.new_context()
        # Wider default timeout to match the agent's DOM-stability budget;
        # auto-waiting Playwright APIs (expect, locator.wait_for) inherit this.
        context.set_default_timeout(15000)

        # Open a new page in the browser context
        page = await context.new_page()

        # Interact with the page elements to simulate user flow
        # -> navigate
        await page.goto("http://localhost:8000/")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Scroll down to reveal the search form, the 'Cek Sekarang' button, and other navigation elements so a result page or link to '/hasil' can be opened.
        await page.mouse.wheel(0, 300)
        
        # -> Navigate to the results page at /hasil (the Results page) so a specific result can be opened and its safety label and counts inspected.
        await page.goto("http://localhost:8000/hasil")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Click the trending number '0812-3456-789' (labelled 'Berbahaya') to open its result page and inspect the safety summary.
        # 1 0812-3456-789 Berbahaya link
        elem = page.get_by_role('link', name='1 0812-3456-789 Berbahaya', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify the safety label is displayed
        # Assert: The safety label 'Berbahaya' is visible on the page.
        await expect(page.locator("xpath=/html/body/div[1]/div[2]/aside/div[2]/div[2]/a[1]").nth(0)).to_contain_text("Berbahaya", timeout=15000), "The safety label 'Berbahaya' is visible on the page."
        
        # --> Verify the overall risk summary is displayed
        # Assert: Overall risk percentages '12% Aman 88% Bahaya' are displayed.
        await expect(page.locator("xpath=/html/body/div[1]/div[2]/div/div[2]/div[5]/div[2]/div[1]/div[1]/div[1]").nth(0)).to_contain_text("12% Aman 88% Bahaya", timeout=15000), "Overall risk percentages '12% Aman 88% Bahaya' are displayed."
        # Assert: Overall risk summary paragraph is displayed.
        await expect(page.locator("xpath=/html/body/div[1]/div[2]/div/div[2]/div[5]/div[2]/div[1]/div[1]/div[1]").nth(0)).to_contain_text("Nomor ini sangat sering dilaporkan oleh pengguna. Mayoritas laporan menyebutkan modus penipuan mengaku dari bank dan meminta kode OTP, serta tawaran pinjaman ilegal.", timeout=15000), "Overall risk summary paragraph is displayed."
        current_url = await page.evaluate("() => window.location.href")
        # Assert: page loaded with a URL (final outcome verified by the AI judge during the run)
        assert current_url, 'Page should have loaded with a URL'
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    
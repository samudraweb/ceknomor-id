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
        
        # -> Scroll the homepage to reveal the 'Nomor Telepon' input and the 'Cek Sekarang' search button.
        await page.mouse.wheel(0, 300)
        
        # -> Scroll the homepage down to reveal the 'Nomor Telepon' input field and the 'Cek Sekarang' (Check Now) button so the form can be observed.
        await page.mouse.wheel(0, 300)
        
        # -> Scroll up the homepage to reveal the 'Nomor Telepon' input field and the 'Cek Sekarang' button so the phone search form is visible.
        await page.mouse.wheel(0, 300)
        
        # -> Fill the 'Nomor Telepon' field with a valid Indonesian phone number and click the 'Cek Sekarang' button.
        # Contoh: 08123456789 tel field
        elem = page.locator('[id="phoneInput"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("08123456789")
        
        # -> Fill the 'Nomor Telepon' field with a valid Indonesian phone number and click the 'Cek Sekarang' button.
        # Cek Sekarang button
        elem = page.locator('[id="btnSearchPhone"]')
        await elem.click(timeout=10000)
        
        # -> Close the 'Masuk ke ceknomor.id' login modal by clicking the close (X) button so the page and search results are visible.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'X' close button on the 'Masuk ke ceknomor.id' login modal to dismiss it so the search results can be viewed.
        # button
        elem = page.locator('xpath=/html/body/div[4]/div/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Beranda' link in the open menu to close the navigation/modal so the search results can be viewed.
        # Beranda link
        elem = page.locator('xpath=/html/body/div/ul/li/a')
        await elem.click(timeout=10000)
        
        # -> Click the site logo 'ceknomor.id' at the top-left to try to close the modal and reveal the search results.
        # cek nomor .id link
        elem = page.get_by_role('link', name='ceknomor.id', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the phone result card showing '08123456789' to open its detailed summary.
        # Click the phone result card showing '08123456789' to open its detailed summary.
        elem = page.locator('xpath=/html/body/section/div/div/div[2]/div[3]/div/div')
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify a safety label is displayed
        # Assert: The safety category label 'Pinjol' is visible.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[2]/div[2]/div[2]/span[2]").nth(0)).to_have_text("Pinjol", timeout=15000), "The safety category label 'Pinjol' is visible."
        
        # --> Verify report and comment counts are displayed
        # Assert: The report count (47 Laporan) is displayed.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0)).to_contain_text("47 Laporan", timeout=15000), "The report count (47 Laporan) is displayed."
        # Assert: The comment count (128 Komentar) is displayed.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0)).to_contain_text("128 Komentar", timeout=15000), "The comment count (128 Komentar) is displayed."
        
        # --> Verify category and latest report information are displayed
        # Assert: The category 'Pinjol' is displayed in the result summary.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[2]/div[2]/div[2]/span[2]").nth(0)).to_have_text("Pinjol", timeout=15000), "The category 'Pinjol' is displayed in the result summary."
        await page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0).scroll_into_view_if_needed()
        # Assert: The latest report information is visible in the result summary.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0)).to_be_visible(timeout=15000), "The latest report information is visible in the result summary."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    
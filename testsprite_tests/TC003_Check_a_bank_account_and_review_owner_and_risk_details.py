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
        
        # -> Scroll the homepage to reveal the bank account search field or the 'Cari nomor rekening' search area.
        await page.mouse.wheel(0, 300)
        
        # -> Scroll up to reveal the 'Masukkan Nomor' search field (the input for phone number or bank account).
        await page.mouse.wheel(0, 300)
        
        # -> Click the 'Rekening Bank' tab to switch the search mode from phone number to bank account.
        # Rekening Bank button
        elem = page.locator('[id="tabRekening"]')
        await elem.click(timeout=10000)
        
        # -> Enter a suspicious bank account number into the 'Nomor rekening' field and click the 'Cek' button to submit the search.
        # Nomor rekening text field
        elem = page.locator('[id="rekeningInput"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("1234567890123456")
        
        # -> Enter a suspicious bank account number into the 'Nomor rekening' field and click the 'Cek' button to submit the search.
        # Cek button
        elem = page.locator('[id="btnSearchRekening"]')
        await elem.click(timeout=10000)
        
        # -> Close the 'Masuk ke ceknomor.id' login modal by clicking the 'Tutup' (close) button to reveal the search results.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the modal's 'Tutup' (close) button on the 'Masuk ke ceknomor.id' login dialog to dismiss it and reveal the search results.
        # button
        elem = page.locator('xpath=/html/body/div[4]/div/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Tutup' button on the login modal to dismiss it and reveal the search results.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify the search result summary is displayed
        await page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0).scroll_into_view_if_needed()
        # Assert: Search result summary is visible on the page.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0)).to_be_visible(timeout=15000), "Search result summary is visible on the page."
        
        # --> Verify the safety label and owner information are displayed when available
        # Assert: Safety label "Aman — 0 laporan" is visible in the search result.
        await expect(page.locator("xpath=/html/body/section[1]/div/div/div[2]/div[3]/div[1]/div[1]").nth(0)).to_contain_text("Aman \u2014 0 laporan", timeout=15000), "Safety label \"Aman \u2014 0 laporan\" is visible in the search result."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    
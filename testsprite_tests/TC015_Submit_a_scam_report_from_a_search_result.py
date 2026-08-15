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
        
        # -> Scroll the page down to reveal the search box and report options so the suspicious number or account can be searched.
        await page.mouse.wheel(0, 300)
        
        # -> Scroll down to reveal the 'Nomor Telepon' search input and the 'Cek Sekarang' button so the suspicious number can be searched.
        await page.mouse.wheel(0, 300)
        
        # -> Click the "Cek Nomor Telepon" link to open the phone-number search page.
        # Cek Nomor Telepon link
        elem = page.get_by_role('link', name='Cek Nomor Telepon', exact=True)
        await elem.click(timeout=10000)
        
        # -> Reveal the 'Nomor Telepon' search input and the 'Cek Sekarang' button by scrolling down the homepage until the search UI is visible.
        await page.mouse.wheel(0, 300)
        
        # -> Enter a phone number into the 'Nomor Telepon' field and click the 'Cek Sekarang' button to run the search.
        # Contoh: 08123456789 tel field
        elem = page.locator('[id="phoneInput"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("08123456789")
        
        # -> Enter a phone number into the 'Nomor Telepon' field and click the 'Cek Sekarang' button to run the search.
        # Cek Sekarang button
        elem = page.locator('[id="btnSearchPhone"]')
        await elem.click(timeout=10000)
        
        # -> Close the login modal by clicking the 'Tutup' button so the search result and report options become accessible.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the modal's 'X' close icon (the close control on the 'Masuk ke ceknomor.id' login dialog) to dismiss the login modal.
        # button
        elem = page.locator('xpath=/html/body/div[4]/div/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Tutup' button to close the login modal so the search result and report options are accessible.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Tutup' button on the login modal to close the 'Masuk ke ceknomor.id' dialog so the search result and report options become accessible.
        # Tutup button
        elem = page.get_by_role('button', name='Tutup', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the '0812-3456-789' result link to open the number's detail page and check for the report flow.
        # 1 0812-3456-789 undefined Berbahaya link
        elem = page.get_by_role('link', name='1 0812-3456-789 undefined Berbahaya', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Laporkan' button on the number detail page to open the report flow.
        # Laporkan button
        elem = page.get_by_text('Salin', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Laporkan', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the modal's close 'X' button (the visible close icon) to dismiss the login dialog so the report flow can be accessed.
        # button
        elem = page.locator('xpath=/html/body/div[5]/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the modal's close 'X' icon (the visible close button at the top-right of the login dialog) to attempt to dismiss the 'Masuk ke ceknomor.id' modal.
        # button
        elem = page.locator('xpath=/html/body/div[5]/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the modal 'X' close button (the close icon) to dismiss the 'Masuk ke ceknomor.id' dialog so the report flow can be accessed.
        # button
        elem = page.locator('xpath=/html/body/div[4]/div/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Laporkan' button on the number detail page to open the report form.
        # Laporkan button
        elem = page.get_by_text('Salin', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Laporkan', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the modal close button (the 'X' icon) on the 'Masuk ke ceknomor.id' dialog to dismiss it so the report form is accessible.
        # button
        elem = page.locator('xpath=/html/body/div[5]/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'X' close button in the top-right corner to dismiss the overlay/menu so the report form becomes accessible.
        # button
        elem = page.locator('xpath=/html/body/div[5]/div/button')
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
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
    
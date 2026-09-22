import assert from 'node:assert/strict';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const browser = await chromium.launch({ headless: true, ...(process.env.BROWSER_CHANNEL ? { channel: process.env.BROWSER_CHANNEL } : {}) });
const errors = [];
try {
    const pages = [];
    for (const name of ['ana', 'luis']) {
        const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await context.newPage();
        page.on('pageerror', error => errors.push(error.message));
        await page.goto('http://127.0.0.1:8098/login');
        await page.locator('[name=email]').fill(`${name}@chat.example.test`);
        await page.locator('[name=password]').fill('ChatPrueba123');
        await Promise.all([page.waitForURL(url => !url.pathname.endsWith('/login')), page.getByRole('button', { name: 'Entrar a Plaza Local' }).click()]);
        await page.goto('http://127.0.0.1:8098/mensajes/11111111-1111-4111-8111-111111111111');
        await page.getByText('En tiempo real', { exact: true }).waitFor();
        pages.push(page);
    }
    const [ana, luis] = pages;
    const body = `Prueba WebSocket ${Date.now()}`;
    await ana.getByRole('textbox', { name: 'Mensaje', exact: true }).fill(body);
    await luis.getByText('Escribiendo…', { exact: true }).waitFor({ timeout: 10000 });
    await ana.getByRole('button', { name: 'Enviar', exact: true }).click();
    await luis.getByText(body, { exact: true }).last().waitFor({ timeout: 10000 });
    await luis.getByRole('textbox', { name: 'Mensaje', exact: true }).fill('Respuesta en vivo');
    await luis.getByRole('button', { name: 'Enviar', exact: true }).click();
    await ana.getByText('Respuesta en vivo', { exact: true }).last().waitFor({ timeout: 10000 });
    await ana.bringToFront();
    await ana.locator('[data-chat-scroll]').evaluate(element => { element.scrollTop = element.scrollHeight; });
    await luis.locator('[data-message-id]').filter({ hasText: 'Respuesta en vivo' }).getByText(/Leído/).waitFor({ timeout: 10000 });
    await ana.getByRole('button', { name: 'Cargar mensajes anteriores' }).click();
    try {
        await ana.waitForFunction(() => [...document.querySelectorAll('[data-message-id]')].some(row => row.textContent.includes('Mensaje anterior 010')), null, { timeout: 30000 });
    } catch (error) {
        console.log(await ana.locator('[data-chat-window]').innerText());
        console.log(errors);
        throw error;
    }
    await ana.getByRole('searchbox', { name: 'Buscar conversaciones' }).fill('no-existe-xyz');
    await ana.getByText('No hay conversaciones que coincidan.').waitFor();
    await ana.getByRole('searchbox', { name: 'Buscar conversaciones' }).fill('');
    await ana.locator('[data-chat-scroll]').evaluate(element => { element.scrollTop = element.scrollHeight; });
    await ana.screenshot({ path: 'storage/framework/testing/chat-desktop.png', fullPage: true });
    await luis.setViewportSize({ width: 390, height: 844 });
    await luis.screenshot({ path: 'storage/framework/testing/chat-mobile.png', fullPage: true });
    assert.equal(await luis.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'Mobile overflow');
    assert.deepEqual(errors, [], 'Browser JavaScript errors');
    console.log('PASS: real Reverb bidirectional delivery <10s, typing, read receipt, history, search, mobile width, no JavaScript errors.');
} finally { await browser.close(); }

import assert from 'node:assert/strict';

const playwrightModule = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const { chromium } = playwrightModule.default ?? playwrightModule;
const browser = await chromium.launch({
    headless: true,
    ...(process.env.BROWSER_CHANNEL ? { channel: process.env.BROWSER_CHANNEL } : {}),
});
const baseUrl = 'http://127.0.0.1:8098';
const uploadPath = 'storage/framework/testing/chat-mobile.png';
const pageErrors = [];

async function assertNoHorizontalOverflow(page, label) {
    assert.equal(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
        true,
        `${label} has horizontal overflow`,
    );
}

async function marketplacePage(email, viewport = { width: 1280, height: 900 }) {
    const context = await browser.newContext({ viewport });
    const page = await context.newPage();
    page.on('pageerror', (error) => pageErrors.push(`${email}: ${error.message}`));
    await page.goto(`${baseUrl}/login`);
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill('FlujoE2E123');
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.getByRole('button', { name: 'Entrar a Plaza Local' }).click(),
    ]);

    return page;
}

try {
    const provider = await marketplacePage('provider-e2e@example.test');
    await provider.goto(`${baseUrl}/dashboard?publicar=offer#crear-publicacion`);
    await provider.locator('label').filter({ has: provider.locator('[name=type][value=product]') }).click();
    await provider.locator('[name=title]').fill('Producto integral E2E');
    await provider.locator('[name=price_type]').selectOption('fixed');
    await provider.locator('[name=price]').fill('175.50');
    await provider.locator('[name=stock]').fill('4');
    await provider.locator('[name=category_id]').selectOption({ label: 'Servicios E2E' });
    await provider.locator('[name=body]').fill('Producto con imagen creado mediante el recorrido integral del navegador.');
    await provider.locator('[name="media[]"]').setInputFiles(uploadPath);
    await Promise.all([
        provider.waitForURL((url) => url.pathname === '/dashboard'),
        provider.getByRole('button', { name: 'Publicar', exact: true }).click(),
    ]);
    await provider.getByRole('status').filter({ hasText: /publicaci/i }).waitFor();

    for (const width of [320, 375, 390, 412]) {
        await provider.setViewportSize({ width, height: 844 });
        await provider.goto(`${baseUrl}/dashboard?module=products&feed=offers`);
        await provider.getByText('Tu plaza en Comunidad E2E').waitFor();
        await provider.getByRole('navigation', { name: 'Navegación principal' }).waitFor();
        await provider.getByRole('link', { name: 'Publicar', exact: true }).waitFor();
        await provider.getByRole('navigation', { name: 'Filtros de actividad' }).waitFor();
        await assertNoHorizontalOverflow(provider, `Dashboard ${width}px`);
    }

    await provider.setViewportSize({ width: 1280, height: 900 });
    await provider.goto(`${baseUrl}/dashboard?module=products&feed=offers`);
    assert.equal(await provider.getByRole('navigation', { name: 'Navegación principal' }).isVisible(), false, 'Desktop should use the top navigation instead of the mobile bottom bar');

    await provider.goto(`${baseUrl}/mi-perfil/editar`);
    const documentForm = provider.locator('form').filter({ has: provider.locator('[name=document]') });
    await documentForm.locator('[name=type]').selectOption('government_id');
    await documentForm.locator('[name=document]').setInputFiles(uploadPath);
    await Promise.all([
        provider.waitForLoadState('domcontentloaded'),
        documentForm.getByRole('button', { name: 'Enviar', exact: true }).click(),
    ]);
    try {
        await provider.getByText(/Documento recibido|Pendiente/i).first().waitFor();
    } catch (error) {
        console.log('Document upload URL:', provider.url());
        console.log(await provider.locator('body').innerText({ timeout: 5000 }).catch(() => '<body unavailable>'));
        throw error;
    }

    const buyer = await marketplacePage('buyer-e2e@example.test');
    await buyer.goto(`${baseUrl}/explorar?type=product&scope=all`);
    await buyer.setViewportSize({ width: 375, height: 844 });
    await buyer.getByRole('navigation', { name: 'Tipos de resultado' }).waitFor();
    await buyer.getByText('Producto · Servicios E2E').waitFor();
    await assertNoHorizontalOverflow(buyer, 'Explore 375px');
    await buyer.getByRole('link', { name: /Producto integral E2E/ }).click();
    await buyer.locator('[name=quantity]').fill('2');
    await buyer.locator('[name=buyer_notes]').fill('Pedido local E2E.');
    await Promise.all([
        buyer.waitForURL((url) => url.pathname.startsWith('/trabajos/')),
        buyer.getByRole('button', { name: /Crear pedido|Confirmar pedido|Reservar/i }).click(),
    ]);
    await buyer.getByText(/Aceptad|Pedido registrado|acuerdo/i).first().waitFor();

    const employer = await marketplacePage('employer-e2e@example.test');
    await employer.goto(`${baseUrl}/empleo-publicar`);
    await employer.locator('[name=title]').fill('Auxiliar integral E2E');
    await employer.locator('[name=description]').fill('Vacante creada mediante navegador para validar el flujo completo de empleo.');
    await employer.locator('[name=requirements]').fill('Disponibilidad y trato respetuoso durante la prueba.');
    await employer.locator('[name=community_id]').selectOption({ index: 1 });
    await employer.locator('[name=salary_min]').fill('1800');
    await employer.locator('[name=salary_max]').fill('2200');
    await employer.locator('[name=pay_period]').selectOption('weekly');
    await employer.locator('[name=work_mode]').selectOption('onsite');
    await employer.locator('[name=contract_type]').selectOption('temporary');
    await employer.locator('[name=schedule]').fill('Lunes a viernes de 09:00 a 14:00');
    await employer.locator('[name=vacancies_count]').fill('1');
    await Promise.all([
        employer.waitForURL((url) => /^\/empleo\/[^/]+$/.test(url.pathname)),
        employer.getByRole('button', { name: /Crear vacante|Continuar|Publicar/i }).click(),
    ]);
    await employer.getByText('VACANTE DE EMPLEO', { exact: true }).waitFor();

    const applicant = await marketplacePage('applicant-e2e@example.test', { width: 390, height: 844 });
    await applicant.goto(`${baseUrl}/empleo`);
    await applicant.getByRole('link', { name: /Auxiliar integral E2E/ }).click();
    await applicant.locator('[name=cover_letter]').fill('Me interesa la vacante E2E y cuento con disponibilidad para el horario indicado.');
    await applicant.getByRole('button', { name: 'Postularme' }).click();
    await applicant.getByText('Ya enviaste tu postulación.').waitFor();
    await applicant.getByRole('link', { name: 'Buscar trabajo' }).waitFor();
    await assertNoHorizontalOverflow(applicant, 'Mobile vacancy');

    const adminContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const admin = await adminContext.newPage();
    admin.on('pageerror', (error) => pageErrors.push(`admin: ${error.message}`));
    await admin.goto(`${baseUrl}/administracion/login`);
    await admin.locator('[name=email]').fill('admin-e2e@example.test');
    await admin.locator('[name=password]').fill('FlujoE2E123');
    await Promise.all([
        admin.waitForURL((url) => url.pathname === '/administracion'),
        admin.getByRole('button', { name: /Entrar|Iniciar sesión/i }).click(),
    ]);
    await admin.goto(`${baseUrl}/administracion/empleo?q=Auxiliar+integral+E2E`);
    await admin.getByText('Auxiliar integral E2E', { exact: true }).waitFor();
    await admin.goto(`${baseUrl}/administracion/publicaciones?q=Producto+integral+E2E`);
    await admin.getByText('Producto integral E2E', { exact: true }).waitFor();
    await admin.goto(`${baseUrl}/administracion/proveedores?q=Negocio+E2E`);
    await admin.getByText('Negocio E2E', { exact: true }).waitFor();

    assert.deepEqual(pageErrors, [], 'Browser JavaScript errors');
    console.log('PASS: provider product+image, private document, buyer order, employer vacancy, applicant application, admin visibility, mobile width, no JavaScript errors.');
} finally {
    await browser.close();
}

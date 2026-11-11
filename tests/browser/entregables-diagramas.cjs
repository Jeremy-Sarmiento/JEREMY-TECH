// Requiere Playwright y Chromium instalados. Ejecutar: node tests/browser/entregables-diagramas.cjs
const assert = require('node:assert/strict');
const { readdirSync, readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { pathToFileURL } = require('node:url');
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_PATH });
  try {
    const page = await browser.newPage();
    const directory = resolve(__dirname, '../../entregables/entregables2');
    const files = readdirSync(directory).filter(file => file.endsWith('.html') &&
      readFileSync(resolve(directory, file), 'utf8').includes('<svg'));
    for (const file of files) {
      await page.goto(pathToFileURL(resolve(directory, file)).href);
      const diagrams = page.locator('.diagrama-embebido');
      const count = await diagrams.count();
      assert.equal(await page.locator('.diagrama-ampliar:not([disabled])').count() >= count, true, file);
      for (let i = 0; i < count; i++) {
        const trigger = page.locator('.diagrama-ampliar').nth(i);
        for (const width of [320, 768, 1440]) {
          await page.setViewportSize({ width, height: 900 });
          await trigger.click();
          const dialog = page.locator('dialog[open]');
          assert.equal(await dialog.count(), 1, `${file}: visor`);
          const metrics = await dialog.locator('svg').evaluate(svg => {
            const scale = svg.getBoundingClientRect().width / svg.viewBox.baseVal.width;
            return [...svg.querySelectorAll('text')].map(text => parseFloat(getComputedStyle(text).fontSize) * scale);
          });
          assert.ok(Math.min(...metrics) >= 13.9, `${file}: texto ampliado legible`);
          const initial = await dialog.locator('svg').boundingBox();
          await dialog.getByRole('button', { name: 'Aumentar', exact: true }).click();
          assert.ok((await dialog.locator('svg').boundingBox()).width > initial.width);
          await dialog.getByRole('button', { name: 'Reducir', exact: true }).click();
          await dialog.getByRole('button', { name: 'Ajustar al ancho' }).click();
          const fits = await dialog.locator('.diagrama-lienzo').evaluate(el => el.scrollWidth <= el.clientWidth + 1);
          assert.ok(fits, `${file}: ajustar al ancho`);
          await page.keyboard.press('Escape');
          assert.equal(await page.locator('dialog[open]').count(), 0);
          await diagrams.nth(i).locator('svg').waitFor({ state: 'attached' });
          assert.equal(await diagrams.nth(i).locator('svg').count(), 1, `${file}: restaurar SVG`);
          assert.ok(await trigger.evaluate(el => el === document.activeElement), `${file}: restaurar foco`);
        }
      }
      console.log(`PASS ${file}: ampliar, zoom, ajuste, Escape y foco en 3 tamaños`);
    }
    console.log(`PASS ${files.length} entregables con diagramas`);
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });

/*
 * Quick health check of the landing templates on their demo pages, at phone width:
 * horizontal overflow, JavaScript errors, missing files (404).
 *
 *   node scripts/landing-smoke.mjs            every manifest in resources/landing-templates
 *   node scripts/landing-smoke.mjs haircut    one template
 *
 * Exits with 1 when something is wrong, so it can gate a batch of imports. Needs the app up
 * (default http://localhost:8080) and Playwright (local or global). Dev tool only.
 */
import { createRequire } from 'node:module';
import { execSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import path from 'node:path';

const require = createRequire(import.meta.url);
function loadPlaywright() {
  try { return require('playwright'); } catch { /* fall through to the global install */ }
  return require(path.join(execSync('npm root -g', { encoding: 'utf8' }).trim(), 'playwright'));
}
const { chromium } = loadPlaywright();

const BASE = process.env.APP_URL || 'http://localhost:8080';
const root = path.resolve(path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1')), '..');
const slugs = process.argv[2]
  ? [process.argv[2]]
  : readdirSync(path.join(root, 'resources/landing-templates')).filter((f) => f.endsWith('.php')).map((f) => f.slice(0, -4));

const browser = await chromium.launch();
let failed = 0;

for (const slug of slugs) {
  const page = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
  const errors = [];
  const missing = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('console', (m) => m.type() === 'error' && !/Failed to load resource/.test(m.text()) && errors.push(m.text()));
  page.on('response', (r) => r.status() >= 400 && missing.push(`${r.status()} ${r.url().replace(BASE, '')}`));

  const response = await page.goto(`${BASE}/template-demo/${slug}`, { waitUntil: 'load' });
  await page.waitForTimeout(2500);
  const { sw, iw } = await page.evaluate(() => ({ sw: document.documentElement.scrollWidth, iw: innerWidth }));

  const problems = [];
  if (!response || !response.ok()) problems.push(`page answered ${response && response.status()}`);
  if (sw > iw) problems.push(`horizontal overflow (${sw}px in ${iw}px)`);
  if (errors.length) problems.push(`js errors: ${errors.join(' / ')}`);
  if (missing.length) problems.push(`missing: ${missing.join(', ')}`);

  console.log(`${problems.length ? 'FAIL' : 'ok  '} ${slug}${problems.length ? ': ' + problems.join('; ') : ''}`);
  if (problems.length) failed++;
  await page.close();
}

await browser.close();
process.exit(failed ? 1 : 0);

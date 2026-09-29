/*
 * Makes the wizard preview (480x316 preview.jpg) and QA screenshots for landing templates.
 *
 *   node scripts/landing-thumbs.mjs salone            one template
 *   node scripts/landing-thumbs.mjs --all             every manifest in resources/landing-templates
 *
 * It opens /template-demo/{slug} (sample data, nothing stored), so it never depends on a
 * master's real page. Needs the app up (default http://localhost:8080) and Playwright:
 * a local install or a global one (npm i -g playwright). Dev tool only.
 *
 * Writes public/landing-templates/{slug}/preview.jpg and output/qa/{slug}-{desktop,mobile}.png.
 */
import { createRequire } from 'node:module';
import { execSync } from 'node:child_process';
import { readdirSync, mkdirSync, writeFileSync, existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import path from 'node:path';
import os from 'node:os';

const require = createRequire(import.meta.url);
function loadPlaywright() {
  try { return require('playwright'); } catch { /* fall through to the global install */ }
  const globalRoot = execSync('npm root -g', { encoding: 'utf8' }).trim();
  return require(path.join(globalRoot, 'playwright'));
}
const { chromium } = loadPlaywright();

const BASE = process.env.APP_URL || 'http://localhost:8080';
const root = path.resolve(path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1')), '..');
const arg = process.argv[2];

if (!arg) {
  console.error('Usage: node scripts/landing-thumbs.mjs <slug>|--all');
  process.exit(1);
}

const slugs = arg === '--all'
  ? readdirSync(path.join(root, 'resources/landing-templates')).filter((f) => f.endsWith('.php')).map((f) => f.slice(0, -4))
  : [arg];

const browser = await chromium.launch();
const tmp = path.join(os.tmpdir(), 'landing-thumbs');
mkdirSync(tmp, { recursive: true });
mkdirSync(path.join(root, 'output/qa'), { recursive: true });

for (const slug of slugs) {
  const url = `${BASE}/template-demo/${slug}`;
  const assets = path.join(root, 'public/landing-templates', slug);
  if (!existsSync(assets)) { console.warn(`skip ${slug}: no asset folder`); continue; }

  // desktop hero, the source of the wizard preview
  const desktop = await browser.newPage({ viewport: { width: 1366, height: 900 } });
  const response = await desktop.goto(url, { waitUntil: 'load' });
  if (!response || !response.ok()) { console.warn(`skip ${slug}: ${url} answered ${response && response.status()}`); await desktop.close(); continue; }
  await desktop.waitForTimeout(3500);
  await desktop.evaluate(() => window.scrollTo(0, 0));
  await desktop.waitForTimeout(600);
  const heroFile = path.join(tmp, `${slug}-hero.png`);
  await desktop.screenshot({ path: heroFile });

  // whole page, after scrolling through it so reveal-on-scroll blocks appear
  await desktop.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 120)); }
    window.scrollTo(0, 0);
  });
  await desktop.waitForTimeout(800);
  await desktop.screenshot({ path: path.join(root, `output/qa/${slug}-desktop.png`), fullPage: true });
  await desktop.close();

  // phone
  const phone = await browser.newPage({ viewport: { width: 390, height: 844 } });
  await phone.goto(url, { waitUntil: 'load' });
  await phone.waitForTimeout(2500);
  await phone.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 100)); }
    window.scrollTo(0, 0);
  });
  await phone.waitForTimeout(600);
  await phone.screenshot({ path: path.join(root, `output/qa/${slug}-mobile.png`), fullPage: true });
  await phone.close();

  // scale the hero down to the wizard's 480x316 card
  const html = path.join(tmp, `${slug}.html`);
  writeFileSync(html, `<body style="margin:0"><img src="${pathToFileURL(heroFile).href}" style="width:480px;display:block"></body>`);
  const small = await browser.newPage({ viewport: { width: 480, height: 316 } });
  await small.goto(pathToFileURL(html).href);
  await small.waitForTimeout(400);
  await small.screenshot({ path: path.join(assets, 'preview.jpg'), type: 'jpeg', quality: 82, clip: { x: 0, y: 0, width: 480, height: 316 } });
  await small.close();

  console.log(`ok ${slug}: preview.jpg + output/qa/${slug}-{desktop,mobile}.png`);
}

await browser.close();

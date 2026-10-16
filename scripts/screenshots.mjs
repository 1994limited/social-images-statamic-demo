// Screenshots of Dog-ear Books, as used in Social Images' docs. Run against
// the demo with its users and content, and its images made:
//
//   php artisan demo:users && php artisan demo:content
//   php please social-images:generate
//   node scripts/screenshots.mjs
//
// DEMO_URL     the demo's address (default http://127.0.0.1:8000)
// SCREENSHOTS  where to write them (default ../social-images-statamic/docs/images)
// CHROME       a Chrome or Chromium to drive (default: Google Chrome on macOS)
import { readFileSync } from 'node:fs';
import puppeteer from 'puppeteer';

const base = (process.env.DEMO_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = process.env.SCREENSHOTS || new URL('../../social-images-statamic/docs/images', import.meta.url).pathname;
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const browser = await puppeteer.launch({ executablePath: chrome, args: ['--ignore-certificate-errors'] });
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }, { name: 'prefers-reduced-motion', value: 'reduce' }]);

const settle = (ms = 1200) => new Promise((resolve) => setTimeout(resolve, ms));

// A local demo runs Statamic Pro in trial mode, which asks about licences.
async function snooze() {
    await page.evaluate(() => [...document.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Snooze')?.click());
    await settle(400);
}

// Sign in as the demo admin (a local-only account with a known password).
await page.goto(`${base}/cp/auth/login`, { waitUntil: 'networkidle0' });
await page.type('input[type=text], input[type=email]', 'admin@example.com');
await page.type('input[type=password]', 'password');
await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);

// The designer, with a journal post and the title selected.
await page.goto(`${base}/cp/social-images/templates/editorial`, { waitUntil: 'networkidle0' });
await snooze();
const entry = await page.$eval('#si-entry', (select) => [...select.options].find((o) => o.text.startsWith('In conversation'))?.value);
await page.select('#si-entry', entry);
await settle(1500);
await page.evaluate(() => [...document.querySelectorAll('.si-layers__row')].find((row) => row.textContent.includes('Title'))?.click());
await settle(800);
await page.screenshot({ path: `${out}/designer.png` });

// Every entry's images.
await page.goto(`${base}/cp/social-images/images`, { waitUntil: 'networkidle0' });
await snooze();
await settle(1500);
await page.screenshot({ path: `${out}/images.png` });

// The templates.
await page.goto(`${base}/cp/social-images`, { waitUntil: 'networkidle0' });
await snooze();
await settle(2000);
await page.screenshot({ path: `${out}/templates.png` });

// A journal post, with its image in the sidebar.
const post = readFileSync(new URL('../content/collections/journal/2026-09-18.in-conversation-with-imogen-hale.md', import.meta.url), 'utf8');
const id = post.match(/^id: (.+)$/m)[1].trim();
await page.goto(`${base}/cp/collections/journal/entries/${id}`, { waitUntil: 'networkidle0' });
await snooze();
await settle(1500);
await page.evaluate(() => document.querySelector('.si-preview')?.scrollIntoView({ block: 'center' }));
await settle(500);
await page.screenshot({ path: `${out}/entry.png` });

await browser.close();
console.log(`Written to ${out}`);

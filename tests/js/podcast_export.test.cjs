const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const vm = require('node:vm');

const script = readFileSync(join(__dirname, '../../lib/modules/import_export/js/export.js'), 'utf8');

function setup(fetch) {
  const button = {
    href: '/export', attrs: {}, classes: new Set(),
    setAttribute(key, value) { this.attrs[key] = value; },
    removeAttribute(key) { delete this.attrs[key]; },
    addEventListener(event, handler) { this.click = () => handler({ preventDefault() {} }); }
  };
  button.classList = { add: value => button.classes.add(value), remove: value => button.classes.delete(value) };
  const download = { style: { display: 'none' } };
  const status = { dataset: Object.fromEntries(['preparing', 'slow', 'ready', 'failed', 'expired', 'timeout'].map(key => [key, key])) };
  const elements = { podlove_podcast_export: button, podlove_podcast_download: download, podlove_podcast_export_status: status };
  const timers = new Map();
  const revoked = [];
  const window = {
    fetch, AbortController,
    URL: { createObjectURL: () => 'blob:export', revokeObjectURL: url => revoked.push(url) },
    setTimeout(callback, delay) { timers.set(delay, callback); return delay; },
    clearTimeout(id) { timers.delete(id); }
  };
  vm.runInNewContext(script, { window, fetch, AbortController, document: { getElementById: id => elements[id] } });
  return { button, download, status, timers, revoked };
}

function response(type = 'application/gzip', size = 100) {
  return { ok: true, status: 200, headers: { get: name => name === 'Content-Type' ? type : 'attachment; filename="test.xml.gz"' }, blob: async () => ({ size }) };
}

test('prepares an explicit, reusable download and releases the previous file on retry', async () => {
  const ui = setup(async () => response());
  await ui.button.click();
  assert.equal(ui.status.textContent, 'ready');
  assert.equal(ui.download.style.display, '');
  assert.equal(ui.download.href, 'blob:export');
  assert.equal(ui.download.download, 'test.xml.gz');
  assert.equal(ui.button.attrs['aria-disabled'], undefined);
  assert.equal(ui.timers.size, 0);
  await ui.button.click();
  assert.deepEqual(ui.revoked, ['blob:export']);
});

test('ignores duplicate clicks while preparation is pending and displays slow feedback', async () => {
  let resolve, requests = 0;
  const ui = setup(() => { requests++; return new Promise(done => { resolve = done; }); });
  const pending = ui.button.click();
  assert.equal(ui.status.textContent, 'preparing');
  await ui.button.click();
  assert.equal(requests, 1);
  ui.timers.get(15000)();
  assert.equal(ui.status.textContent, 'slow');
  resolve(response());
  await pending;
  assert.equal(ui.status.textContent, 'ready');
});

test('reports expired links without offering a broken download', async () => {
  const ui = setup(async () => ({ status: 403 }));
  await ui.button.click();
  assert.equal(ui.status.textContent, 'expired');
  assert.equal(ui.download.style.display, 'none');
  assert.equal(ui.button.classes.has('disabled'), false);
});

test('rejects HTML login pages, empty files, HTTP errors and network failures', async () => {
  for (const fetch of [
    async () => response('text/html'),
    async () => response('application/xml', 0),
    async () => ({ ok: false, status: 500 }),
    async () => { throw new Error('Network failure'); }
  ]) {
    const ui = setup(fetch);
    await ui.button.click();
    assert.equal(ui.status.textContent, 'failed');
    assert.equal(ui.download.style.display, 'none');
    assert.equal(ui.button.classes.has('disabled'), false);
    assert.equal(ui.timers.size, 0);
  }
});

test('timeouts restore the export button so preparation can be retried', async () => {
  const ui = setup((url, options) => new Promise((resolve, reject) => {
    options.signal.addEventListener('abort', () => reject(Object.assign(new Error(), { name: 'AbortError' })));
  }));
  const pending = ui.button.click();
  ui.timers.get(300000)();
  await pending;
  assert.equal(ui.status.textContent, 'timeout');
  assert.equal(ui.button.classes.has('disabled'), false);
  assert.equal(ui.timers.size, 0);
});

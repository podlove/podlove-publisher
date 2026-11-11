(function () {
  'use strict';

  var button = document.getElementById('podlove_podcast_export');
  if (!button || !window.fetch || !window.AbortController || !window.URL.createObjectURL) {
    return;
  }

  var download = document.getElementById('podlove_podcast_download');
  var status = document.getElementById('podlove_podcast_export_status');
  var busy = false;
  var downloadUrl;

  button.addEventListener('click', async function (event) {
    event.preventDefault();
    if (busy) return;

    busy = true;
    button.setAttribute('aria-disabled', 'true');
    button.classList.add('disabled');
    download.style.display = 'none';
    if (downloadUrl) {
      window.URL.revokeObjectURL(downloadUrl);
      downloadUrl = null;
    }
    status.textContent = status.dataset.preparing;

    var controller = new AbortController();
    var slowTimer = window.setTimeout(function () {
      status.textContent = status.dataset.slow;
    }, 15000);
    var timeout = window.setTimeout(function () { controller.abort(); }, 300000);

    try {
      var response = await fetch(button.href, {
        credentials: 'same-origin', cache: 'no-store', signal: controller.signal
      });
      if (response.status === 403) throw new Error('expired');
      if (!response.ok) throw new Error('failed');
      var type = (response.headers.get('Content-Type') || '').split(';')[0].trim();
      if (type !== 'application/gzip' && type !== 'application/xml') throw new Error('failed');
      var file = await response.blob();
      if (!file.size) throw new Error('failed');
      var disposition = response.headers.get('Content-Disposition') || '';
      var filename = /filename="([^"]+)"/.exec(disposition);
      downloadUrl = window.URL.createObjectURL(file);
      download.href = downloadUrl;
      download.download = filename ? filename[1] : 'podcast.xml' + (type === 'application/gzip' ? '.gz' : '');
      download.style.display = '';
      status.textContent = status.dataset.ready;
    } catch (error) {
      status.textContent = error.name === 'AbortError' ? status.dataset.timeout :
        (error.message === 'expired' ? status.dataset.expired : status.dataset.failed);
    } finally {
      window.clearTimeout(slowTimer);
      window.clearTimeout(timeout);
      busy = false;
      button.removeAttribute('aria-disabled');
      button.classList.remove('disabled');
    }
  });
}());

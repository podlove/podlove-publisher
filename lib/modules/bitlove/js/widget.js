// based on http://bitlove.org/widget/base.js
// but adjusted to use guid instead of url for referencing

window.torrentByEnclosure = (function() { var XHR = window.AnonXMLHttpRequest || window.XMLHttpRequest;
if (!XHR)
    throw new Error("Your browser lacks support for standardized AJAX calls. What the ...?");


var urlCallbacks = {};

function resolve(url, cb) {
    if (urlCallbacks.hasOwnProperty(url)) {
	urlCallbacks[url].push(cb);
    } else {
	urlCallbacks[url] = [cb];
    }

    maySetTimeout();
}

var timeout, sending = false;
function maySetTimeout() {
    if (sending)
        return;
    var pending = Object.keys(urlCallbacks).length > 0;

    if (!timeout && pending) {
	timeout = setTimeout(function() {
	    timeout = undefined;
	    maySend();
	}, 50);
    } else if (timeout && !pending) {
	clearTimeout(timeout);
    }
}

function maySend() {
    var urls = Object.keys(urlCallbacks).slice(0, 8);
    if (urls.length > 0) {
	doSend(urls, function(response) {
	    /* Dispatch to callbacks */
	    urls.forEach(function(url) {
		var urlResponse = response[url];
		var cbs = urlCallbacks[url];
		delete urlCallbacks[url];
		cbs.forEach(function(cb) {
		    try {
			cb(urlResponse);
		    } catch(e) {
			if (console && console.error)
			    console.error(e && e.stack || e);
		    }
		});
	    });
	});
    }
}

function doSend(urls, cb) {
    var q = urls.map(function(url) {
	return "guid=" + encodeURIComponent(url);
    }).join("&");

    var cl = new XHR();
    cl.open('GET', 'https://api.bitlove.org/by-enclosure.json?' + q);
    cl.onreadystatechange = function() {
	if (this.readyState == this.DONE) {
            sending = false;

	    var response;
	    if (this.status == 200 &&
		this.responseText) {
		try {
		    response = JSON.parse(this.responseText);
		} catch (e) {
		    if (console && console.error)
			console.error(e && e.stack || e);
		}
	    }
	    cb(response);

	    /* Continue with next batch: */
	    maySend();
	}
    };
    cl.send();
    sending = true;
}
return resolve; })();
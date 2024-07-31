
const CACHE_NAME = "dgwltd-1717169183194";
const MAX_CACHE_SIZE = 50; // Maximum cache size in entries
const THEME_PATH = 'wp-content/themes/dgwltd/';

// This is the service worker with the Cache-first network
const precacheFiles = [
  /* Add an array of files to precache for your app */
  `${THEME_PATH}/src/html/offline.html`, 
  `${THEME_PATH}/dist/css/main.css`, 
  `${THEME_PATH}/dist/js/app.min.js`,
  `${THEME_PATH}/dist/js/govuk-frontend-5.3.0.min.js`, 
  `${THEME_PATH}/dist/images/fav/favicon.png`,
  `${THEME_PATH}/dist/images/fav/favicon-192x192.png`, 
  `${THEME_PATH}/dist/fonts/soehne/soehne-halbfett.woff2`,
  `${THEME_PATH}/dist/fonts/soehne/soehne-kraftig.woff2`
];

async function fromCache(request) {
  const cache = await caches.open(CACHE_NAME);
  const matching = await cache.match(request);
  if (!matching || matching.status === 404) {
    throw new Error("no-match");
  }
  return matching;
}

async function updateCache(request, response) {
  const cache = await caches.open(CACHE_NAME);
  await cache.put(request, response);
  const keys = await cache.keys();
  if (keys.length > MAX_CACHE_SIZE) {
    await cache.delete(keys[0]); // Delete the oldest entry
  }
}

self.addEventListener("install", function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(precacheFiles);
    })
  );
});

self.addEventListener("activate", function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (CACHE_NAME !== cacheName && cacheName.startsWith("dgwltd")) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

self.addEventListener("fetch", function (event) { 
  if (event.request.method !== "GET" || event.request.url.match(/wp-admin/) || event.request.url.match(/preview=true/)) {
    return;
  }

  event.respondWith(
    fromCache(event.request).then(
      function (response) {
        event.waitUntil(
          fetch(event.request).then(function (response) {
            return updateCache(event.request, response);
          })
        );
        return response;
      },
      function () {
        return fetch(event.request)
          .then(function (response) {
            event.waitUntil(updateCache(event.request, response.clone()));
            return response;
          })
          .catch(function (error) {
            console.log("[PWA Builder] Network request failed and no cache." + error);
            return caches.match(`${THEME_PATH}/src/html/offline.html`);
          });
      }
    )
  );
});
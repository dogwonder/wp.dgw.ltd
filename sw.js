
<<<<<<< HEAD
const CACHE = "dgwltd-1697728702031";
=======
const CACHE_NAME = "dgwltd-1716894470695";
const MAX_CACHE_SIZE = 50; // Maximum cache size in entries
const THEME_PATH = 'wp-content/themes/dgwltd/';
>>>>>>> 6cbfa2e (first push)

// This is the service worker with the Cache-first network
const precacheFiles = [
  /* Add an array of files to precache for your app */
<<<<<<< HEAD
  'wp-content/themes/dgwltd/src/html/offline.html', 
  'wp-content/themes/dgwltd/dist/css/main.css', 
  'wp-content/themes/dgwltd/dist/js/app.min.js',
  'wp-content/themes/dgwltd/dist/js/govuk-frontend-4.4.0.min.js', 
  'wp-content/themes/dgwltd/dist/images/fav/favicon.png',
  'wp-content/themes/dgwltd/dist/images/fav/favicon-192x192.png', 
  'wp-content/themes/dgwltd/dist/fonts/soehne/soehne-halbfett.woff2',
  'wp-content/themes/dgwltd/dist/fonts/soehne/soehne-kraftig.woff2'
];

self.addEventListener("install", function (event) {
  console.log("[PWA Builder] Install Event processing");

  console.log("[PWA Builder] Skip waiting on install");
  self.skipWaiting();

  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      console.log("[PWA Builder] Caching pages during install");
=======
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
>>>>>>> 6cbfa2e (first push)
      return cache.addAll(precacheFiles);
    })
  );
});
<<<<<<< HEAD
  

// If any fetch fails, it will look for the request in the cache and serve it from there first
self.addEventListener("fetch", function (event) { 
  if (event.request.method !== "GET") return;
=======

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
>>>>>>> 6cbfa2e (first push)

  event.respondWith(
    fromCache(event.request).then(
      function (response) {
<<<<<<< HEAD
        // The response was found in the cache so we respond with it and update the entry

        // This is where we call the server to get the newest version of the
        // file to use the next time we show view
=======
>>>>>>> 6cbfa2e (first push)
        event.waitUntil(
          fetch(event.request).then(function (response) {
            return updateCache(event.request, response);
          })
        );
<<<<<<< HEAD

        return response;
      },
      function () {
        // The response was not found in the cache so we look for it on the server
        return fetch(event.request)
          .then(function (response) {
            // If request was success, add or update it in the cache
            event.waitUntil(updateCache(event.request, response.clone()));

=======
        return response;
      },
      function () {
        return fetch(event.request)
          .then(function (response) {
            event.waitUntil(updateCache(event.request, response.clone()));
>>>>>>> 6cbfa2e (first push)
            return response;
          })
          .catch(function (error) {
            console.log("[PWA Builder] Network request failed and no cache." + error);
<<<<<<< HEAD
=======
            return caches.match(`${THEME_PATH}/src/html/offline.html`);
>>>>>>> 6cbfa2e (first push)
          });
      }
    )
  );
<<<<<<< HEAD
});

function fromCache(request) {
    // Check to see if you have it in the cache
    // Return response
    // If not in the cache, then return
    return caches.open(CACHE).then(function (cache) {
      return cache.match(request).then(function (matching) {
        if (!matching || matching.status === 404) {
          return Promise.reject("no-match");
        }
  
        return matching;
      });
    });
  }
  
  function updateCache(request, response) {
    return caches.open(CACHE).then(function (cache) {
      return cache.put(request, response);
    });
  }


self.addEventListener("activate", function(event) {
    event.waitUntil(
      //Delete old caches
      caches.keys().then(function(cacheNames) {
        return Promise.all(
          cacheNames.map(function(cacheName) {
            if (CACHE !== cacheName &&  cacheName.startsWith("dgwltd")) {
              return caches.delete(cacheName);
            }
          })
        );
      }).then(function() {
        console.log('[ServiceWorker] Claiming clients for version - 1697728702031');
        return self.clients.claim();
      })  
    );
=======
>>>>>>> 6cbfa2e (first push)
});
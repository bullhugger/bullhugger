const cacheName = 'v2';

self.addEventListener('install', onEvent => {
  console.log('Service Worker: Installed');
});

self.addEventListener('activate',onEvent=> {
  console.log('Service Worker: Activated');
  onEvent.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== cacheName) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch',onEvent=> {
  console.log('Service Worker: Fetching');
  onEvent.respondWith(
    fetch(e.request)
      .then(res => {
        const resClone = res.clone();
        caches.open(cacheName).then(cache => {
          cache.put(onEvent.request, resClone);
        });
        return res;
      })
      .catch(err => caches.match(onEvent.request).then(res => res))
  );
});

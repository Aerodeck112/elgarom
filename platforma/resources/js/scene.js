/*
 * Loader for the homepage zinc-plating illustration.
 * Three.js + GSAP are fetched only here, only on the homepage, and only when the device
 * can run them. Otherwise the high-quality static image stays and the text steps render as a list.
 */
const root = document.querySelector('[data-scene]');

function webglAvailable() {
  try {
    const c = document.createElement('canvas');
    return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl')));
  } catch (_) {
    return false;
  }
}

function goStatic() {
  root.classList.add('is-static');
  root.classList.remove('is-live');
  const poster = root.querySelector('.scene__poster');
  if (poster && poster.dataset.final !== 'done') {
    poster.src = poster.src.replace('scena-poster', 'scena-final');
    poster.dataset.final = 'done';
  }
}

if (root) {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  const saveData = navigator.connection && navigator.connection.saveData;
  const capture = location.hash.startsWith('#scene-capture=');
  if ((reduce.matches && !capture) || saveData || !webglAvailable()) {
    goStatic();
  } else {
    const start = () => import('./scene/main.js')
      .then((m) => m.mount(root, { onFail: goStatic }))
      .catch(() => goStatic());
    if (capture) start();
    else if ('requestIdleCallback' in window) window.requestIdleCallback(start, { timeout: 1200 });
    else setTimeout(start, 250);
  }
  reduce.addEventListener?.('change', (e) => { if (e.matches) { window.dispatchEvent(new Event('elg:scene-stop')); goStatic(); } });
}

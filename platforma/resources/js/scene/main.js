/*
 * ELGAROM – scroll-linked illustration of electrolytic zinc plating.
 *
 * A perforated steel L-bracket (the logo's geometry) hangs on a rack under the transport
 * gantry, is lowered into a weak-acid zinc bath modelled on the real Elgarom tanks (beige
 * polypropylene tank, green-grey agitated solution), receives its zinc layer, is lifted out
 * wet and finally shows a passivated zinc finish while a light sweeps across it.
 *
 * Performance: one renderer, ~20 draw calls, instanced bubbles/drops, procedural textures
 * (no downloads), DPR capped, 30 fps cap on small screens, rendering stops when the scene
 * is off-screen or the tab is hidden. Scrolling stays native (sticky stage + scrub).
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const clamp01 = (v) => Math.min(1, Math.max(0, v));
const lerp = (a, b, t) => a + (b - a) * t;
const smooth = (t) => t * t * (3 - 2 * t);
const seg = (t, a, b) => clamp01((t - a) / (b - a));

const WATER = new THREE.Color('#4f6d64');
const RAW = new THREE.Color('#8b9094');
const ZINC = new THREE.Color('#d5dce2');

/* ---------------------------------------------------------------- textures */
function noiseCanvas(size, fn) {
  const c = document.createElement('canvas');
  c.width = c.height = size;
  const ctx = c.getContext('2d');
  const img = ctx.createImageData(size, size);
  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const [r, g, b] = fn(x / size, y / size);
      const i = (y * size + x) * 4;
      img.data[i] = r; img.data[i + 1] = g; img.data[i + 2] = b; img.data[i + 3] = 255;
    }
  }
  ctx.putImageData(img, 0, 0);
  return c;
}

// Cheap tileable value noise
function makeNoise(seed = 1) {
  const n = 64;
  const p = new Float32Array(n * n);
  let s = seed;
  for (let i = 0; i < p.length; i++) { s = (s * 16807) % 2147483647; p[i] = s / 2147483647; }
  const at = (x, y) => p[((y % n + n) % n) * n + ((x % n + n) % n)];
  return (u, v, f) => {
    const x = u * f, y = v * f;
    const xi = Math.floor(x), yi = Math.floor(y);
    const xf = smooth(x - xi), yf = smooth(y - yi);
    const m = f;
    const a = at(xi % m, yi % m), b = at((xi + 1) % m, yi % m), c = at(xi % m, (yi + 1) % m), d = at((xi + 1) % m, (yi + 1) % m);
    return lerp(lerp(a, b, xf), lerp(c, d, xf), yf);
  };
}

function makeTextures(small) {
  const size = small ? 256 : 512;
  const nz = makeNoise(7);
  // Raw hot-rolled steel: dark grey with mottled mill scale, slight cool/warm variation, no rust orange.
  const raw = noiseCanvas(size, (u, v) => {
    const m = nz(u, v, 4) * 0.55 + nz(u, v, 16) * 0.3 + nz(u, v, 48) * 0.15;
    const g = 150 + m * 70;
    return [g + m * 6, g + 2, g + 5 - m * 3];
  });
  // Electroplated zinc: bright and smooth with very fine grain (brighteners), soft variation.
  const zinc = noiseCanvas(size, (u, v) => {
    const m = nz(u, v, 8) * 0.25 + nz(u, v, 64) * 0.75;
    const g = 218 + m * 22;
    return [g - 6, g, g + 6];
  });
  // Height -> normal map for micro texture
  const h = (u, v) => nz(u, v, 32) * 0.6 + nz(u, v, 64) * 0.4;
  const e = 1 / size;
  const normal = noiseCanvas(size, (u, v) => {
    const dx = (h(u + e, v) - h(u - e, v)) * 6;
    const dy = (h(u, v + e) - h(u, v - e)) * 6;
    const len = Math.hypot(dx, dy, 1);
    return [(-dx / len * 0.5 + 0.5) * 255, (-dy / len * 0.5 + 0.5) * 255, (1 / len * 0.5 + 0.5) * 255];
  });
  const tex = (c, srgb) => {
    const t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    t.repeat.set(2, 2);
    if (srgb) t.colorSpace = THREE.SRGBColorSpace;
    return t;
  };
  return { raw: tex(raw, true), zinc: tex(zinc, true), normal: tex(normal, false) };
}

/* ------------------------------------------------- underwater tint helper */
// Anything below the solution surface (y = 0) is seen through the liquid: tint it.
function underwater(material, uniforms, strength = 0.82) {
  const prev = material.onBeforeCompile;
  material.onBeforeCompile = (shader) => {
    prev?.(shader);
    shader.uniforms.uWater = uniforms.uWater;
    shader.uniforms.uSurface = uniforms.uSurface;
    shader.vertexShader = shader.vertexShader
      .replace('#include <common>', '#include <common>\nvarying float vWorldY;')
      .replace('#include <worldpos_vertex>', '#include <worldpos_vertex>\nvWorldY = (modelMatrix * vec4(transformed, 1.0)).y;');
    shader.fragmentShader = shader.fragmentShader
      .replace('#include <common>', '#include <common>\nvarying float vWorldY;\nuniform vec3 uWater;\nuniform float uSurface;')
      .replace('#include <opaque_fragment>', `#include <opaque_fragment>
        float uw = smoothstep(uSurface + 0.01, uSurface - 1.6, vWorldY);
        gl_FragColor.rgb = mix(gl_FragColor.rgb, uWater * (0.55 + 0.45 * smoothstep(-3.0, uSurface, vWorldY)), uw * ${strength.toFixed(2)});`);
  };
  material.customProgramCacheKey = () => 'uw' + strength;
  return material;
}

/* ---------------------------------------------------------------- geometry */
function bracketGeometry() {
  // Perforated L-bracket with two triangular gussets. Units ~ metres / 1.2.
  const L = 1.9, H = 1.45, D = 0.72, T = 0.07;
  const bevel = { depth: T, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 2, curveSegments: 18 };

  const slot = (cx, cy, w, h) => {
    const p = new THREE.Path();
    const r = h / 2;
    p.absarc(cx - w / 2 + r, cy, r, Math.PI / 2, Math.PI * 1.5, false);
    p.absarc(cx + w / 2 - r, cy, r, Math.PI * 1.5, Math.PI / 2, false);
    return p;
  };
  const hole = (cx, cy, r) => { const p = new THREE.Path(); p.absarc(cx, cy, r, 0, Math.PI * 2, true); return p; };

  // vertical flange in XY (facing +Z), bottom edge at y = 0
  const v = new THREE.Shape();
  const c = 0.06;
  v.moveTo(-L / 2, 0); v.lineTo(L / 2, 0); v.lineTo(L / 2, H - c); v.lineTo(L / 2 - c, H); v.lineTo(-L / 2 + c, H); v.lineTo(-L / 2, H - c); v.lineTo(-L / 2, 0);
  v.holes.push(slot(-0.55, H - 0.18, 0.26, 0.09), slot(0.55, H - 0.18, 0.26, 0.09), slot(0, H - 0.18, 0.26, 0.09));
  [-0.68, -0.34, 0, 0.34, 0.68].forEach((x) => v.holes.push(hole(x, 0.62, 0.075)));
  v.holes.push(slot(-0.36, 0.95, 0.42, 0.11), slot(0.36, 0.95, 0.42, 0.11));
  const vg = new THREE.ExtrudeGeometry(v, bevel);
  vg.translate(0, 0, -T);

  // horizontal flange in XZ, extending toward the camera
  const hshape = new THREE.Shape();
  hshape.moveTo(-L / 2, 0); hshape.lineTo(L / 2, 0); hshape.lineTo(L / 2, D - c); hshape.lineTo(L / 2 - c, D); hshape.lineTo(-L / 2 + c, D); hshape.lineTo(-L / 2, D - c); hshape.lineTo(-L / 2, 0);
  [-0.6, 0.6].forEach((x) => hshape.holes.push(slot(x, D * 0.6, 0.3, 0.1)));
  hshape.holes.push(hole(0, D * 0.6, 0.08));
  const hg = new THREE.ExtrudeGeometry(hshape, bevel);
  hg.rotateX(Math.PI / 2);
  hg.translate(0, T, -T);

  // gussets
  const g = new THREE.Shape();
  g.moveTo(0, 0); g.lineTo(D * 0.78, 0); g.lineTo(0, H * 0.62); g.lineTo(0, 0);
  const gussets = [-0.62, 0.62].map((x) => {
    const gg = new THREE.ExtrudeGeometry(g, { ...bevel, depth: T * 0.9 });
    gg.rotateY(-Math.PI / 2);
    gg.translate(x + T * 0.45, T, 0);
    return gg;
  });
  const merged = mergeGeometries([vg, hg, ...gussets], false);
  merged.computeVertexNormals();
  merged.computeBoundingSphere();
  return { geometry: merged, L, H, D };
}

/* ------------------------------------------------------------------ mount */
export function mount(root, { onFail } = {}) {
  const canvas = root.querySelector('[data-scene-canvas]');
  const stage = root.querySelector('.scene__stage');
  const small = Math.min(window.innerWidth, window.innerHeight) < 700 || window.innerWidth < 760;
  const captureMatch = location.hash.match(/^#scene-capture=([\d.]+)/);

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: !small, alpha: true, powerPreference: small ? 'low-power' : 'high-performance', preserveDrawingBuffer: !!captureMatch });
  } catch (e) {
    onFail?.();
    return;
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, small ? 1.25 : 1.75));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;

  const scene = new THREE.Scene();
  const pmrem = new THREE.PMREMGenerator(renderer);
  const env = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
  scene.environment = env;
  scene.environmentIntensity = 0.85;
  pmrem.dispose();

  const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 60);
  const shared = { uWater: { value: WATER.clone().convertSRGBToLinear() }, uSurface: { value: 0 }, uTime: { value: 0 } };
  const tx = makeTextures(small);

  /* lights */
  scene.add(new THREE.HemisphereLight('#dfe7ec', '#2a3034', 0.55));
  const key = new THREE.DirectionalLight('#f4f7f9', 2.1);
  key.position.set(-4, 7, 6);
  scene.add(key);
  const rim = new THREE.DirectionalLight('#a9bccb', 1.1);
  rim.position.set(5, 3, -4);
  scene.add(rim);
  const sweep = new THREE.SpotLight('#ffffff', 0, 14, 0.22, 0.85, 1.2);
  sweep.position.set(-3, 2.6, 4.2);
  scene.add(sweep, sweep.target);

  /* tank modelled on the Elgarom line: beige PP tank, light rim, anode bars */
  const tank = new THREE.Group();
  const W = 5.6, Dp = 2.3, wall = 0.12, rimH = 0.38, depth = 3.4;
  const pp = underwater(new THREE.MeshStandardMaterial({ color: '#c4b99d', roughness: 0.78, metalness: 0, envMapIntensity: 0.6 }), shared, 0.9);
  const ppRim = new THREE.MeshStandardMaterial({ color: '#e6dfcc', roughness: 0.5, metalness: 0 });
  const box = (w, h, d, m, x, y, z) => { const b = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), m); b.position.set(x, y, z); tank.add(b); return b; };
  box(W + wall * 2, depth + rimH, wall, pp, 0, (rimH - depth) / 2, Dp / 2 + wall / 2); // front
  box(W + wall * 2, depth + rimH, wall, pp, 0, (rimH - depth) / 2, -Dp / 2 - wall / 2); // back
  box(wall, depth + rimH, Dp, pp, -W / 2 - wall / 2, (rimH - depth) / 2, 0);
  box(wall, depth + rimH, Dp, pp, W / 2 + wall / 2, (rimH - depth) / 2, 0);
  box(W + wall * 2, 0.06, Dp + wall * 2, pp, 0, -depth, 0); // bottom
  box(W + 0.5, 0.07, 0.24, ppRim, 0, rimH + 0.035, Dp / 2 + 0.06); // rim lips
  box(W + 0.5, 0.07, 0.24, ppRim, 0, rimH + 0.035, -Dp / 2 - 0.06);
  box(0.24, 0.07, Dp + 0.5, ppRim, -W / 2 - 0.06, rimH + 0.035, 0);
  box(0.24, 0.07, Dp + 0.5, ppRim, W / 2 + 0.06, rimH + 0.035, 0);
  // horizontal reinforcement ribs, as on welded PP process tanks
  [-0.35, -1.25, -2.15].forEach((y) => box(W + wall * 2 + 0.1, 0.12, 0.08, ppRim, 0, y, Dp / 2 + wall + 0.04));
  [-0.6, 0.6].forEach((x) => box(0.1, depth + rimH - 0.1, 0.07, ppRim, x * W * 0.62, (rimH - depth) / 2, Dp / 2 + wall + 0.035));
  // anode bus bars and anode baskets along the long sides
  const anodeMat = underwater(new THREE.MeshStandardMaterial({ color: '#3d4347', roughness: 0.45, metalness: 0.8 }), shared, 0.9);
  const busMat = new THREE.MeshStandardMaterial({ color: '#b07a52', roughness: 0.35, metalness: 1 });
  [-1, 1].forEach((side) => {
    const bus = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, W + 0.3, 12), busMat);
    bus.rotation.z = Math.PI / 2;
    bus.position.set(0, rimH + 0.13, side * (Dp / 2 - 0.12));
    tank.add(bus);
    for (let i = 0; i < 7; i++) {
      const a = new THREE.Mesh(new THREE.BoxGeometry(0.42, 2.4, 0.05), anodeMat);
      a.position.set(-W / 2 + 0.55 + i * ((W - 1.1) / 6), 0.18 - 1.2, side * (Dp / 2 - 0.16));
      tank.add(a);
    }
  });
  // saddles where the flight bar rests
  const saddleMat = new THREE.MeshStandardMaterial({ color: '#5c666e', roughness: 0.4, metalness: 0.7 });
  [-1, 1].forEach((s) => box(0.3, 0.12, 0.5, saddleMat, s * (W / 2 + 0.06), rimH + 0.12, 0));
  scene.add(tank);

  /* solution surface: waves + ripple rings, physically lit */
  const segX = small ? 64 : 128, segY = small ? 28 : 56;
  const waterGeo = new THREE.PlaneGeometry(W, Dp, segX, segY);
  const waterMat = new THREE.MeshPhysicalMaterial({
    color: '#46645a', roughness: 0.1, metalness: 0, transparent: true, opacity: 0.8, depthWrite: false,
    clearcoat: 1, clearcoatRoughness: 0.08, envMapIntensity: 1.1,
  });
  const ripple = { origin: { value: new THREE.Vector2(0, 0) }, start: { value: -100 }, amp: { value: 0 } };
  const agitation = { value: 0.35 };
  waterMat.onBeforeCompile = (shader) => {
    shader.uniforms.uTime = shared.uTime;
    shader.uniforms.uRO = ripple.origin;
    shader.uniforms.uRS = ripple.start;
    shader.uniforms.uRA = ripple.amp;
    shader.uniforms.uAg = agitation;
    const fn = `
      uniform float uTime; uniform vec2 uRO; uniform float uRS; uniform float uRA; uniform float uAg;
      float hgt(vec2 p) {
        float h = 0.0;
        h += sin(p.x * 3.1 + uTime * 1.7) * 0.010;
        h += sin(p.y * 4.3 - uTime * 1.3 + p.x * 1.2) * 0.008;
        h += sin((p.x + p.y) * 9.0 + uTime * 3.2) * 0.004 * uAg;
        h += sin((p.x * 1.7 - p.y) * 15.0 - uTime * 4.1) * 0.0035 * uAg;
        float t = uTime - uRS;
        float d = distance(p, uRO);
        if (t > 0.0 && t < 6.0) {
          float front = smoothstep(t * 1.4 + 0.3, t * 1.4 - 0.2, d);
          h += sin(d * 11.0 - t * 7.0) * 0.045 * uRA * exp(-t * 0.9) * exp(-d * 0.7) * front;
        }
        return h;
      }`;
    shader.vertexShader = shader.vertexShader
      .replace('#include <common>', '#include <common>\n' + fn)
      .replace('#include <beginnormal_vertex>', `
        vec2 pp = position.xy;
        float e = 0.02;
        float hx = hgt(pp + vec2(e, 0.0)) - hgt(pp - vec2(e, 0.0));
        float hy = hgt(pp + vec2(0.0, e)) - hgt(pp - vec2(0.0, e));
        vec3 objectNormal = normalize(vec3(-hx / (2.0 * e), -hy / (2.0 * e), 1.0));
        #ifdef USE_TANGENT
          vec3 objectTangent = vec3(1.0, 0.0, 0.0);
        #endif`)
      .replace('#include <begin_vertex>', 'vec3 transformed = vec3(position.xy, hgt(position.xy));');
  };
  const water = new THREE.Mesh(waterGeo, waterMat);
  water.rotation.x = -Math.PI / 2;
  water.renderOrder = 2;
  scene.add(water);

  /* transport gantry (white, like the real hoist) */
  const gantry = new THREE.Group();
  const white = new THREE.MeshStandardMaterial({ color: '#e9ecee', roughness: 0.5, metalness: 0.15 });
  const gx = (w, h, d, x, y, z) => { const b = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), white); b.position.set(x, y, z); gantry.add(b); };
  gx(3.2, 0.32, 0.42, 0, 5.15, 0);
  gx(0.9, 0.5, 0.7, 0, 4.85, 0);
  scene.add(gantry);

  /* load: flight bar + rack + bracket */
  const load = new THREE.Group();
  const flight = new THREE.Mesh(new THREE.BoxGeometry(W + 0.8, 0.16, 0.16), new THREE.MeshStandardMaterial({ color: '#c9ced2', roughness: 0.35, metalness: 0.9 }));
  flight.position.y = 3.05;
  load.add(flight);
  [-1, 1].forEach((s) => {
    const cu = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.18, 0.2), busMat);
    cu.position.set(s * (W / 2 + 0.06), 3.05, 0);
    load.add(cu);
  });
  const rackMat = underwater(new THREE.MeshStandardMaterial({ color: '#3c4f62', roughness: 0.55, metalness: 0.2 }), shared); // PVC-coated rack
  const rod = (x1, y1, x2, y2, r = 0.028) => {
    const len = Math.hypot(x2 - x1, y2 - y1);
    const m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, len, 10), rackMat);
    m.position.set((x1 + x2) / 2, (y1 + y2) / 2, -0.12);
    m.rotation.z = Math.atan2(x1 - x2, y2 - y1);
    load.add(m);
  };
  rod(-0.55, 3.0, -0.55, 1.55, 0.034);
  rod(0.55, 3.0, 0.55, 1.55, 0.034);
  rod(-0.9, 2.15, 0.9, 2.15);
  rod(-0.9, 1.75, 0.9, 1.75);
  [-0.55, 0, 0.55].forEach((x) => {
    rod(x, 1.75, x, 1.32, 0.02);
    const hook = new THREE.Mesh(new THREE.TorusGeometry(0.06, 0.016, 8, 16, Math.PI), rackMat);
    hook.position.set(x, 1.3, -0.08);
    hook.rotation.set(Math.PI / 2, 0, Math.PI);
    load.add(hook);
  });

  const { geometry: bracketGeo, H } = bracketGeometry();
  const partUniforms = { uCoat: { value: 0 }, uZincMap: { value: tx.zinc } };
  const partMat = new THREE.MeshPhysicalMaterial({
    color: RAW.clone(), map: tx.raw, normalMap: tx.normal, normalScale: new THREE.Vector2(0.18, 0.18),
    metalness: 1, roughness: 0.55, envMapIntensity: 1.0,
    iridescence: 0, iridescenceIOR: 1.75, iridescenceThicknessMinimum: 250, iridescenceThicknessMaximum: 330,
    clearcoat: 0, clearcoatRoughness: 0.12,
  });
  partMat.onBeforeCompile = (shader) => {
    shader.uniforms.uCoat = partUniforms.uCoat;
    shader.uniforms.uZincMap = partUniforms.uZincMap;
    shader.fragmentShader = shader.fragmentShader
      .replace('#include <common>', '#include <common>\nuniform float uCoat;\nuniform sampler2D uZincMap;')
      .replace('#include <map_fragment>', `
        #ifdef USE_MAP
          vec4 rawC = texture2D(map, vMapUv);
          vec4 znC = texture2D(uZincMap, vMapUv);
          diffuseColor *= mix(rawC, znC, uCoat);
        #endif`);
  };
  underwater(partMat, shared, 0.62);
  const part = new THREE.Mesh(bracketGeo, partMat);
  part.position.set(0, 1.3 - H + 0.18, 0.04);
  const partPivot = new THREE.Group();
  partPivot.add(part);
  load.add(partPivot);
  scene.add(load);

  // cables from gantry to flight bar
  const cableMat = new THREE.MeshStandardMaterial({ color: '#2c343a', roughness: 0.6 });
  const cables = [-0.9, 0.9].map((x) => { const c = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.018, 1, 6), cableMat); c.position.x = x; scene.add(c); return c; });

  /* bubbles (gas at the part during deposition + air agitation) and drips */
  const bubbleCount = small ? 70 : 220;
  const bubbleMat = underwater(new THREE.MeshStandardMaterial({ color: '#e6efed', roughness: 0.15, metalness: 0, transparent: true, opacity: 0.55, depthWrite: false }), shared, 0.6);
  const bubbles = new THREE.InstancedMesh(new THREE.SphereGeometry(1, 8, 6), bubbleMat, bubbleCount);
  bubbles.renderOrder = 1;
  const bState = Array.from({ length: bubbleCount }, (_, i) => ({ x: 0, y: -10, z: 0, v: 0, s: 0, part: i % 3 !== 0 }));
  scene.add(bubbles);
  const dropCount = small ? 18 : 40;
  const drops = new THREE.InstancedMesh(new THREE.SphereGeometry(1, 6, 5), new THREE.MeshStandardMaterial({ color: '#cfe0dc', roughness: 0.05, metalness: 0, transparent: true, opacity: 0.8 }), dropCount);
  const dState = Array.from({ length: dropCount }, () => ({ x: 0, y: -10, z: 0, v: 0, s: 0 }));
  scene.add(drops);
  const dummy = new THREE.Object3D();

  /* ---------------------------------------------- scroll → scene state */
  const state = { p: captureMatch ? Number(captureMatch[1]) : 0 };
  let anchors = [0, 0.18, 0.36, 0.52, 0.68, 0.84, 1];
  const steps = [...root.querySelectorAll('.scene__step')];
  const marks = [...root.querySelectorAll('[data-scene-progress] span')];
  const computeAnchors = () => {
    const top = root.getBoundingClientRect().top + window.scrollY;
    const range = Math.max(1, root.offsetHeight - window.innerHeight);
    const a = steps.map((s) => clamp01((s.getBoundingClientRect().top + window.scrollY + s.offsetHeight * 0.5 - window.innerHeight * 0.5 - top) / range));
    if (a.length >= 5) anchors = [0, ...a.slice(0, 5), 1];
  };

  // Map progress p (0..1) onto phase time t (0..6) so visuals stay in sync with the text cards.
  const phase = (p) => {
    for (let i = 0; i < anchors.length - 1; i++) {
      if (p <= anchors[i + 1]) return i + clamp01((p - anchors[i]) / Math.max(1e-4, anchors[i + 1] - anchors[i]));
    }
    return 6;
  };

  let wasUnder = false;
  let lastT = 0;
  const camPos = new THREE.Vector3();
  const camTarget = new THREE.Vector3();
  let viewShiftX = 0, viewShiftY = 0;

  const applyState = (t, time, dt) => {
    // A–B: hang, then descend into the bath (t 1→2). C: deposition (2→3). D: lift (3→4). E: passivated finish + light (4→6).
    const down = smooth(seg(t, 1, 2));
    const up = smooth(seg(t, 3, 4));
    const loadY = lerp(lerp(1.05, -2.62, down), 0.62, up);
    const sway = (1 - down) * Math.sin(time * 0.9) * 0.012 + up * Math.sin(time * 0.7) * 0.008;
    load.position.y = loadY;
    load.rotation.z = sway;

    const coat = smooth(seg(t, 2.15, 2.95));
    partUniforms.uCoat.value = coat;
    partMat.color.copy(RAW).lerp(ZINC, coat);
    const wet = seg(t, 3.3, 3.9) * (1 - seg(t, 4.2, 5.3));
    const passiv = smooth(seg(t, 4.0, 5.0));
    partMat.roughness = lerp(0.56, 0.24, coat) - wet * 0.1;
    partMat.clearcoat = wet * 0.9;
    partMat.iridescence = coat * (0.05 + passiv * 0.2);
    partMat.normalScale.setScalar(lerp(0.22, 0.06, coat));

    partPivot.rotation.y = lerp(0, -0.42, smooth(seg(t, 4.1, 6)));
    partPivot.rotation.x = lerp(0, 0.06, smooth(seg(t, 4.1, 6)));

    // light sweep across the finished surface
    const sw = seg(t, 4.5, 5.9);
    sweep.intensity = Math.sin(sw * Math.PI) * 60;
    sweep.position.set(lerp(-3.6, 3.4, sw), 2.2 + Math.sin(sw * Math.PI) * 0.6, 4.4);
    sweep.target.position.set(lerp(-1.2, 1.2, sw), loadY + 0.6, 0);

    // ripples when the part breaks the surface
    const partTop = loadY + 1.3;
    const partBottom = loadY + part.position.y;
    const under = partBottom < 0.02 && partTop > -0.1;
    if (under !== wasUnder && Math.abs(t - lastT) < 0.5) {
      ripple.start.value = time;
      ripple.origin.value.set(0, 0.1);
      ripple.amp.value = 1;
    }
    wasUnder = under;
    lastT = t;
    agitation.value = 0.35 + 0.9 * seg(t, 1.7, 2.2) * (1 - seg(t, 3, 3.4));

    // gantry cables
    cables.forEach((c) => { const top = 4.6, bot = loadY + 3.05; c.scale.y = Math.max(0.01, top - bot); c.position.y = (top + bot) / 2; });

    // bubbles
    const deposit = seg(t, 1.9, 2.1) * (1 - seg(t, 2.95, 3.15));
    const ambient = 0.2;
    for (let i = 0; i < bubbleCount; i++) {
      const b = bState[i];
      const active = b.part ? deposit > (i / bubbleCount) * 0.9 : (i / bubbleCount) < ambient;
      if (b.y > -0.02 || b.s === 0) {
        if (!active) { b.s = 0; } else if (b.part) {
          b.x = (Math.random() - 0.5) * 1.9; b.y = loadY + part.position.y + Math.random() * 1.4; b.z = (Math.random() - 0.3) * 0.6;
          b.v = 0.25 + Math.random() * 0.4; b.s = 0.014 + Math.random() * 0.02;
        } else {
          b.x = (Math.random() - 0.5) * (W - 0.6); b.y = -2.9 + Math.random() * 0.4; b.z = (Math.random() - 0.5) * (Dp - 0.6);
          b.v = 0.5 + Math.random() * 0.5; b.s = 0.01 + Math.random() * 0.015;
        }
      }
      if (b.s > 0) { b.y += b.v * dt; b.x += Math.sin(time * 3 + i) * 0.002; }
      dummy.position.set(b.x, Math.min(b.y, -0.015), b.z);
      dummy.scale.setScalar(b.s);
      dummy.updateMatrix();
      bubbles.setMatrixAt(i, dummy.matrix);
    }
    bubbles.instanceMatrix.needsUpdate = true;

    // drips while the wet part hangs above the bath
    const dripping = seg(t, 3.5, 3.8) * (1 - seg(t, 4.3, 4.8));
    for (let i = 0; i < dropCount; i++) {
      const d = dState[i];
      if (d.y < 0 || d.s === 0) {
        if (dripping > i / dropCount && Math.random() < 0.05) {
          d.x = (Math.random() - 0.5) * 1.8; d.y = partBottom + 0.02; d.z = Math.random() * 0.7; d.v = 0; d.s = 0.012 + Math.random() * 0.01;
        } else if (d.y < 0) { d.s = 0; }
      }
      if (d.s > 0) { d.v += 9.8 * dt * 0.35; d.y -= d.v * dt; }
      dummy.position.set(d.x, d.y, d.z);
      dummy.scale.set(d.s, d.s * 1.5, d.s);
      dummy.updateMatrix();
      drops.setMatrixAt(i, dummy.matrix);
    }
    drops.instanceMatrix.needsUpdate = true;

    // camera
    const c1 = smooth(seg(t, 0, 2)), c2 = smooth(seg(t, 3, 4.5)), c3 = smooth(seg(t, 4.5, 6));
    camPos.set(lerp(lerp(4.6, 3.8, c1), lerp(2.2, -1.4, c3), c2), lerp(lerp(6.4, 5.6, c1), lerp(3.4, 3.0, c3), c2), lerp(lerp(12.5, 11.2, c1), lerp(7.6, 7.4, c3), c2));
    camTarget.set(lerp(0.2, 0, c2), lerp(lerp(1.4, 0.2, c1), loadY + 0.9, c2), 0);
    camera.position.copy(camPos);
    camera.lookAt(camTarget);
  };

  /* resize: shift the scene away from the text column */
  const resize = () => {
    const w = stage.clientWidth, h = stage.clientHeight;
    renderer.setSize(w, h, false);
    const aspect = w / h;
    camera.aspect = aspect;
    camera.fov = aspect < 0.8 ? 44 : 32;
    viewShiftX = aspect > 1.15 ? -w * 0.27 : 0;
    viewShiftY = aspect < 0.8 ? h * 0.16 : 0;
    camera.setViewOffset(w, h, viewShiftX, viewShiftY, w, h);
    camera.updateProjectionMatrix();
    computeAnchors();
  };
  const ro = new ResizeObserver(resize);
  ro.observe(stage);
  resize();

  /* render loop: only while visible */
  let visible = true;
  let running = false;
  let raf = 0;
  let last = performance.now();
  let acc = 0;
  const minFrame = small ? 1 / 30 : 0;
  const clock = { t: 0 };

  const frame = (now) => {
    raf = requestAnimationFrame(frame);
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    acc += dt;
    if (acc < minFrame) return;
    const step = acc;
    acc = 0;
    clock.t += step;
    shared.uTime.value = clock.t;
    applyState(phase(state.p), clock.t, step);
    renderer.render(scene, camera);
    marks.forEach((m, i) => m.classList.toggle('is-on', phase(state.p) >= i + 0.5));
  };
  const start = () => { if (!running && visible && !document.hidden) { running = true; last = performance.now(); raf = requestAnimationFrame(frame); } };
  const stop = () => { running = false; cancelAnimationFrame(raf); };

  new IntersectionObserver((entries) => {
    visible = entries[0].isIntersecting;
    visible ? start() : stop();
  }, { rootMargin: '100px' }).observe(root);
  document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
  window.addEventListener('elg:scene-stop', () => { stop(); renderer.dispose(); });

  canvas.addEventListener('webglcontextlost', (e) => { e.preventDefault(); stop(); onFail?.(); });

  if (captureMatch) {
    // Deterministic still frame for generating the static poster images.
    computeAnchors();
    let tt = 0;
    for (let i = 0; i < 90; i++) { tt += 1 / 30; shared.uTime.value = tt; applyState(phase(state.p), tt, 1 / 30); }
    renderer.render(scene, camera);
    root.classList.add('is-live');
    document.documentElement.dataset.sceneReady = '1';
    return;
  }

  gsap.to(state, {
    p: 1,
    ease: 'none',
    scrollTrigger: { trigger: root, start: 'top top', end: 'bottom bottom', scrub: 0.6, invalidateOnRefresh: true, onRefresh: computeAnchors },
  });
  document.fonts?.ready.then(() => ScrollTrigger.refresh());

  // first frame, then reveal the canvas over the poster
  applyState(phase(state.p), 0, 0);
  renderer.render(scene, camera);
  requestAnimationFrame(() => root.classList.add('is-live'));
  start();
}

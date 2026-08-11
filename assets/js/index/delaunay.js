"use strict";

import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";
import { RGBELoader } from "three/addons/loaders/RGBELoader.js";

(function () {
  const container = document.querySelector(".delaunay-canvas");
  const statusText = document.querySelector(".delaunay-copy p");

  if (!container) {
    return;
  }

  let camera;
  let scene;
  let renderer;
  let controls;

  init();
  createObject();
  updateStatus("Listo");

  function createObject() {
    const loader = new THREE.TextureLoader();
    const frontTexture = loader.load(
      "/assets/imgWeb/marcasIMG/image-removebg-preview (8).png"
    );
    const backTexture = loader.load(
      "/assets/imgWeb/marcasIMG/image-removebg-preview (6).png"
    );

    frontTexture.colorSpace = THREE.SRGBColorSpace;
    backTexture.colorSpace = THREE.SRGBColorSpace;
    frontTexture.center.set(0.5, 0.5);
    backTexture.center.set(0.5, 0.5);
    frontTexture.rotation = Math.PI / 2;
    backTexture.rotation = Math.PI / 2;

    const radius = 1.2;
    const height = 0.12;
    const radialSegments = 64;

    const geometry = new THREE.CylinderGeometry(
      radius,
      radius,
      height,
      radialSegments
    );

    const edgeMaterial = new THREE.MeshStandardMaterial({
      color: 0x0b1b2b,
      roughness: 0.5,
      metalness: 0.4
    });
    const frontMaterial = new THREE.MeshStandardMaterial({
      map: frontTexture,
      transparent: true,
      roughness: 0.35,
      metalness: 0.2
    });
    const backMaterial = new THREE.MeshStandardMaterial({
      map: backTexture,
      transparent: true,
      roughness: 0.35,
      metalness: 0.2
    });

    const disk = new THREE.Mesh(geometry, [edgeMaterial, frontMaterial, backMaterial]);
    disk.rotation.x = Math.PI / 2;
    disk.rotation.z = Math.PI / 2;
    scene.add(disk);

    const light = new THREE.DirectionalLight(0xffffff, 1.2);
    light.position.set(3, 5, 4);
    scene.add(light);

    const ambient = new THREE.AmbientLight(0xffffff, 0.35);
    scene.add(ambient);
  }

  function init() {
    scene = new THREE.Scene();

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(0, 0, 5);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.autoRotate = true;
    controls.enableDamping = true;
    controls.enablePan = false;
    controls.enableZoom = false;
    controls.minPolarAngle = Math.PI / 2;
    controls.maxPolarAngle = Math.PI / 2;
    controls.target.set(0, 0, 0);

    onResize();
    window.addEventListener("resize", onResize);

    if ("ResizeObserver" in window) {
      const ro = new ResizeObserver(onResize);
      ro.observe(container);
    }

    new RGBELoader().load(
      "https://raw.githubusercontent.com/mrdoob/three.js/dev/examples/textures/equirectangular/quarry_01_1k.hdr",
      (texture) => {
        texture.mapping = THREE.EquirectangularReflectionMapping;
        scene.environment = texture;
      }
    );
  }

  function onResize() {
    const width = container.clientWidth || window.innerWidth;
    const height = container.clientHeight || Math.round(window.innerHeight * 0.6);

    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
  }

  function animate() {
    controls.update();
    renderer.render(scene, camera);
  }

  renderer.setAnimationLoop(animate);

  function updateStatus(text) {
    if (statusText) statusText.textContent = text;
  }
})();

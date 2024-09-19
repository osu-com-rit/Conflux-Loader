import React from 'react';

import * as THREE from 'three';
import { useRef, useState, useMemo, useEffect, Suspense } from 'react';
import { Canvas, useFrame } from '@react-three/fiber';
import { Billboard, Text, TrackballControls } from '@react-three/drei';

// Adapted from the React Three Fiber Word "Spherical word-cloud" example
//   https://docs.pmnd.rs/react-three-fiber/getting-started/examples
//   https://codesandbox.io/p/sandbox/spherical-word-cloud-yup2o

const FEELING_WORDS = [
  "joy", "sadness", "anger", "fear", "surprise", "love", "disgust", "excitement", "anxiety", "contentment",
  "gratitude", "envy", "guilt", "hope", "loneliness", "pride", "happiness", "depression", "frustration",
  "confusion", "regret", "curiosity", "shame", "peace", "affection", "remorse", "euphoria", "sympathy",
  "embarrassment", "boredom", "nostalgia", "awe", "relief", "jealousy", "apathy", "amusement", "disappointment",
  "satisfaction", "doubt", "apprehension", "resentment", "longing", "grief", "rapture", "pity", "anticipation",
  "vulnerability", "overwhelm", "skepticism", "yearning", "serenity", "calmness", "bitterness", "panic",
  "enthusiasm", "optimism", "despair", "delight", "attraction", "melancholy", "disappointment", "hostility",
  "tenderness", "cynicism", "resignation", "arousal", "exasperation", "glee", "desperation", "gratefulness",
  "ecstasy", "antipathy", "worry", "trepidation", "elation", "fondness", "repulsion", "zeal", "detachment",
  "affection", "pleasure", "dread", "guilt", "rage", "insecurity", "sorrow", "eagerness", "disinterest",
  "yearning", "nostalgia", "unease", "ecstasy", "mirth", "reluctance", "melancholy", "lust", "gratitude",
  "anticipation", "dismay"
];

function recordWordClick(word) {
  const selector = '#selected_feelings-tr > .data > span:nth-child(1) > input.x-form-text';
  const el = document.querySelector(selector);
  el.value = `${el.value} ${word}`;
}

function Word({ children, ...props }) {
  const color = new THREE.Color();
  const fontProps = { fontSize: 2.0, letterSpacing: -0.05, lineHeight: 1, 'material-toneMapped': false };
  const ref = useRef();
  const [hovered, setHovered] = useState(false);
  const [clicked, setClicked] = useState(false);

  const over = (e) => (e.stopPropagation(), setHovered(true));
  const out = () => setHovered(false);

  const click = (e) => {
    if (!clicked) {
      setClicked(true);
      recordWordClick(children);
    };
  };

  useEffect(() => {
    // Change the mouse cursor on hover
    if (hovered) document.body.style.cursor = 'pointer';
    return () => (document.body.style.cursor = 'auto');
  }, [hovered]);

  useFrame(({ camera }) => {
    const newColor = clicked ? '#00cc00' : (hovered ? '#0000cc' : 'black');
    ref.current.material.color.lerp(color.set(newColor), 0.1);
  });

  return (
    <Billboard {...props}>
      <Text ref={ref} onPointerOver={over} onPointerOut={out}
            onClick={click} {...fontProps} children={children} />
    </Billboard>
  );
}

function Cloud({ count = 4, radius = 20 }) {
  // Create count x count words with spherical distribution
  const words = useMemo(() => {
    const temp = [];
    const spherical = new THREE.Spherical();
    const phiSpan = Math.PI / (count + 1);
    const thetaSpan = (Math.PI * 2) / count;
    var idx = 0;
    for (let i = 1; i < count + 1; i++) {
      for (let j = 0; j < count; j++) {
        temp.push([
          new THREE.Vector3().setFromSpherical(spherical.set(radius, phiSpan * i, thetaSpan * j)),
          FEELING_WORDS[idx]
        ]);
        idx++;
      }
    }
    return temp;
  }, [count, radius]);
  return words.map(([pos, word], index) => <Word key={index} position={pos} children={word} />);
}

export default function Application() {
  return (
    <Canvas dpr={[1, 2]} camera={{ position: [0, 0, 35], fov: 90 }}>
      <fog attach="fog" args={['#f0f0f0', 0, 80]} />
      <Suspense fallback={null}>
        <group rotation={[10, 10.5, 10]}>
          <Cloud count={8} radius={20} />
        </group>
      </Suspense>
      <TrackballControls />
    </Canvas>
  );
}

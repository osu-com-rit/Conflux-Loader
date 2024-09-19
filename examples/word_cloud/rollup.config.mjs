import nodeResolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import babel from '@rollup/plugin-babel';
import replace from '@rollup/plugin-replace';
import image from '@rollup/plugin-image';
import json from '@rollup/plugin-json';

import nodePolyfills from 'rollup-plugin-polyfill-node';
// import gltf from 'rollup-plugin-gltf';

export default {
   input: 'src/app.js',
   output: {
      file: 'public/bundle.js',
      format: 'iife'
   },
   plugins: [
      nodeResolve({
         extensions: ['.js', '.jsx']
      }),
      babel({
         babelHelpers: 'bundled',
         presets: ['@babel/preset-react'],
         extensions: ['.js', '.jsx']
      }),
      commonjs(),
      replace({
         preventAssignment: false,
         'process.env.NODE_ENV': '"development"'
      }),
      // gltf({
      //    include: 'resources/*.gltf',
      //    inlineAssetLimit: 10 * 1024 * 1024, // 10MB
      //    inline: true,
      // }),
     image(),
     nodePolyfills( /* options */ ),
     json(),
   ]
}

import { defineConfig } from 'vite'
import * as path from 'path'
import vue from '@vitejs/plugin-vue'
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js'

const root = path.resolve(__dirname)

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    cssInjectedByJsPlugin({
      jsAssetsFilterFunction: function customJsAssetsfilterFunction(outputChunk) {
        return outputChunk.fileName == 'app.js'
      },
    }),
  ],
  root: path.resolve(__dirname),
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },
  build: {
    outDir: path.resolve(root, 'dist'),
    cssCodeSplit: false,
    rollupOptions: {
      input: ['src/app.js', 'src/podlove-admin.js', 'src/podcast-stats.js'],
      output: {
        entryFileNames: `[name].js`,
        chunkFileNames: `chunk-[name].js`,
        assetFileNames: `[name].[ext]`,
      },
    },
  },
})

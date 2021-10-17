import { defineConfig } from 'vite'
import * as path from 'path'
import vue from '@vitejs/plugin-vue'

const root = path.resolve(__dirname)

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
      '@store': path.resolve(root, 'js', 'src', 'store'),
      '@components': path.resolve(root, 'js', 'src', 'components'),
      '@types': path.resolve(root, 'js', 'src', 'types'),
      '@sagas': path.resolve(root, 'js', 'src', 'sagas'),
      '@lib': path.resolve(root, 'js', 'src', 'lib'),
    }
  },
  build: {
    lib: {
      entry: path.resolve(root, 'js', 'src', 'client.ts'),
      name: 'PodloveClient',
      formats: ['iife'],
      fileName: () => `client.js`
    },

    outDir: path.resolve(root, 'js', 'dist')
  }
})

import { defineConfig } from 'vite'
import * as path from 'path'
import vue from '@vitejs/plugin-vue'

const root = path.resolve(__dirname)

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
<<<<<<< HEAD
  server: {
    proxy: {
      '/wp-json': 'http://podlove.local'
    }
  },
  root: path.resolve(__dirname, 'js'),
=======
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
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
<<<<<<< HEAD
    outDir: path.resolve(root, 'js', 'dist'),
    rollupOptions: {
      output: {
        entryFileNames: `client.js`,
        chunkFileNames: `chunk-[name].js`,
        assetFileNames: `style.[ext]`
      }
    }
=======
    lib: {
      entry: path.resolve(root, 'js', 'src', 'client.ts'),
      name: 'PodloveClient',
      formats: ['iife'],
      fileName: () => `client.js`
    },

    outDir: path.resolve(root, 'js', 'dist')
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
  }
})

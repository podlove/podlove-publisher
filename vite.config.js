import { defineConfig } from 'vite'
import * as path from 'path'
import vue from '@vitejs/plugin-vue'

const root = path.resolve(__dirname)

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/wp-json': 'http://podlove.local'
    }
  },
  root: path.resolve(__dirname, 'js'),
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
    outDir: path.resolve(root, 'js', 'dist'),
    rollupOptions: {
      output: {
        entryFileNames: `client.js`,
        chunkFileNames: `chunk-[name].js`,
        assetFileNames: `style.[ext]`
      }
    }
  }
})

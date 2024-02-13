import { defineConfig } from 'vite'
import * as path from 'path'
import vue from '@vitejs/plugin-vue'

const root = path.resolve(__dirname)

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/wp-json': {
        target: process.env.WORDPRESS_URL || 'http://podlove.local',
        changeOrigin: true,
        secure: false,

      }
    }
  },
  root: path.resolve(__dirname),
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
      '@store': path.resolve(root, 'src', 'store'),
      '@components': path.resolve(root, 'src', 'components'),
      '@types': path.resolve(root, 'src', 'types'),
      '@sagas': path.resolve(root, 'src', 'sagas'),
      '@lib': path.resolve(root, 'src', 'lib'),
    }
  },
  build: {
    outDir: path.resolve(root, 'dist'),
    cssCodeSplit: false,
    rollupOptions: {
      output: {
        entryFileNames: `client.js`,
        chunkFileNames: `chunk-[name].js`,
        assetFileNames: `[name].[ext]`
      }
    }
  }
})

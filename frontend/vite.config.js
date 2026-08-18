import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

const apiBase = process.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

export default defineConfig({
  server: {
    host: '0.0.0.0',
    port: Number(process.env.PORT || 5173),
    proxy: {
      '/api': apiBase,
    },
  },
  preview: {
    host: '0.0.0.0',
    port: Number(process.env.PORT || 4173),
  },
  plugins: [
    vue(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg'],
      manifest: {
        name: 'Smart Learning Down Syndrome',
        short_name: 'Smart Learning',
        description: 'Aplikasi pembelajaran visual untuk pendamping siswa.',
        theme_color: '#0f766e',
        background_color: '#f8fafc',
        display: 'standalone',
        icons: [],
      },
      workbox: {
        runtimeCaching: [
          {
            urlPattern: /^https?:\/\/.*\/api\//,
            handler: 'NetworkFirst',
            options: { cacheName: 'api-cache', networkTimeoutSeconds: 5 },
          },
        ],
      },
    }),
  ],
})

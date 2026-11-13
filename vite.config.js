// Limitar threads de Rayon/Rolldown para garantizar compilación estable y libre de errores de memoria en sistemas de 8GB
process.env.RAYON_NUM_THREADS = process.env.RAYON_NUM_THREADS || '1'

import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

function devApiPlugin() {
  return {
    name: 'dev-api-middleware',
    configureServer(server) {
      server.middlewares.use(async (req, res, next) => {
        const url = req.url?.split('?')[0]

        if (url === '/api/sync-gaming') {
          try {
            const { scrapeAndSyncGamingCatalog } = await import('./scripts/sync-gaming-catalog.js')
            const result = await scrapeAndSyncGamingCatalog()
            res.setHeader('Content-Type', 'application/json')
            res.end(JSON.stringify(result))
          } catch (e) {
            res.statusCode = 500
            res.setHeader('Content-Type', 'application/json')
            res.end(JSON.stringify({ error: e.message }))
          }
          return
        }

        if (url === '/api/sync-revende' || url === '/api/sync-tua') {
          try {
            const { syncPhysicalCatalog } = await import('./scripts/sync-catalog.js')
            const result = await syncPhysicalCatalog()
            res.setHeader('Content-Type', 'application/json')
            res.end(JSON.stringify(result))
          } catch (e) {
            res.statusCode = 500
            res.setHeader('Content-Type', 'application/json')
            res.end(JSON.stringify({ error: e.message }))
          }
          return
        }

        if (url === '/api/notify-order') {
          try {
            let body = ''
            req.on('data', chunk => { body += chunk })
            req.on('end', async () => {
              try {
                req.body = body ? JSON.parse(body) : {}
                const { default: handler } = await import('./api/notify-order.js')
                const fakeRes = {
                  setHeader(k, v) { res.setHeader(k, v) },
                  status(code) { res.statusCode = code; return this },
                  json(data) {
                    res.setHeader('Content-Type', 'application/json')
                    res.end(JSON.stringify(data))
                  },
                  end(data) { res.end(data) }
                }
                await handler(req, fakeRes)
              } catch (e) {
                res.statusCode = 500
                res.setHeader('Content-Type', 'application/json')
                res.end(JSON.stringify({ error: e.message }))
              }
            })
          } catch (e) {
            res.statusCode = 500
            res.setHeader('Content-Type', 'application/json')
            res.end(JSON.stringify({ error: e.message }))
          }
          return
        }

        next()
      })
    }
  }
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    devApiPlugin(),
    VitePWA({
      registerType: 'autoUpdate',
      devOptions: {
        enabled: false
      },
      workbox: {
        cleanupOutdatedCaches: true,
        clientsClaim: true,
        skipWaiting: true,
        maximumFileSizeToCacheInBytes: 3500000,
        globPatterns: ['**/*.{js,css,html,ico,png,svg,webmanifest}'],
        globIgnores: ['**/catalog-gaming*.js'],
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname.includes('catalog-gaming'),
            handler: 'CacheFirst',
            options: {
              cacheName: 'streamepro-gaming-catalog',
              expiration: {
                maxEntries: 2,
                maxAgeSeconds: 60 * 60 * 24 * 7
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      },
      manifest: {
        name: 'StreamePro | Streaming & Tecnología Oficial',
        short_name: 'StreamePro',
        description: 'Plataforma oficial de Cuentas de Streaming y Tecnología en Argentina. Perfiles privados, PIN exclusivo y entrega de 10 min a 1 hora con comprobante.',
        theme_color: '#020617',
        background_color: '#020617',
        display: 'standalone',
        orientation: 'portrait-primary',
        start_url: '/',
        scope: '/',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'pwa-maskable-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable'
          },
          {
            src: 'favicon.svg',
            sizes: 'any',
            type: 'image/svg+xml'
          }
        ]
      }
    })
  ],
  build: {
    target: 'esnext',
    chunkSizeWarningLimit: 2000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('gamingProducts.json')) {
            return 'catalog-gaming'
          }
          if (id.includes('physicalProducts.json')) {
            return 'catalog-physical'
          }
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) {
              return 'vendor-vue'
            }
            if (id.includes('firebase')) {
              return 'vendor-firebase'
            }
          }
        }
      }
    }
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  }
})

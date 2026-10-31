import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// App — deploys to mimmycare.com/app/ so base must match the folder (release.json also passes --base=/app/).
// In development /api goes to the local PHP API on its fixed port 8041
// (php -S 127.0.0.1:8041 api/index.php, settings in api/config.php — see docs/DEPLOYMENT.md).
export default defineConfig({
  base: '/app/',
  plugins: [react(), tailwindcss()],
  server: {
    port: 5092,
    strictPort: true,
    proxy: {
      '/api': { target: 'http://127.0.0.1:8041', changeOrigin: false },
    },
  },
  preview: {
    port: 5092,
    strictPort: true,
  },
})

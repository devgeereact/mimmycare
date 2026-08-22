import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// App — client NN 04. Deploys to mimmycare.com/app/ so base must match subdir.
export default defineConfig({
  base: '/app/',
  plugins: [react(), tailwindcss()],
  server: {
    port: 5092,
    strictPort: true,
  },
  preview: {
    port: 5092,
    strictPort: true,
  },
})

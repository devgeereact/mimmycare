import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// Landing — client NN 04. Port pinned per workspace convention.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5091,
    strictPort: true,
  },
  preview: {
    port: 5091,
    strictPort: true,
  },
})

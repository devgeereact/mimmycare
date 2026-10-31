import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { ClerkProvider } from '@clerk/clerk-react'
import './index.css'
import App from './App.tsx'
import NotConfigured from './components/NotConfigured.tsx'

// Public value (it is in every page the browser loads). Live: release.json's build. Local: app/.env.local.
const CLERK_KEY = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY ?? ''

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    {CLERK_KEY ? (
      <ClerkProvider publishableKey={CLERK_KEY} afterSignOutUrl={import.meta.env.BASE_URL}>
        <App />
      </ClerkProvider>
    ) : (
      <NotConfigured />
    )}
  </StrictMode>,
)

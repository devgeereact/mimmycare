/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Clerk publishable key (public). Live: set in release.json's build. Local: app/.env.local (pk_test_...). */
  readonly VITE_CLERK_PUBLISHABLE_KEY?: string
  /** Optional API base; defaults to same-origin /api. */
  readonly VITE_API_BASE?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

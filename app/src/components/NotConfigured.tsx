import Shell from './Shell.tsx'

/** Shown when the build has no VITE_CLERK_PUBLISHABLE_KEY (a local run without app/.env.local). */
export default function NotConfigured() {
  return (
    <Shell>
      <section className="w-full max-w-md rounded-2xl border border-white/10 bg-white/5 p-8 text-center" role="alert">
        <h1 className="text-xl font-semibold">Sign-in is not set up in this build</h1>
        <p className="mt-3 text-sm text-slate-300">
          Set VITE_CLERK_PUBLISHABLE_KEY (copy app/.env.example to app/.env.local), then restart the dev server.
        </p>
      </section>
    </Shell>
  )
}

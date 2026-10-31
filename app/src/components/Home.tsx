import { useCallback, useEffect, useState } from 'react'
import { useAuth, useClerk, useUser } from '@clerk/clerk-react'
import { displayName, fetchMe, type ApiResult, type MeUser } from '../lib/api'

type State = { kind: 'loading' } | ApiResult<MeUser>

/** Signed-in home: asks the API who we are (GET /api/me creates the user on first sign-in) and greets them. */
export default function Home() {
  const { getToken } = useAuth()
  const { user: clerkUser } = useUser()
  const { signOut } = useClerk()
  const [state, setState] = useState<State>({ kind: 'loading' })

  const load = useCallback(async () => {
    setState({ kind: 'loading' })
    setState(await fetchMe(() => getToken()))
  }, [getToken])

  useEffect(() => {
    void load()
  }, [load])

  const card = 'w-full max-w-md rounded-2xl border border-white/10 bg-white/5 p-8 text-center shadow-xl'
  const button =
    'rounded-full px-5 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950'
  const signOutButton = (
    <button type="button" onClick={() => void signOut()} className={`${button} border border-white/20 text-white hover:bg-white/10`}>
      Sign out
    </button>
  )

  if (state.kind === 'loading') {
    return (
      <section className={card} aria-busy="true">
        <p role="status" className="text-slate-400">Loading your account…</p>
      </section>
    )
  }

  if (state.kind === 'ok') {
    return (
      <section className={card}>
        <h1 className="text-3xl font-semibold tracking-tight">Hello, {displayName(state.data, clerkUser?.fullName)}</h1>
        {state.data.email && <p className="mt-2 text-sm text-slate-400">Signed in as {state.data.email}</p>}
        <p className="mt-6 text-slate-300">Your MimmyCare account is ready. More is coming soon.</p>
        <div className="mt-8 flex justify-center">{signOutButton}</div>
      </section>
    )
  }

  // signed-out (the API refused the session), offline, or an API error
  const message =
    state.kind === 'signed-out'
      ? 'Your session could not be confirmed. Sign out and sign in again.'
      : state.message
  return (
    <section className={card} role="alert">
      <h1 className="text-xl font-semibold">We could not load your account</h1>
      <p className="mt-3 text-sm text-slate-300">{message}</p>
      {state.kind === 'error' && state.ref && <p className="mt-2 text-xs text-slate-500">Reference: {state.ref}</p>}
      <div className="mt-8 flex justify-center gap-3">
        <button type="button" onClick={() => void load()} className={`${button} bg-white text-slate-900 hover:bg-white/90`}>
          Try again
        </button>
        {signOutButton}
      </div>
    </section>
  )
}

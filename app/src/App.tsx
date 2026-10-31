import { ClerkLoaded, ClerkLoading, SignedIn, SignedOut } from '@clerk/clerk-react'
import Home from './components/Home.tsx'
import Shell from './components/Shell.tsx'
import SignInView from './components/SignInView.tsx'

/** Signed out: Clerk sign-in (email code, Google). Signed in: the home screen, backed by GET /api/me. */
export default function App() {
  return (
    <Shell>
      <ClerkLoading>
        <p role="status" className="text-slate-400">Loading…</p>
      </ClerkLoading>
      <ClerkLoaded>
        <SignedOut>
          <SignInView />
        </SignedOut>
        <SignedIn>
          <Home />
        </SignedIn>
      </ClerkLoaded>
    </Shell>
  )
}

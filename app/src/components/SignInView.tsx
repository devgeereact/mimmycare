import { SignIn } from '@clerk/clerk-react'

/**
 * Signed-out view. Clerk's own sign-in. Methods are set in the Clerk dashboard (email code now; a Google
 * button appears here by itself once Google is enabled for the instance).
 * Hash routing keeps Clerk's steps inside /app/ without server routes; withSignUp lets a first-time
 * visitor create an account in the same flow (if sign-ups are enabled for the instance).
 */
export default function SignInView() {
  return (
    <div className="flex w-full max-w-md flex-col items-center gap-6">
      <div className="text-center">
        <h1 className="text-2xl font-semibold tracking-tight">Sign in to MimmyCare</h1>
        <p className="mt-2 text-sm text-slate-400">Enter your email address and we will send you a one-time code.</p>
      </div>
      <SignIn routing="hash" withSignUp />
    </div>
  )
}

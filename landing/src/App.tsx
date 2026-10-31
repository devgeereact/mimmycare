import { useState } from 'react'
import QuoteRotator from './components/QuoteRotator'
import ContactCard from './components/ContactCard'

// Curated natural, inviting hero photos. One is chosen per day so the landing
// feels fresh daily. Self-hosted files win; the remote URL is a safety net.
// Files live in landing/public/assets/hero-1.jpg … hero-7.jpg.
const HERO_IDS = [
  '1507525428034-b723cf961d3e', // turquoise beach + palms
  '1505118380757-91f5f5632de0', // palm beach shoreline
  '1506929562872-bb421503ef21', // clear tropical water
  '1501785888041-af3ef285b470', // lake + green mountains
  '1441974231531-c6227db76b6e', // sun through forest
  '1470770841072-f978cf4d019e', // alpine lake, pines
  '1519046904884-53103b34b206', // sunny wide beach
]

// Rotates at local midnight — deterministic, no storage needed.
function heroForToday() {
  const now = new Date()
  const dayIndex =
    Math.floor((now.getTime() - now.getTimezoneOffset() * 60000) / 86400000) % HERO_IDS.length
  const n = dayIndex + 1
  const remote = `https://images.unsplash.com/photo-${HERO_IDS[dayIndex]}?auto=format&fit=crop&w=3200&q=82`
  return `url('/assets/hero-${n}.jpg'), url('${remote}')`
}

export default function App() {
  const [contactOpen, setContactOpen] = useState(false)

  return (
    <main className="hero-fallback relative min-h-screen w-full overflow-hidden text-white">
      {/* Hero media + overlays — background rotates daily */}
      <div
        className="animate-kenburns absolute inset-0 bg-cover bg-center"
        style={{ backgroundImage: heroForToday() }}
        aria-hidden="true"
      />
      {/* Readability overlays: flat scrim + top→bottom gradient + center vignette */}
      <div className="absolute inset-0 bg-black/40" aria-hidden="true" />
      <div
        className="absolute inset-0 bg-gradient-to-b from-black/50 via-black/35 to-black/70"
        aria-hidden="true"
      />
      <div
        className="absolute inset-0"
        style={{ background: 'radial-gradient(75% 60% at 50% 42%, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0) 70%)' }}
        aria-hidden="true"
      />

      {/* Content */}
      <section className="relative z-10 mx-auto flex min-h-[100svh] max-w-4xl flex-col items-center justify-center px-5 py-16 text-center sm:px-6">
        <img
          src="/assets/logo-white.svg"
          alt="MimmyCare"
          className="fadeup h-16 w-auto sm:h-20 md:h-24"
          style={{ animationDelay: '.05s' }}
        />

        <p
          className="fadeup mt-6 text-[0.65rem] font-semibold uppercase tracking-[0.35em] text-white/80 sm:text-xs sm:tracking-[0.4em]"
          style={{ animationDelay: '.15s' }}
        >
          Launching Soon
        </p>

        {/* Rotating motivational quote — the headline, changes every 15 minutes */}
        <div className="fadeup mt-7" style={{ animationDelay: '.25s' }}>
          <QuoteRotator />
        </div>

        <span
          className="fadeup mt-9 block h-px w-28 bg-white/50"
          style={{ animationDelay: '.4s' }}
          aria-hidden="true"
        />

        {/* Charge of the day — the brand line */}
        <div className="fadeup mt-8" style={{ animationDelay: '.5s' }}>
          <p className="text-xs font-semibold uppercase tracking-[0.32em] text-white/55">
            Charge of the day
          </p>
          <p className="mt-3 font-serif text-xl italic text-white/90 sm:text-2xl">
            Everything you love, in one place.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setContactOpen(true)}
          className="fadeup group mt-10 inline-flex items-center gap-3 rounded-full bg-white px-7 py-4 text-sm font-bold tracking-wide text-slate-900 shadow-lg transition hover:bg-white/90 hover:shadow-xl active:scale-[.98]"
          style={{ animationDelay: '.6s' }}
        >
          <span className="grid h-8 w-8 place-items-center rounded-full bg-slate-900 text-white transition group-hover:scale-105">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="5" width="18" height="14" rx="2" />
              <path d="m3 7 9 6 9-6" />
            </svg>
          </span>
          CONTACT US
        </button>
      </section>

      {/* Footer */}
      <footer className="absolute inset-x-0 bottom-0 z-10 flex flex-col items-center gap-1 px-6 pb-6 text-center text-xs text-white/60 sm:flex-row sm:justify-between">
        <span>© {new Date().getFullYear()} MimmyCare. All rights reserved.</span>
        <span className="flex items-center gap-4">
          <a href="mailto:info@mimmycare.com" className="transition hover:text-white">
            info@mimmycare.com
          </a>
          <a href="/app/" className="font-semibold text-white/80 transition hover:text-white">
            Sign in
          </a>
        </span>
      </footer>

      <ContactCard open={contactOpen} onClose={() => setContactOpen(false)} />
    </main>
  )
}

import type { ReactNode } from 'react'

/** Page frame shared by every view: brand header, centred content, footer link back to the site. */
export default function Shell({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col bg-slate-950 text-slate-100">
      <header className="px-5 py-5 sm:px-8">
        <a href="/" className="inline-flex items-center rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
          <img src={`${import.meta.env.BASE_URL}logo-white.svg`} alt="MimmyCare" className="h-8 w-auto" />
        </a>
      </header>
      <main className="flex flex-1 items-center justify-center px-5 py-10">{children}</main>
      <footer className="px-5 pb-6 text-center text-xs text-slate-500">
        © {new Date().getFullYear()} MimmyCare ·{' '}
        <a href="mailto:info@mimmycare.com" className="underline-offset-2 hover:text-slate-300 hover:underline">
          info@mimmycare.com
        </a>
      </footer>
    </div>
  )
}

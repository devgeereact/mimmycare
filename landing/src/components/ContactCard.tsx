import { useEffect, useRef, useState } from 'react'

const EMAIL = 'info@mimmycare.com'

type Props = { open: boolean; onClose: () => void }

export default function ContactCard({ open, onClose }: Props) {
  const [name, setName] = useState('')
  const [from, setFrom] = useState('')
  const [message, setMessage] = useState('')
  const firstFieldRef = useRef<HTMLInputElement>(null)
  const dialogRef = useRef<HTMLDivElement>(null)

  // Close on Escape, keep Tab / Shift+Tab inside the dialog, lock body scroll,
  // focus first field when opened and return focus to the opener on close.
  useEffect(() => {
    if (!open) return
    const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose()
        return
      }
      if (e.key !== 'Tab' || !dialogRef.current) return
      const items = Array.from(
        dialogRef.current.querySelectorAll<HTMLElement>(
          'button, [href], input, textarea, select, [tabindex]:not([tabindex="-1"])',
        ),
      ).filter((el) => !el.hasAttribute('disabled'))
      if (items.length === 0) return
      const first = items[0]
      const last = items[items.length - 1]
      const active = document.activeElement
      const inside = active instanceof Node && dialogRef.current.contains(active)
      if (e.shiftKey && (active === first || !inside)) {
        e.preventDefault()
        last.focus()
      } else if (!e.shiftKey && (active === last || !inside)) {
        e.preventDefault()
        first.focus()
      }
    }
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    firstFieldRef.current?.focus()
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
      if (opener?.isConnected) opener.focus()
    }
  }, [open, onClose])

  if (!open) return null

  // No backend wired yet (API is deployed manually) — compose a mailto so the
  // visitor's mail client handles delivery to info@mimmycare.com.
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const subject = encodeURIComponent(`MimmyCare enquiry${name ? ` — ${name}` : ''}`)
    const body = encodeURIComponent(
      `${message}\n\n—\nFrom: ${name || 'Website visitor'}${from ? ` <${from}>` : ''}`,
    )
    window.location.href = `mailto:${EMAIL}?subject=${subject}&body=${body}`
  }

  return (
    <div
      ref={dialogRef}
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="contact-title"
    >
      {/* Backdrop */}
      <button
        type="button"
        aria-label="Close contact form"
        onClick={onClose}
        className="absolute inset-0 cursor-default bg-black/60 backdrop-blur-sm"
      />

      {/* Card */}
      <div className="relative w-full max-w-md overflow-hidden rounded-2xl border border-white/15 bg-white/95 shadow-2xl backdrop-blur-xl animate-[cardin_.35s_ease-out]">
        <div className="p-7 sm:p-8">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">
                Get in touch
              </p>
              <h2 id="contact-title" className="mt-2 font-serif text-2xl text-slate-900">
                Contact us
              </h2>
            </div>
            <button
              type="button"
              onClick={onClose}
              aria-label="Close"
              className="-mr-2 -mt-1 rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4">
            <input
              ref={firstFieldRef}
              type="text"
              aria-label="Your name"
              placeholder="Your name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
            <input
              type="email"
              required
              aria-label="Your email"
              placeholder="Your email"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
            <textarea
              aria-label="Your message"
              placeholder="How can we help?"
              rows={4}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="w-full resize-none rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
            <button
              type="submit"
              className="w-full rounded-full bg-slate-900 px-6 py-3.5 text-sm font-semibold tracking-wide text-white transition hover:bg-slate-800 active:scale-[.99]"
            >
              Send message
            </button>
          </form>

          <p className="mt-5 text-center text-xs text-slate-500">
            Or email us directly at{' '}
            <a href={`mailto:${EMAIL}`} className="font-medium text-slate-900 underline underline-offset-2">
              {EMAIL}
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}

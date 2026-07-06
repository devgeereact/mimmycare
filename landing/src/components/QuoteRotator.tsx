import { useEffect, useState } from 'react'
import { quotes } from '../data/quotes'

const FIFTEEN_MIN = 15 * 60 * 1000

// Which quote is "current" for a given moment — deterministic so a reload
// mid-window keeps showing the same quote instead of jumping to the start.
function indexForNow() {
  return Math.floor(Date.now() / FIFTEEN_MIN) % quotes.length
}

export default function QuoteRotator() {
  const [index, setIndex] = useState(indexForNow)
  const [visible, setVisible] = useState(true)

  useEffect(() => {
    // Align the first tick to the next 15-min boundary, then tick every 15 min.
    const msToBoundary = FIFTEEN_MIN - (Date.now() % FIFTEEN_MIN)

    let interval: ReturnType<typeof setInterval>

    const advance = () => {
      setVisible(false) // fade out
      window.setTimeout(() => {
        setIndex(indexForNow())
        setVisible(true) // fade back in with the new quote
      }, 600)
    }

    const boundary = window.setTimeout(() => {
      advance()
      interval = setInterval(advance, FIFTEEN_MIN)
    }, msToBoundary)

    return () => {
      window.clearTimeout(boundary)
      if (interval) clearInterval(interval)
    }
  }, [])

  const quote = quotes[index]

  return (
    <figure
      className={`mx-auto max-w-3xl transition-opacity duration-[600ms] ease-out ${
        visible ? 'opacity-100' : 'opacity-0'
      }`}
      aria-live="polite"
    >
      <h1 className="font-serif text-3xl leading-[1.15] tracking-tight text-white [text-wrap:balance] sm:text-4xl sm:leading-[1.12] md:text-6xl">
        “{quote.text}”
      </h1>
      <figcaption className="mt-5 text-xs font-semibold uppercase tracking-[0.28em] text-white/60">
        — {quote.author}
      </figcaption>
    </figure>
  )
}

// Clean quotes rotated on the hero every 15 minutes (see QuoteRotator).
// Mixed motivational / encouragement / inspirational — kept concise because
// each one is displayed as the large serif headline.
export type Category = 'motivational' | 'encouragement' | 'inspirational'
export type Quote = { text: string; author: string; category: Category }

export const quotes: Quote[] = [
  // — Motivational —
  { text: 'The secret of getting ahead is getting started.', author: 'Mark Twain', category: 'motivational' },
  { text: 'Well done is better than well said.', author: 'Benjamin Franklin', category: 'motivational' },
  { text: 'Little by little, one travels far.', author: 'J.R.R. Tolkien', category: 'motivational' },
  { text: 'Dream big and dare to fail.', author: 'Norman Vaughan', category: 'motivational' },
  { text: 'Quality is not an act, it is a habit.', author: 'Aristotle', category: 'motivational' },
  { text: 'The way to get started is to quit talking and begin doing.', author: 'Walt Disney', category: 'motivational' },
  { text: 'What we think, we become.', author: 'Buddha', category: 'motivational' },
  { text: 'Act as if what you do makes a difference. It does.', author: 'William James', category: 'motivational' },
  { text: 'Do small things with great love.', author: 'Mother Teresa', category: 'motivational' },
  { text: 'Simplicity is the ultimate sophistication.', author: 'Leonardo da Vinci', category: 'motivational' },
  { text: 'Take care of your body. It’s the only place you have to live.', author: 'Jim Rohn', category: 'motivational' },
  { text: 'Discipline is choosing between what you want now and what you want most.', author: 'Abraham Lincoln', category: 'motivational' },

  // — Encouragement —
  { text: 'You are braver than you believe, and stronger than you seem.', author: 'A.A. Milne', category: 'encouragement' },
  { text: 'Almost everything will work again if you unplug it for a few minutes — including you.', author: 'Anne Lamott', category: 'encouragement' },
  { text: 'Believe you can and you’re halfway there.', author: 'Theodore Roosevelt', category: 'encouragement' },
  { text: 'You are enough, just as you are.', author: 'Meghan Markle', category: 'encouragement' },
  { text: 'Every day may not be good, but there is something good in every day.', author: 'Alice Morse Earle', category: 'encouragement' },
  { text: 'Comfort is not a luxury. It is the beginning of everything good.', author: 'Unknown', category: 'encouragement' },
  { text: 'Start where you are. Use what you have. Do what you can.', author: 'Arthur Ashe', category: 'encouragement' },
  { text: 'Fall seven times, stand up eight.', author: 'Japanese Proverb', category: 'encouragement' },
  { text: 'Nothing is impossible. The word itself says “I’m possible.”', author: 'Audrey Hepburn', category: 'encouragement' },
  { text: 'You don’t have to be great to start, but you have to start to be great.', author: 'Zig Ziglar', category: 'encouragement' },
  { text: 'Keep going. Everything you need will come to you at the perfect time.', author: 'Unknown', category: 'encouragement' },
  { text: 'A little progress each day adds up to big results.', author: 'Unknown', category: 'encouragement' },

  // — Inspirational —
  { text: 'Wherever you go, go with all your heart.', author: 'Confucius', category: 'inspirational' },
  { text: 'The quieter you become, the more you are able to hear.', author: 'Rumi', category: 'inspirational' },
  { text: 'Find joy in the ordinary.', author: 'Max Lucado', category: 'inspirational' },
  { text: 'The little things? The little moments? They aren’t little.', author: 'Jon Kabat-Zinn', category: 'inspirational' },
  { text: 'What you seek is seeking you.', author: 'Rumi', category: 'inspirational' },
  { text: 'Life is a journey, not a race — slow down and enjoy it.', author: 'Unknown', category: 'inspirational' },
  { text: 'Everything you need is already within reach.', author: 'Unknown', category: 'inspirational' },
  { text: 'Happiness is not something ready made. It comes from your own actions.', author: 'Dalai Lama', category: 'inspirational' },
  { text: 'In the middle of difficulty lies opportunity.', author: 'Albert Einstein', category: 'inspirational' },
  { text: 'The best way to predict the future is to create it.', author: 'Peter Drucker', category: 'inspirational' },
  { text: 'Stay close to anything that makes you glad you are alive.', author: 'Hafez', category: 'inspirational' },
  { text: 'Let your light shine, one bright moment at a time.', author: 'Unknown', category: 'inspirational' },
]

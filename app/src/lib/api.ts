/**
 * MimmyCare API client. Same-origin `/api` live; in development Vite proxies `/api` to the local PHP API
 * (vite.config.ts) unless VITE_API_BASE points somewhere else.
 *
 * Every call takes a token getter (Clerk's `getToken`), so a fresh short-lived session token goes with
 * each request. Results are typed unions: callers handle every state, nothing throws past here.
 */
export const API_BASE = ((import.meta.env.VITE_API_BASE as string | undefined) || "/api").replace(/\/+$/, "")

export interface MeUser {
  id: number
  clerk_user_id: string
  email: string | null
  name: string | null
  created_at: string
  last_seen_at: string | null
}

export type ApiResult<T> =
  | { kind: "ok"; data: T }
  | { kind: "signed-out" }
  | { kind: "error"; status: number; code: string; message: string; ref?: string }
  | { kind: "offline"; message: string }

type TokenGetter = () => Promise<string | null>

/** Reads the kit error shape {"error":{"code","message","ref?"}}; anything else becomes a generic error. */
export function parseApiError(status: number, body: unknown): Extract<ApiResult<never>, { kind: "error" }> {
  const err = (body as { error?: unknown } | null)?.error
  if (err && typeof err === "object") {
    const e = err as Record<string, unknown>
    if (typeof e.code === "string" && typeof e.message === "string") {
      return { kind: "error", status, code: e.code, message: e.message, ...(typeof e.ref === "string" ? { ref: e.ref } : {}) }
    }
  }
  return { kind: "error", status, code: "unexpected_response", message: "The server gave an unexpected answer. Try again shortly." }
}

export function isMeUser(v: unknown): v is MeUser {
  if (!v || typeof v !== "object") return false
  const u = v as Record<string, unknown>
  return (
    typeof u.id === "number" &&
    typeof u.clerk_user_id === "string" &&
    (u.email === null || typeof u.email === "string") &&
    (u.name === null || typeof u.name === "string") &&
    typeof u.created_at === "string" &&
    (u.last_seen_at === null || typeof u.last_seen_at === "string")
  )
}

async function getJson(path: string, getToken: TokenGetter, fetchImpl: typeof fetch): Promise<ApiResult<unknown>> {
  let token: string | null
  try {
    token = await getToken()
  } catch {
    return { kind: "offline", message: "Could not reach the sign-in service. Check your connection." }
  }
  if (!token) return { kind: "signed-out" }

  let res: Response
  try {
    res = await fetchImpl(`${API_BASE}${path}`, { headers: { Accept: "application/json", Authorization: `Bearer ${token}` } })
  } catch {
    return { kind: "offline", message: "Could not reach MimmyCare. Check your connection and try again." }
  }
  let body: unknown = null
  try {
    body = await res.json()
  } catch {
    body = null
  }
  if (res.status === 401) return { kind: "signed-out" }
  if (!res.ok) return parseApiError(res.status, body)
  return { kind: "ok", data: body }
}

/** GET /api/me: creates the user on first sign-in, returns the stored record. */
export async function fetchMe(getToken: TokenGetter, fetchImpl: typeof fetch = fetch): Promise<ApiResult<MeUser>> {
  const r = await getJson("/me", getToken, fetchImpl)
  if (r.kind !== "ok") return r
  const user = (r.data as { user?: unknown } | null)?.user
  return isMeUser(user) ? { kind: "ok", data: user } : parseApiError(200, null)
}

/** The name to greet: stored name, else Clerk's profile name, else the email's local part. */
export function displayName(user: Pick<MeUser, "name" | "email"> | null, clerkName?: string | null): string {
  const n = user?.name?.trim() || clerkName?.trim()
  if (n) return n
  const email = user?.email ?? ""
  return email.includes("@") ? email.slice(0, email.indexOf("@")) : "there"
}

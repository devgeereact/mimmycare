import { describe, expect, it } from "vitest"

import { displayName, fetchMe, isMeUser, parseApiError } from "./api"

const user = { id: 1, clerk_user_id: "user_abc", email: "mimmy@example.com", name: "Mimmy", created_at: "2026-10-30 12:00:00", last_seen_at: null }

function fakeFetch(status: number, body: unknown): typeof fetch {
  return (async (_url: RequestInfo | URL, init?: RequestInit) => {
    const headers = new Headers(init?.headers)
    if (headers.get("Authorization") !== "Bearer tok") throw new Error("no bearer token sent")
    return new Response(body === undefined ? "not json" : JSON.stringify(body), { status })
  }) as typeof fetch
}

const token = async () => "tok"

describe("fetchMe", () => {
  it("returns the user and sends the Clerk token as a bearer", async () => {
    expect(await fetchMe(token, fakeFetch(200, { user }))).toEqual({ kind: "ok", data: user })
  })
  it("is signed-out without a token, without calling the API", async () => {
    let called = false
    const f = (async () => { called = true; return new Response("{}") }) as typeof fetch
    expect(await fetchMe(async () => null, f)).toEqual({ kind: "signed-out" })
    expect(called).toBe(false)
  })
  it("treats 401 as signed-out", async () => {
    expect(await fetchMe(token, fakeFetch(401, { error: { code: "unauthorized", message: "Sign in to continue." } }))).toEqual({ kind: "signed-out" })
  })
  it("passes the API error code, message and ref through", async () => {
    const r = await fetchMe(token, fakeFetch(500, { error: { code: "server_error", message: "Something went wrong on our side.", ref: "abc123" } }))
    expect(r).toEqual({ kind: "error", status: 500, code: "server_error", message: "Something went wrong on our side.", ref: "abc123" })
  })
  it("reports a non-JSON answer as an unexpected response", async () => {
    const r = await fetchMe(token, fakeFetch(502, undefined))
    expect(r.kind === "error" && r.code).toBe("unexpected_response")
  })
  it("rejects a 200 without a valid user", async () => {
    const r = await fetchMe(token, fakeFetch(200, { user: { id: "1" } }))
    expect(r.kind === "error" && r.code).toBe("unexpected_response")
  })
  it("is offline when fetch throws", async () => {
    const f = (async () => { throw new TypeError("Failed to fetch") }) as typeof fetch
    expect((await fetchMe(token, f)).kind).toBe("offline")
  })
  it("is offline when the token cannot be fetched", async () => {
    expect((await fetchMe(async () => { throw new Error("clerk down") }, fakeFetch(200, { user }))).kind).toBe("offline")
  })
})

describe("helpers", () => {
  it("parseApiError falls back for an unknown shape", () => {
    expect(parseApiError(400, { error: "old style" }).code).toBe("unexpected_response")
  })
  it("isMeUser checks every field", () => {
    expect(isMeUser(user)).toBe(true)
    expect(isMeUser({ ...user, email: 5 })).toBe(false)
    expect(isMeUser(null)).toBe(false)
  })
  it("displayName prefers the stored name, then Clerk's, then the email", () => {
    expect(displayName(user)).toBe("Mimmy")
    expect(displayName({ name: null, email: "ada@example.com" }, "Ada Lovelace")).toBe("Ada Lovelace")
    expect(displayName({ name: "  ", email: "ada@example.com" })).toBe("ada")
    expect(displayName(null)).toBe("there")
  })
})

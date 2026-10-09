# Current Task Contract

## Outcome

Audit MimmyCare against the applicable GEE OS 0.9.0 standards and apply safe, reversible local improvements, preserving its purpose, working behaviour, data and existing work.

## Mode

Existing Application. Workflow: Existing Application, with the Change Safety protocol for each edit.

## Scope

- In scope: baseline inspection and checks, findings ranked P0 to P4, small local fixes to code, documentation and agent instructions, reports under `../reports/mimmycare/`.
- Out of scope: deletions, dependency or runtime changes, database migrations, authentication, payment or business-rule changes, deployment, commits and pushes, production or external mutations.

## Authority

- Research: yes, read-only.
- Create planning artefacts: yes, under `../reports/mimmycare/`.
- Edit local files: safe, reversible changes only.
- Use external services: no.
- Change production: no.
- MCP reads: documentation lookups only.
- MCP mutations: no.

## Risk and dependencies

- Risk level: production. Pushing `main` deploys the landing page by FTPS through `.github/workflows/deploy.yml`.
- Affected areas: `landing/` launch page, `app/` scaffold, deploy workflow, project documentation. The PHP API source is not in this repository.
- Required decisions: listed in `../reports/mimmycare/AUDIT-AND-POLISH-2026-10-08.md`.

## Evidence

- Required checks: `npm run build` (runs `tsc -b` per workspace), TypeScript checks per workspace, manual review of changed files.
- Completion conditions: every change verified or marked NOT TESTED, project report and workspace summary updated, no unrelated work altered.

## Previous task

GEE OS 0.5.0 local adoption (wave 1) is complete. Its record is `docs/GEE-OS.md`.

## 8 October 2026: owner-authorised launcher correction

The current owner request supersedes the earlier documentation-only scope for this focused fix. Added the nested npm argument separator to dev:app and dev:landing, preserving ports and workspace setup. Updated README with loopback commands and the app's /app/ URL.

PASS: both exact root commands with -- --host 127.0.0.1 launch successfully and return HTTP 200 for HTML and transformed src/main.tsx. PASS: npm run build for both workspaces. No lint/unit scripts are defined. Local API/database account flows remain NOT TESTED. Test processes stopped; no account, environment or production changes.

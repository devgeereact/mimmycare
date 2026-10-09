# Current Task Contract

## Outcome

Adopt GEE OS 0.5.0 locally without replacing existing project knowledge or changing application behaviour.

## Scope

- Add missing agent-neutral routing and MCP contracts.
- Preserve existing `AGENTS.md`, `CLAUDE.md`, source code, documentation and untracked work.
- Record a project-specific plan for later knowledge modularisation.

## Outside scope

- Feature or defect work.
- Dependency changes.
- File deletion or renaming.
- Git commits, remote synchronisation and pushes.
- Deployment or external-system mutation.

## Mode and workflow

- Primary mode: Existing Application.
- Workflow: Project Recovery for documentation state, then Change Safety for later edits.

## Evidence

- Before and after `git status`.
- Added-file inventory.
- YAML and link checks.
- Confirmation that existing dirty files are unchanged.

## Completion

The local project discovers the shared GEE OS, all original project files remain intact, and remaining modularisation work is recorded honestly.

## 8 October 2026: owner-authorised launcher correction

The current owner request supersedes the earlier documentation-only scope for this focused fix. Added the nested npm argument separator to dev:app and dev:landing, preserving ports and workspace setup. Updated README with loopback commands and the app's /app/ URL.

PASS: both exact root commands with -- --host 127.0.0.1 launch successfully and return HTTP 200 for HTML and transformed src/main.tsx. PASS: npm run build for both workspaces. No lint/unit scripts are defined. Local API/database account flows remain NOT TESTED. Test processes stopped; no account, environment or production changes.

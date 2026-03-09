---
name: veloria-module-sync
description: Use when changing a Veloria feature across routes, Blade screens, API controllers, menus, and docs so the product rename or workflow change stays consistent everywhere.
---

# Veloria Module Sync

Use this skill for repo-wide feature migrations and consistency work.

## When to use
Trigger when a feature is renamed, replaced, or re-scoped across:
- routes
- Blade views
- API controllers
- dashboard promos
- sidebar/menu entries
- AGENTS.md and project docs

Examples:
- replacing `learning` with `trends`
- updating a billing/subscription flow across page + API + copy
- removing dead controllers/views after a module replacement

## Required workflow
1. Search the repo for the feature name with `rg`.
2. Classify matches:
   - active runtime code
   - docs/comments
   - dead/legacy code
3. Update runtime code first:
   - routes
   - menu/navigation
   - page entry points
   - API endpoints/controllers
4. Update docs next:
   - `AGENTS.md`
   - dashboard or help references
5. Remove dead files only if the new runtime no longer depends on them.
6. Re-verify the final flow in browser if a UI route is involved.

## Consistency rules
- do not leave the old product wording in menus if the route changed
- do not keep docs describing removed controllers or deleted views
- if the old route must survive, keep it as a redirect and say so in docs
- if a hidden or deprecated module still supplies data tables/models, document that clearly

## Output expectations
After this type of work, the repo should answer consistently:
- what the feature is called now
- which route is canonical
- which API feeds it
- what files are the current source of truth

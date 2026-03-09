---
name: veloria-blade-ui
description: Use for Blade-based UI work in Veloria CRM when redesigning screens, simplifying admin-heavy layouts, or syncing a Blade page with its API payload and browser verification.
---

# Veloria Blade UI

Use this skill for screen redesigns and UI cleanups in this repo.

## When to use
Trigger when the task involves:
- any `resources/views/*.blade.php` screen redesign
- simplifying a dense CRM screen for inexperienced users
- aligning a Blade screen with its `/api/v1/*` payload
- checking light/dark themes and real browser rendering
- reducing visual noise, collapsing advanced content, or improving empty states

## Core workflow
1. Find the Blade view and the API/controller feeding it.
2. Understand whether the current issue is:
   - layout hierarchy
   - copy/wording
   - too much eager data
   - poor empty state
   - poor dark-theme contrast
3. Change the Blade and the API together if the UI should hide or defer backend data.
4. Clear Blade views:
   - `docker compose exec app php artisan view:clear`
5. Verify in browser at `http://localhost:8080`
6. Check both light and dark themes before closing layout work.

## Veloria UI rules
- default to one obvious primary action
- do not let destructive or advanced actions dominate the first screen
- hide secondary panels or advanced filters until needed
- prefer human-oriented rows/cards over technical registry tables
- for inexperienced users, avoid jargon-first layouts
- if a block is empty and non-essential, consider hiding it entirely instead of rendering a dead section
- for cancellation/downgrade/payment copy, state clearly whether data is preserved

## Practical heuristics
- if a right rail competes with the main task, weaken it or collapse it
- if two bright CTAs compete, demote one
- if a section is useful only after selection, keep it conditional
- if hidden sections still require heavy API data, consider lazy loading
- if dark theme looks washed out, add explicit `html[data-bs-theme="dark"]` overrides

## Verification checklist
- loads without console errors
- useful empty state
- meaningful active state
- dark theme readable
- light theme still intact
- no accidental regressions in button size, spacing, or text wrapping

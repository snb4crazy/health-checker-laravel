# CI/CD Implementation Summary

This documents what was actually built, in what order, and every real issue hit
along the way — kept for reference when setting up CI/CD on future packages.

See `docs/CI-CD-PLAN.md` for the original step-by-step plan and reasoning behind
each decision. This doc is the "what actually happened" companion to that plan.

## Branch / PR

- Branch: `CI/CD`
- PR: [#5](https://github.com/snb4crazy/health-checker-laravel/pull/5) — `CI/CD` → `main`

## Decisions made up front

| Question | Decision |
|---|---|
| Coding style tool | Laravel Pint |
| Test matrix scope | Full matrix: PHP 8.2/8.3/8.4 × Laravel 10/11/12/13 |
| Coverage driver | PCOV (lighter/faster than Xdebug) |
| Release trigger | Manual `git tag vX.Y.Z` + push → workflow creates GitHub Release |
| Packagist publishing | Packagist's own GitHub webhook (no custom Action) |

## Steps completed

### Step 1 — Install PHP + Composer
Created `.github/workflows/ci.yml` with a minimal job: checkout →
`shivammathur/setup-php` (PHP 8.3) → `composer install`. Trigger was
`workflow_dispatch` only at this point, to isolate "does the environment boot"
from later concerns.

### Step 2 — Trigger on push and pull_request
Added `on: push (branches: [main])` and `on: pull_request (branches: [main])`,
kept `workflow_dispatch` for manual debugging runs.

### Step 3 — Run tests across a matrix
Renamed the job to `test`, added a `strategy.matrix` over:
- `php: ['8.2', '8.3', '8.4']`
- `laravel: [10, 11, 12, 13]`
- `include:` mapping each Laravel version to its matching
  `orchestra/testbench` constraint (`^8.0`, `^9.0`, `^10.0`, `^11.0`)

`fail-fast: false` so one broken combo doesn't hide the others. Each job requires
the matching testbench version, then `composer update` (not `install`, so it
actually re-resolves against that Laravel version) and runs `vendor/bin/phpunit`.

### Step 4 — Coding style (Laravel Pint)
Added `laravel/pint` as a dev dependency and a separate `lint` job (single PHP
version, no matrix needed) running `vendor/bin/pint --test` (fails without
rewriting files).

### Step 5 — Verify a failing check actually fails CI
Pushed the branch and opened PR #5 to trigger real `pull_request` runs on
GitHub (not just local trust). This surfaced two real, unrelated environment
bugs — see "Issues found and fixed" below. After fixing them, confirmed the
full pipeline (11 valid matrix jobs + lint) went green from a genuinely broken
starting state, not by accident.

### Step 6 — Coverage report as an artifact
- Added a `<source><include><directory>src</directory></include></source>`
  block to `phpunit.xml` — required by PHPUnit 10+ to know what to measure
  coverage for.
- Added a `coverage` job (single PHP 8.3 run only, since coverage % doesn't
  vary by PHP/Laravel version): installs the PCOV extension via
  `shivammathur/setup-php` (`coverage: pcov`), runs
  `phpunit --coverage-html coverage-html --coverage-clover coverage.xml`,
  uploads both via `actions/upload-artifact@v4` (14-day retention).
- Ignored generated `coverage-html/` and `coverage.xml` in `.gitignore`.
- Verified: the workflow run produced a real `coverage-report` artifact
  (~378 KB) attached to the run.

### Step 7 — Automatic GitHub Release on tag push
Added a standalone `.github/workflows/release.yml`:
- Trigger: `push: tags: ['v*']`
- `permissions: contents: write` (default token is read-only)
- `fetch-depth: 0` on checkout (needed for release-notes generation to see
  full history)
- `softprops/action-gh-release@v2` with `generate_release_notes: true`

Verified live: pushed a throwaway tag `v0.0.0-cicd-test`, confirmed a real
GitHub Release was created with auto-generated notes pulling in merged PRs,
then deleted the test tag and release afterward.

### Step 8 — Publish to Packagist
No code changes — by design (webhook-based, not a GitHub Actions step).
Verified existing configuration:
- GitHub repo webhook → `POST https://packagist.org/api/github` on `push`
  events, active, last response `202`.
- Packagist package `snb4crazy/health-checker-laravel` already registered and
  in sync (tagged versions `v0.2.0`/`v0.3.0` present, branches tracked as
  `dev-*`).
- Confirmed recent webhook deliveries (including the one from the Step 7 test
  tag push) all returned `202`.

## Issues found and fixed

These were real problems surfaced by actually running CI on GitHub (Step 5),
not hypothetical — each one would have silently broken the pipeline if not
caught.

1. **Composer 2.10+ blocks installs on security advisories.**
   CI resolved Composer 2.10.3 by default, which blocks installing packages
   flagged by security advisories. Laravel's own dependency chain currently
   has several such advisories, so `composer update` failed across nearly the
   whole matrix — unrelated to our package code.
   **Fix:** pinned `tools: composer:2.8` in both the `test` and `lint` jobs to
   match local dev tooling and keep CI reproducible.

2. **`laravel/pint ^1.32` requires PHP 8.3+, breaking every PHP 8.2 matrix job.**
   Pint bumped its minimum PHP version starting at v1.31, which conflicted
   with declared PHP 8.2 support in `composer.json`.
   **Fix:** relaxed the constraint to `laravel/pint: ^1.30` — still resolves
   the latest 1.32.x under PHP 8.3/8.4, falls back to 1.30.x under PHP 8.2.

3. **PHP 8.2 + Laravel 13 is a genuinely invalid combination.**
   Laravel 13 (via `orchestra/testbench ^11.0`) requires PHP ^8.3, so this
   combo can never succeed — an accepted risk of choosing a "full matrix"
   over a "trimmed, valid-only matrix" up front.
   **Fix:** added an explicit `exclude:` entry in the matrix so CI reflects
   this as an intentionally unsupported combination rather than a
   permanently red, uninformative job.

4. **Existing code had Pint style violations across nearly every file.**
   Mostly missing trailing newlines, some unary operator spacing and brace
   placement issues — pre-existing, unrelated to the CI setup itself.
   **Fix:** ran `vendor/bin/pint` locally to auto-fix, verified tests still
   passed, committed as a separate `style:` commit (no behavior change).

5. **`git push` to `origin` failed with SSH permission denied.**
   The repo remote was configured for SSH (`git@github.com:...`) but no
   working SSH key was available in this environment; `gh` was authenticated
   via HTTPS only.
   **Fix:** switched the remote URL to HTTPS
   (`https://github.com/snb4crazy/health-checker-laravel.git`) so pushes could
   go through the already-authenticated `gh`/git credential. Worth revisiting
   SSH key setup for future local work if SSH remotes are preferred.

## Final workflow files

- `.github/workflows/ci.yml` — `test` (matrix), `lint` (Pint), `coverage`
  (PCOV + artifact) jobs; triggers on push/PR to `main` plus manual dispatch.
- `.github/workflows/release.yml` — creates a GitHub Release on any `v*` tag
  push, with auto-generated notes.

## Notes for future packages

- Always verify CI actually fails before trusting it (Step 5's approach) —
  it caught two issues that would otherwise have been invisible until a
  contributor's PR mysteriously failed.
- When declaring broad version support (PHP/Laravel ranges) in
  `composer.json`, expect dev-tool constraints (Pint, PHPStan, etc.) to
  sometimes have a narrower floor than the package itself — pin dev deps
  loosely enough to cover your full support matrix.
- Composer's advisory-blocking behavior (2.10+) can break CI installs
  independent of your code; pinning a known-good Composer version keeps CI
  reproducible until you deliberately want to review advisories.
- Packagist sync needs zero GitHub Actions code if you rely on its native
  GitHub webhook — cheaper to maintain than a custom Action calling
  Packagist's API.

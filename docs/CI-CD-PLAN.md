# CI/CD Plan for health-checker-laravel

Goal: learn GitHub Actions end-to-end by building a real pipeline for this Laravel
package, going a bit further than the bare minimum where it teaches something useful.

We go step by step. Each step below = one PR/commit, with reasoning, so you can see
*why*, not just *what*.

## Decisions locked in before starting

- **Coding style tool:** Laravel Pint (zero-config, standard for Laravel packages).
- **Test matrix:** PHP 8.2/8.3/8.4 × Laravel 10/11/12/13 (composer already declares
  support for all of these — matrix proves it's true instead of just claimed).
- **Coverage driver:** PCOV (lighter/faster than Xdebug, coverage-only).
- **Release trigger:** manual `git tag vX.Y.Z` + push → workflow builds a GitHub
  Release from the tag (not fully automated semantic-release, to keep it learnable).
- **Packagist publishing:** via Packagist's own webhook (configured on packagist.org,
  not GitHub Actions) — this is the standard/idiomatic way Packagist expects packages
  to be published, so CI doesn't need to know about Packagist at all.

## Steps

1. **Scaffold the workflow file + install PHP/Composer**
   Create `.github/workflows/ci.yml`. Use `shivammathur/setup-php` to install a PHP
   version and Composer, then `composer install`. No tests yet — just prove the
   environment boots and dependencies resolve.

2. **Trigger on push and pull_request**
   Add `on: push` (main-ish branches) and `on: pull_request` triggers, so every PR
   gets checked before merge, and every push to main is verified too.

3. **Run the test suite (matrix)**
   Add a `matrix` strategy over PHP versions × Laravel/testbench versions, run
   `composer install` then `vendor/bin/phpunit`. This is where "matrix" earns its
   keep: it will actually catch version-specific breakage.

4. **Add coding style check (Laravel Pint)**
   Add Pint as a dev dependency and a separate CI job that runs
   `vendor/bin/pint --test` (fails build on style violations, doesn't rewrite files
   in CI).

5. **Make sure a failing test actually fails CI**
   Deliberately break a test, push, confirm the workflow goes red, then revert.
   This validates steps 1–3 aren't silently green (e.g. wrong working dir, swallowed
   exit codes).

6. **Generate & upload coverage as an artifact**
   Install PCOV, run PHPUnit with `--coverage-html`/`--coverage-clover`, upload the
   report with `actions/upload-artifact`. Kept as an artifact (not a third-party
   coverage service) to stay dependency-free while still learning the mechanic.

7. **Auto-create a GitHub Release on tag push**
   New workflow triggered on `push: tags: ['v*']` that uses
   `softprops/action-gh-release` (or `gh release create`) to create a GitHub Release
   with generated release notes when you push a semver tag.

8. **Publish to Packagist**
   Not a GitHub Actions step — register the package on packagist.org and add the
   GitHub webhook Packagist provides. Every push (and the tag-based release) then
   auto-updates the Packagist listing. We'll do this configuration on packagist.org
   together at the end.

## Order rationale

Steps 1–3 build the minimal working pipeline. Step 4 layers in a second signal
(style) once tests work. Step 5 is a deliberate "trust but verify" checkpoint —
skipping it is a common mistake (green CI that isn't actually testing anything).
Steps 6–8 are the "ship it" side: artifacts for humans (coverage), releases for
GitHub, and Packagist for the PHP ecosystem — each layered on top of a
pipeline we've already proven works.

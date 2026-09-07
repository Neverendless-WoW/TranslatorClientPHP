# GitHub Actions — Workflows

## Workflows

### `validate.yml` — Runs on every push to `main` and all PRs

- Validates `composer.json` is well-formed (`--strict`)
- Installs dependencies
- Checks PHP syntax on all files in `src/`

No secrets required.

---

### `release.yml` — Runs when a version tag is pushed

Triggered by pushing a tag matching `v*.*.*` (e.g. `v1.0.0`).

- Validates the package
- Creates a GitHub Release with auto-generated notes from commits
- Packagist picks up the new version automatically via the webhook (see setup below)

No secrets required — uses the default `GITHUB_TOKEN`.

---

## Releasing a new version

```bash
# 1. Update CHANGELOG.md — move [Unreleased] items under the new version
# 2. Commit the changelog
git add CHANGELOG.md
git commit -m "Release v1.0.0"

# 3. Tag and push — this triggers the release workflow
git tag v1.0.0
git push origin main --tags
```

The GitHub Release is created automatically. Packagist updates within a minute via webhook.

---

## Packagist Setup (one-time)

This is done once when the package is first published. No GitHub Actions secrets are needed.

### Step 1 — Register on Packagist

1. Go to [packagist.org](https://packagist.org) and log in
2. Click **Submit** (top right)
3. Enter the GitHub repo URL: `https://github.com/YOUR_ORG/TranslatorClientPHP`
4. Click **Check** then **Submit**

The package will appear as `neverendless/translator-client`.

### Step 2 — Set up the GitHub webhook

Packagist shows you a webhook URL after registration. Add it to GitHub:

1. Go to your GitHub repo → **Settings → Webhooks → Add webhook**
2. **Payload URL:** the URL Packagist gave you (looks like `https://packagist.org/api/github?username=...`)
3. **Content type:** `application/json`
4. **Secret:** your Packagist API token (from [packagist.org/profile](https://packagist.org/profile))
5. **Events:** select **Just the push event**
6. Click **Add webhook**

After this, every push of a new tag triggers Packagist to pull the latest version automatically.

### Step 3 — Verify

```bash
composer require neverendless/translator-client
```

Should install the latest tagged version.

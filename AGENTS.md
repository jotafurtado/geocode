# Agent skills

## Agent skills

### Issue tracker

Issues and specs are tracked as GitHub issues (via `gh`). See `docs/agents/issue-tracker.md`.

### Triage labels

Five default triage labels: needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.

### Platform support

- **3.x** (current): Laravel **9–13**, PHP **8.1+** — single release per [ADR-0002](docs/adr/0002-single-release-for-laravel-9-through-13.md).
- **2.x**: Laravel 9–13, PHP 8.0+ — maintenance line without 3.0 features.
- **1.x**: frozen at **1.5.0** for legacy Laravel 4–8.

When changing compatibility or release strategy, update the ADR and cross-check `README.md`, `UPGRADING.md`, and `CHANGELOG.md`.

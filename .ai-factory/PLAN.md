# Implementation Plan: Исправление логики ручного и естественного ранга

Branch: none
Created: 2026-05-09

## Settings
- Testing: no
- Logging: verbose
- Docs: no

## Goal
Исправить поведение ручного ранга так, чтобы пользователь всегда продолжал накапливать фактический оборот по линиям и естественный ранг, а отключение переключателя `Ранг установлен вручную` возвращало пользователя к естественному рангу без обнуления дохода/прогресса по линиям.

## Requirements
- Естественный ранг должен считаться по фактическим личным депозитам и оборотам линий независимо от ручного ранга.
- При включенном ручном ранге расчет прогресса должен стартовать от минимально допустимого кумулятивного оборота для выставленного ранга.
- Требования рангов, которые пользователь проскочил за счет ручного назначения, должны считаться полностью заполненными.
- Фактический оборот линии должен сохраняться полностью и попадать в прогресс следующего ранга поверх заполненной базы ручного ранга.
- Новый оборот после включения ручного ранга должен входить в фактический оборот и добавляться поверх расчетной базы.
- При отключении ручного ранга поля override очищаются, затем `users.rank` пересчитывается к естественному значению по реальным данным без бонуса и без обнуления прогресса.

## Relevant Files
- `app/Services/User/UserRankServices.php`
- `app/Actions/User/Partner/ProgressBarAction.php`
- `app/Livewire/Account/Partners/Partners.php`
- `app/MoonShine/Resources/UserResource.php`
- `app/Console/Commands/User/UseUserRankCommand.php`
- `app/Console/Commands/UsersRecalcRankCommand.php`
- `resources/docs/1.0/manual-rank.md`

## Tasks

### Phase 1: Separate Natural And Effective Rank Concepts
- [x] Task 1: Audit and adjust `app/Services/User/UserRankServices.php` so `calculateRank(User $user)` can reliably calculate the natural rank from factual data without using manual-rank baselines when the override is being disabled.
  - Deliverable: rank calculation has a clear path for natural/factual turnover and a separate path for effective manual-rank turnover where needed.
  - Expected behavior: disabling `overridden_rank` and running `user:use-rank --no-bonus` returns `users.rank` to the user's factual rank, not `0` unless factual data truly does not meet any rank.
  - Files: `app/Services/User/UserRankServices.php`, optionally `app/Console/Commands/User/UseUserRankCommand.php` if command-level parameters need to clarify natural recalculation.
  - Dependency notes: this is the foundation for all later UI and admin behavior.
  - Logging requirements: add DEBUG logs around rank calculation entry, selected mode (`natural` vs `manual_effective` if introduced), personal deposit, per-line factual turnover, per-line effective turnover, requirement failures, and final rank; log WARNING only for missing/invalid rank requirement data; do not log sensitive user data beyond IDs and numeric calculation inputs.

### Phase 2: Preserve Manual Rank Baseline And Line Excess
- [x] Task 2: Normalize manual-rank line turnover formula in `app/Services/User/UserRankServices.php` to match the required formula: `effective = max(turnover_before_manual_rank, cumulative_base_for_manual_rank_line) + turnover_since_manual_rank`.
  - Deliverable: manual rank calculation preserves excess on strong lines and injects only the missing base on weak lines.
  - Expected behavior: for manual rank 5 with base line 1 = 4000 and base line 2 = 5000, factual line 1 = 6500 and line 2 = 3000 produces effective line 1 = 6500 and effective line 2 = 5000 before new turnover; later turnover increases both lines from that point.
  - Files: `app/Services/User/UserRankServices.php`.
  - Dependency notes: depends on Task 1 terminology/flow so natural and effective calculations do not overwrite each other.
  - Logging requirements: log DEBUG entries for `base_amount`, `before_amount`, `since_amount`, `effective_amount`, `line`, `manual_rank`, and `overridden_rank_from`; log INFO only when a recalculation changes persisted rank; keep logs structured as `[UserRankServices.method] message {data}`.

### Phase 3: Fix Admin Toggle Disable Flow
- [x] Task 3: Update `app/MoonShine/Resources/UserResource.php::saveLevelOverride()` so disabling `Ранг установлен вручную` clears override fields and recalculates the natural rank in a deterministic order without leaving stale manual-rank state in memory.
  - Deliverable: admin toggle disable persists `overridden_rank = false`, clears `overridden_rank_from`, refreshes the user or passes a clean model to recalculation, and stores the natural rank returned by the rank service/command.
  - Expected behavior: when the admin disables manual rank, user rank becomes the natural rank based on existing turnover and progress bars reflect real factual progress instead of zeroed line income.
  - Files: `app/MoonShine/Resources/UserResource.php`, optionally `app/Services/User/UserRankServices.php` if direct service use is safer than `Artisan::call()`.
  - Dependency notes: depends on Tasks 1 and 2; do not change percent override behavior in the same task except where it shares the save flow.
  - Logging requirements: log DEBUG before and after the toggle branch with `user_id`, old rank, requested rank, old override state, new override state, and `overridden_rank_from`; log INFO when manual rank is disabled and natural recalculation completes; log ERROR with exception context if recalculation fails.

### Phase 4: Align Progress Bars With Rank Calculation
- [x] Task 4: Make progress bar calculations consistent between `app/Actions/User/Partner/ProgressBarAction.php` and `app/Livewire/Account/Partners/Partners.php` so both use the same manual-rank baseline and excess formula.
  - Deliverable: user-facing and admin progress bars show identical line progress rules for manual rank and natural rank.
  - Expected behavior: progress toward the next rank starts from the full factual line turnover above the filled skipped-rank base; after disabling manual rank, progress is based only on factual cumulative turnover.
  - Files: `app/Actions/User/Partner/ProgressBarAction.php`, `app/Livewire/Account/Partners/Partners.php`, optionally extract a small shared calculator under `app/Services/User/` or `app/Actions/User/Partner/` if it reduces duplication without broad rewrite.
  - Dependency notes: depends on Tasks 1 and 2; prefer the smallest safe refactor and avoid changing unrelated partner dashboard transfer logic.
  - Logging requirements: keep DEBUG logs for effective turnover calculation with `user_id`, `line`, `target`, `cumulative_to_next`, `base_amount`, `before_amount`, `since_amount`, `effective_amount`, and calculated `current`; avoid INFO logs on every page render unless an unusual fallback happens.

### Phase 5: Reconcile Legacy Command And Documentation
- [x] Task 5: Review `app/Console/Commands/UsersRecalcRankCommand.php` for duplicated legacy rank logic and either route it through `UserRankServices` or explicitly align its manual-rank handling with the corrected service behavior.
  - Deliverable: bulk/legacy recalculation cannot reintroduce the old zeroing or inconsistent manual-rank calculation.
  - Expected behavior: both `user:use-rank` and any legacy recalculation path produce the same natural rank after override disable and the same effective rank while override is enabled.
  - Files: `app/Console/Commands/UsersRecalcRankCommand.php`, `app/Console/Commands/User/UseUserRankCommand.php`, `app/Services/User/UserRankServices.php`.
  - Dependency notes: depends on Tasks 1 through 3; if `UsersRecalcRankCommand` is unused, still document the decision in a concise code comment or remove duplication only if safe.
  - Logging requirements: log INFO at command start/end with processed user count; log DEBUG for per-user recalculation only if existing command verbosity/log-level conventions allow it; log ERROR for per-user failures with `user_id` and exception class/message.

- [x] Task 6: Update `resources/docs/1.0/manual-rank.md` only if implementation behavior or file locations change from the current documented model.
  - Deliverable: documentation remains accurate for enable, progress, disable, and implementation locations.
  - Expected behavior: docs describe that natural rank continues to be calculated in parallel and disabling manual rank returns to natural rank without zeroing line income.
  - Files: `resources/docs/1.0/manual-rank.md`.
  - Dependency notes: depends on implementation choices in Tasks 1 through 5; skip content changes if the current document remains accurate.
  - Logging requirements: no runtime logging is required for documentation changes; if docs are skipped, note the reason in the implementation summary instead of adding a report task.

## Commit Plan
- **Commit 1** (after tasks 1-3): `fix: restore natural rank after manual override disable`
- **Commit 2** (after tasks 4-6): `fix: align manual rank progress calculations`

## Verification
- Run targeted manual checks or existing app flows for the admin user detail rank toggle and partner progress page.
- Run static checks that are practical for changed files, such as `./vendor/bin/phpstan analyse` and `./vendor/bin/pint --test`.
- Do not add or modify tests because plan settings specify `Testing: no`.

## Risks And Edge Cases
- Existing code uses duplicated progress formulas in Livewire and `ProgressBarAction`; updating only one path will keep inconsistent UI behavior.
- `saveLevelOverride()` currently mixes manual rank and percent override persistence; changes must not reset percent overrides unintentionally.
- Rank bonus payouts must not be triggered when recalculating after manual-rank disable; keep `--no-bonus` behavior or equivalent service call.
- Existing calculations use floats in legacy rank/progress code; avoid expanding float use into new accounting flows, but keep changes minimal unless a dedicated money refactor is planned.

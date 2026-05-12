# Changelog

## 1.0.0 (2026-05-12)


### Features

* add AssignMembership action and actingAsMemberOf Pest helper (epic [#41](https://github.com/shadowburst/vue-kit/issues/41)) ([#47](https://github.com/shadowburst/vue-kit/issues/47)) ([c627693](https://github.com/shadowburst/vue-kit/commit/c627693812cdb3c317480783537e3f4504009fa7))
* Add ChangeMembershipRole action (epic [#43](https://github.com/shadowburst/vue-kit/issues/43), sub-issue [#43](https://github.com/shadowburst/vue-kit/issues/43)) ([#61](https://github.com/shadowburst/vue-kit/issues/61)) ([fa00303](https://github.com/shadowburst/vue-kit/commit/fa0030325781c12815ae3ad1034f9e7be1656bd9))
* add prettier plugins and format ([0968a6b](https://github.com/shadowburst/vue-kit/commit/0968a6b07a7a39c59dce8e285b1291bf0e529ff5))
* Add RemoveMembership action and delete SyncCurrentTeamOnRoleDetached listener ([#60](https://github.com/shadowburst/vue-kit/issues/60)) ([5297f65](https://github.com/shadowburst/vue-kit/commit/5297f65e83ef094d552b81bb8e4991f817777bca))
* Add ResetCurrentTeam action for current_team_id reconciliation ([#46](https://github.com/shadowburst/vue-kit/issues/46)) ([91c2ab8](https://github.com/shadowburst/vue-kit/commit/91c2ab8182c3eb57941497c8bf82ba1cb3b69168))
* add spatie permissions and actions ([9e89337](https://github.com/shadowburst/vue-kit/commit/9e893374966033cc193e61d7218abf2bcc65ade5))
* add spatie/laravel-permission for permissions management ([cf8afe7](https://github.com/shadowburst/vue-kit/commit/cf8afe7f8e4c7d9e133d54359bb3c730f8b5ea9c))
* **admin:** add filament base ([b1fe9b1](https://github.com/shadowburst/vue-kit/commit/b1fe9b1753e7b011859ed536180f65f58ffe3fa9))
* adopt Wayfinder for routes and types ([#48](https://github.com/shadowburst/vue-kit/issues/48)) ([208f20b](https://github.com/shadowburst/vue-kit/commit/208f20b9e4669b917829e8e7b9e6e2a5b55e7ba6))
* ADR-0015: Eloquent models cross the Inertia/JS boundary as JsonResource ([#71](https://github.com/shadowburst/vue-kit/issues/71)) ([18b1731](https://github.com/shadowburst/vue-kit/commit/18b1731e309865a03df852319cb549620bbcb4a5))
* **arch:** Pest architecture test suite + ADR 0001 ([#25](https://github.com/shadowburst/vue-kit/issues/25)) ([9073f8d](https://github.com/shadowburst/vue-kit/commit/9073f8d7c3ef0aa68a75cb5de9b563d93e1f4887))
* Block over-cap downgrade; replace destructive prune with read-only recovery (supersedes ADR-0013) ([#72](https://github.com/shadowburst/vue-kit/issues/72)) ([a08c5a5](https://github.com/shadowburst/vue-kit/commit/a08c5a5c0ec9db16ae4af63b4037bb53660e5c16))
* catch up to upstream ([e749125](https://github.com/shadowburst/vue-kit/commit/e74912525f61af1c43fc7f2e03142b82a4a6ffd0))
* **dev:** add debugbar ([0e6fcd9](https://github.com/shadowburst/vue-kit/commit/0e6fcd96e36dc0e983662d379705abf05d722a97))
* Extract HasTeams and HasMembers traits for membership reads ([#45](https://github.com/shadowburst/vue-kit/issues/45)) ([825d26a](https://github.com/shadowburst/vue-kit/commit/825d26a2248171552fa274c677c7b8ce199953db))
* Filament operator panel for cross-tenant admin ([#96](https://github.com/shadowburst/vue-kit/issues/96)) ([28d3cd3](https://github.com/shadowburst/vue-kit/commit/28d3cd36f00e45890ee1692b4bd73ee93dfa211f))
* **frontend:** add custom data table primitives ([6cd9f2c](https://github.com/shadowburst/vue-kit/commit/6cd9f2cfabc8041f160702af540c626b5dfcd62e))
* **frontend:** adopt shadcn-vue UI kit with custom form/dialog wrappers ([#95](https://github.com/shadowburst/vue-kit/issues/95)) ([2028e33](https://github.com/shadowburst/vue-kit/commit/2028e33a54e6b6484cf5bc73bf2405844fb34649))
* **frontend:** translate Vue view strings ([#97](https://github.com/shadowburst/vue-kit/issues/97)) ([481a5d9](https://github.com/shadowburst/vue-kit/commit/481a5d9255938069802ae7ae8f61997963cf68d2))
* migrate Inertia/JS boundary shapes from JsonResource to Spatie Data ([#94](https://github.com/shadowburst/vue-kit/issues/94)) ([380c24f](https://github.com/shadowburst/vue-kit/commit/380c24f831965216428d55690611e0a23fa7f394))
* multi-language support (i18n) ([#24](https://github.com/shadowburst/vue-kit/issues/24)) ([dd3fb96](https://github.com/shadowburst/vue-kit/commit/dd3fb967348abe3518a7421133929dbf5417ac8b))
* replace laravel wayfinder with spatie typescript transformer ([d6e2ae0](https://github.com/shadowburst/vue-kit/commit/d6e2ae0e3af0ffa3bb342624eca5546b0e0b6acc))
* roles, permissions, and team membership (epic [#15](https://github.com/shadowburst/vue-kit/issues/15)) ([#26](https://github.com/shadowburst/vue-kit/issues/26)) ([821e404](https://github.com/shadowburst/vue-kit/commit/821e404699ffd5191ffc17e959c65e3069cc4538))
* Subscriptions: Cashier + Stripe with tiered access ([#44](https://github.com/shadowburst/vue-kit/issues/44)) ([c527b9f](https://github.com/shadowburst/vue-kit/commit/c527b9f6289d2ecc3d967484e75778b2252e00db))
* Tier-gated team member cap with downgrade enforcement ([#63](https://github.com/shadowburst/vue-kit/issues/63)) ([4d5ab25](https://github.com/shadowburst/vue-kit/commit/4d5ab25dc611a7c685883ac2f1bfe429407e1460))


### Bug Fixes

* **arch:** actions should not have 'Action' suffix ([c6b4c2f](https://github.com/shadowburst/vue-kit/commit/c6b4c2ff520c05f842e2c6156273d057997d832f))
* **dev:** wt config ([64d4036](https://github.com/shadowburst/vue-kit/commit/64d40369d6f496a37dd4117d64a58a325dc2cb31))
* **i18n:** ignore generated lang files ([695d5b6](https://github.com/shadowburst/vue-kit/commit/695d5b69a5647099219f8dd95259e8bf82b43875))
* ignore boost files ([50c71c8](https://github.com/shadowburst/vue-kit/commit/50c71c8d6f84da3958a7274af03faea2838b9391))
* lint and type check ([439cc50](https://github.com/shadowburst/vue-kit/commit/439cc500b01060ba5e05116f98c9c0c37f56288b))
* remove eslint import order rule ([c3a27b1](https://github.com/shadowburst/vue-kit/commit/c3a27b16af7fd1c299dc9958db4f8d25e5aa7a23))
* **test:** static analysis, lint and format ([63c6f2a](https://github.com/shadowburst/vue-kit/commit/63c6f2ac4e70e9ef87a7a0dd479c17c27e341ee1))
* use spatie typescript routes ([61a8962](https://github.com/shadowburst/vue-kit/commit/61a89628911abbf83b74b6df0a5c60cfcdd76466))

# ADR-001: Pure Single-Tenant Architecture and Multi-Site Purge

## Status
Accepted

## Date
2026-10-26

## Context
SlimeKit was originally structured with an upstream multi-site/multi-tenant capability inherited from SlimeNews (an enterprise news publishing platform running multiple independent digital media websites under one backend). In SlimeKit, models and database tables (`tb_article`, `tb_article_category`, `tb_article_stats`) had a `site` column, validators demanded `site` in every query and mutation, cache keys were partitioned by `site`, and revalidation used multi-target endpoint maps.

However, SlimeKit's primary mission is to serve as a clean, lightweight, boilerplate starter framework for new independent projects and microservices. Keeping residual multi-site parameters introduced several issues:
- Developers using SlimeKit for normal single-tenant APIs had to pass dummy `site='default'` parameters in every query and payload.
- Article and category slugs were unique only per `(site, slug)`, complicating indexing and clean URL routing.
- Valkey cache invalidation required complex loops across known sites or multi-segmented cache keys.
- Next.js ISR revalidation required endpoint dictionaries instead of a single webhook URL.

## Decision
Perform a complete **Full Purge** of all multi-site mechanisms across SlimeKit:
1. **Database Schema:** Drop the `site` column entirely from `tb_article`, `tb_article_category`, and `tb_article_stats` in the base Phinx migrations. Add a global unique constraint on `slug` for articles and categories. Simplify composite indexes to `(status, slug)` and `(status, publish)`.
2. **Business Layer:** Eliminate `site` from all request parameters, payload bodies, Rakit validation rules, and Eloquent queries across Admin and Client services.
3. **Cache System:** Flatten Valkey cache keys (e.g. `article:detail:slug=%s` and `article:list:order_by=...`). Simplify cache invalidation to global prefix purges (`article:list:`, `article:detail:`, `article:similar:`, `article:admin`).
4. **ISR Revalidation:** Simplify `NextRevalidator` to consume a single endpoint `NEXT_REVALIDATE_URL` with signature `revalidateArticle(string $slug, ?string $oldSlug = null)`.
5. **Scheduler & Jobs:** Update automated publishing, Cronhooks payloads, and visit analytics queues to operate globally without tenant segregation.

## Alternatives Considered

### Alternative 1: Soft Multi-Site with Default Tenant (`site = 'default'`)
- *Pros:* Minimal code changes to existing upstream services.
- *Cons:* Leaked multi-tenant cognitive overhead into a boilerplate meant to be clean and simple. Database queries still required filtering by `site`, and slug collisions could occur if misconfigured.
- *Rejected:* Does not fulfill the goal of making SlimeKit an intuitive single-tenant starter micro-framework.

### Alternative 2: Configurable Tenant Middleware (Plugin)
- *Pros:* Allows projects to opt-in or opt-out of multi-tenancy.
- *Cons:* Adds excessive complexity to a micro-framework that values simplicity and low latency.
- *Rejected:* Projects requiring enterprise multi-tenancy should reference SlimeNews or implement custom tenant-resolution middleware.

## Consequences
- **Developer Experience:** Clean, intuitive REST API contracts without redundant `site` parameters.
- **Performance:** Simplified queries and indexes in PostgreSQL, reducing query planner overhead and composite index maintenance.
- **Cache Efficiency:** Flattened cache keys with direct prefix invalidation.
- **Backward Compatibility:** Codebases expecting `site` in API payloads will need to omit the parameter. SlimeKit's test suite was fully migrated and verified with 293 passing tests.

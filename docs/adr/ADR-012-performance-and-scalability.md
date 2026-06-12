# ADR-012: Performance and Scalability

## Status
Accepted

## Context
Large tournaments can generate high write volume and heavy recalculation workloads.

## Decision
Use asynchronous scoring jobs, materialized scoreboards, partitioned queue workloads, and periodic reconciliation jobs.
Treat leaderboard freshness as eventual consistency and finalized score snapshots as strong consistency.

## Alternatives Considered
1. Fully synchronous scoring.
2. Single monolithic recalculation process.
3. Read-time aggregation only.

## Consequences
Improves throughput and resilience under peak load.

## Risks
Operational complexity for queue management and observability.

## Why Alternatives Were Rejected
They do not scale reliably for peak submission and scoring windows.

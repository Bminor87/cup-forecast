# ADR-009: Snapshot Strategy

## Status
Accepted

## Context
Historical predictions and scores must remain reproducible forever despite master data changes.

## Decision
Capture immutable snapshots for scoring context and display references when predictions are scored.
Keep mutable master entities separate from immutable historical payloads.

## Alternatives Considered
1. Reference-only approach without snapshots.
2. Event sourcing only without materialized snapshots.

## Consequences
Ensures reproducible historical results and auditability.

## Risks
Higher storage usage and schema versioning complexity.

## Why Alternatives Were Rejected
Reference-only history breaks when names/logos/external values change.

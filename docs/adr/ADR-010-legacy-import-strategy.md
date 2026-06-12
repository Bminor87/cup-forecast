# ADR-010: Legacy Import Strategy

## Status
Accepted

## Context
Legacy imports were brittle and difficult to retry safely.

## Decision
Use staged, idempotent, resumable imports with mapping tables and error logs.
Import authoritative outcomes into PredictionResult, not Prediction.

## Alternatives Considered
1. One-shot migration script.
2. Manual CSV workflows.

## Consequences
Reliable re-runs, traceability, and controlled failure handling.

## Risks
Identity mapping and data quality issues may require complex transformers.

## Why Alternatives Were Rejected
They are operationally fragile and not reproducible.

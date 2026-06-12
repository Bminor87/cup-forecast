# ADR-007: Scoring Engine

## Status
Accepted

## Context
Scoring must be pluggable, testable, versionable, and persisted.

## Decision
Use strategy classes selected by key and version.
Persist prediction_scores and maintain scoreboard as read model.
Run scoring asynchronously via queues.

## Alternatives Considered
1. Compute scores on read.
2. Hardcode scoring in models.
3. Build a full rules DSL first.

## Consequences
Provides auditability and scalable performance.

## Risks
Queue lag can delay fresh leaderboard updates.

## Why Alternatives Were Rejected
They are either not scalable or too rigid for multi-sport growth.

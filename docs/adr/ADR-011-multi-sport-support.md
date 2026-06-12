# ADR-011: Multi-Sport Support

## Status
Accepted

## Context
Platform must support football, ice hockey, future sports, and future club competitions without redesign.

## Decision
Use sport-aware configuration with Tournament sport type, competition mode, and strategy capability mapping.
Keep sport-specific logic in pluggable providers.

## Alternatives Considered
1. Football-first hardcoding.
2. Separate codebases per sport.

## Consequences
Enables extensibility while preserving a shared core domain.

## Risks
Over-abstraction if sport capability boundaries are unclear.

## Why Alternatives Were Rejected
Football-only assumptions caused legacy coupling and future rewrite risk.

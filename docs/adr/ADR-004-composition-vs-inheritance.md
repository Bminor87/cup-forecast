# ADR-004: Composition vs Inheritance

## Status
Accepted

## Context
Tournament must map to teams table while preserving maintainable domain boundaries.

## Decision
Prefer composition and repository mapping over inheritance as default architecture.

## Alternatives Considered
1. Tournament extends Team.
2. Use Team directly as domain model.

## Consequences
Improves decoupling and long-term maintainability. Adds mapper/repository code.

## Risks
Mapping defects if contracts are poorly tested.

## Why Alternatives Were Rejected
Inheritance tightly couples domain behavior to framework internals.

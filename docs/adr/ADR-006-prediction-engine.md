# ADR-006: Prediction Engine

## Status
Accepted

## Context
Legacy app duplicated guess-field logic across tournament and match scopes.

## Decision
Use a unified PredictionField model with a scope enum (tournament or match).
Support Team picker, Player picker, Text, Number, Boolean, Date, and Time.

## Alternatives Considered
1. Separate models by scope.
2. Separate models by field type.
3. Fully untyped JSON-only fields.

## Consequences
Removes duplication and supports extensible field configuration.

## Risks
Validation/indexing complexity if too much flexibility is allowed.

## Why Alternatives Were Rejected
They either recreate legacy duplication or reduce data integrity.

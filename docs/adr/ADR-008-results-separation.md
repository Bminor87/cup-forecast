# ADR-008: Results Separation

## Status
Accepted

## Context
Legacy app stored correct answers through fake user prediction records.

## Decision
Store authoritative outcomes in PredictionResult only.
Never represent results using user accounts.

## Alternatives Considered
1. Admin/fake user predictions.
2. Inline result fields on prediction definitions.

## Consequences
Clear trust boundary between participant input and authoritative results.

## Risks
Requires strict policy controls for who can resolve results.

## Why Alternatives Were Rejected
They blur boundaries and repeat legacy defects.

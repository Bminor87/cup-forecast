# ADR-002: Tournament Aggregate

## Status
Accepted

## Context
Tournament is the central business concept and ownership boundary.

## Decision
Treat Tournament as the aggregate root for participants, invitations, teams, matches, and prediction configuration.

## Alternatives Considered
1. Match-centric aggregate.
2. Team-centric aggregate.

## Consequences
Authorization and lifecycle rules are centralized at Tournament boundary.

## Risks
Aggregate can grow too broad if services are not separated.

## Why Alternatives Were Rejected
They fragment workflows and increase cross-aggregate coordination.

# ADR-005: Team Model Naming

## Status
Accepted

## Context
Business requires Team for competing country or club, while framework uses Team for tournament persistence.

## Decision
Use Team in the business domain for competing teams.
Use tournament_teams for persistence naming when that model is introduced.
Keep framework Team model hidden behind Tournament domain layer.

## Alternatives Considered
1. Keep Competitor naming.
2. Use TournamentTeam everywhere.

## Consequences
Business language stays natural; technical naming collision must be managed.

## Risks
Import alias confusion and accidental wrong model usage.

## Why Alternatives Were Rejected
Competitor violates agreed terminology. TournamentTeam everywhere is less natural for product language.

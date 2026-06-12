# ADR-003: Reuse of Laravel Teams

## Status
Accepted

## Context
Laravel WorkOS already provides teams, team_members, and team_invitations.

## Decision
Reuse these tables as infrastructure storage:
- teams = tournaments
- team_members = participants
- team_invitations = invitations

## Alternatives Considered
1. Create a new tournaments table.
2. Dual-write to both teams and tournaments.

## Consequences
Fast delivery and less migration risk. Storage naming remains a semantic mismatch.

## Risks
Infrastructure naming may leak into domain layer.

## Why Alternatives Were Rejected
They duplicate mature functionality and add migration complexity without immediate value.

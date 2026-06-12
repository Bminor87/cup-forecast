# ADR-001: Terminology

## Status
Accepted

## Context
The business domain requires clear terms that match product language and user expectations.

## Decision
Use Tournament, Team, Player, Match, Participant, and Invitation consistently across domain code.
Do not use Competitor.
Keep Laravel Team wording as infrastructure detail only.

## Alternatives Considered
1. Mixed business and framework naming.
2. Continue using Competitor for competing teams.

## Consequences
Improves readability and product alignment. Requires strict review discipline.

## Risks
Developers may accidentally reintroduce framework terms in domain code.

## Why Alternatives Were Rejected
Mixed naming created cognitive overhead and increased onboarding cost.

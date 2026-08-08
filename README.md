# CyberNanny — AI Core Showcase

Code excerpts from **CyberNanny** (https://cybernanny.uz), an AI-powered child digital safety platform. Published for the **President AI Award 2026** jury review.

CyberNanny is a production system live since 2022 with 100,000+ installs on Google Play (Android, iOS, HarmonyOS, 10 languages including Uzbek). This repository contains **simplified, sanitized excerpts** of its AI pipeline: production keys, prompts, detection signatures, infrastructure details and business logic are intentionally omitted or replaced with illustrative stubs.

## What the AI core does

**1. Real-time AI threat alerts** (`alerts/`)
A pipeline continuously analyses the child's incoming messenger messages and detects grooming, drug solicitation, self-harm risk, violence and sexual content. Parents receive a push notification in their own language with the category, severity and a short explanation. Hundreds of thousands of messages flow through the pipeline monthly; a fast prefilter discards ~99% of neutral traffic before the AI stage, keeping the system economical.

```
messenger stream -> prefilter -> AI classification (category + severity) -> push to parent
                     ~99% out       batched per language                     only real threats
```

Quality loop: admin verdicts (true/false positive), a "possibly missed" review queue, open-rate metrics, per-call cost accounting.

**2. AI Parenting Advisor** (`advisor/`)
A chat assistant for parents. It sees an **anonymized** context of the child's device (screen time, apps, browser history, location) and helps the parent respond calmly and constructively. Weekly AI summaries per child. Built-in ethics: refuses to assist with surveillance of anyone but the user's own child; on bullying or suicide topics it responds warmly and provides helpline numbers (1146 in Uzbekistan).

**3. Privacy by design**
Phone numbers, emails and card numbers are stripped from texts before any AI processing (`advisor/context_sanitizer.example.php`). The AI provider does not train on user data.

## Repository layout

| Path | What it shows |
|---|---|
| `alerts/pipeline.example.php` | Alert pipeline skeleton: cursor, prefilter, batching, idempotent insert, rate limiting |
| `alerts/classifier.example.php` | Batched AI classification call with severity threshold and cost accounting |
| `advisor/context_sanitizer.example.php` | PII stripping before AI processing |
| `advisor/tiers.example.php` | Usage tiers and daily limits |
| `docs/architecture.md` | End-to-end architecture notes |

## What is intentionally NOT here

- API keys, endpoints, server addresses, database schemas
- Real detection patterns and full production prompts (publishing them would help attackers evade detection)
- Mobile client code (Android/iOS/HarmonyOS) and backend business logic

## License

All rights reserved. These excerpts are published solely for the President AI Award 2026 review. See `LICENSE`.

# Architecture notes (showcase)

```
                     +---------------------------+
  child's device --> |  ingestion API (backend)  | --> message store
  (Android/iOS/      +---------------------------+          |
   HarmonyOS)                                               v
                                              +---------------------------+
                                              |  AI alerts pipeline (cron)|
                                              |  prefilter -> AI classify |
                                              +---------------------------+
                                                            |
                                     severity >= threshold  v
                                              +---------------------------+
                                              |  notification service     |
                                              |  FCM / HMS / APNs         |
                                              +---------------------------+
                                                            |
                                                            v
  parent's device  <--  push in parent's language (category + reason)

  parent's cabinet <--> AI Advisor (chat + weekly summaries)
                        ^ anonymized behavioural context only
```

## Design decisions worth noting

- **Prefilter before AI.** ~99% of the message stream never reaches the model.
  This is the difference between an AI feature and an AI product that scales:
  hundreds of thousands of messages a month at a predictable cost.
- **Two-tier models.** A lightweight model handles the high-volume stream and
  chat; a stronger model writes weekly summaries. Prompt caching on static
  system prompts cuts input cost several-fold.
- **Language-aware batching.** Messages are grouped by the parent's language
  before classification so explanations arrive in one of 10 supported locales.
- **Idempotency and rate limiting.** Unique index per source message prevents
  duplicate alerts; pushes are capped per hour so a burst never spams a parent
  (alerts remain visible in the cabinet).
- **Quality loop.** Admin verdicts (true/false positive), a "possibly missed"
  queue, open-rate tracking and per-call cost metering feed a dashboard used
  to tune prompts on real data.
- **Privacy by design.** PII is stripped before AI processing; the advisor
  sees behaviour, not identity; the provider does not train on user data.
- **Multi-platform delivery.** Push delivery spans FCM, Huawei HMS and APNs:
  the product runs on Android, iOS and HarmonyOS.

<?php
/**
 * CyberNanny AI Alerts — pipeline skeleton (sanitized showcase).
 *
 * Runs every few minutes by cron. Reads new incoming messages of protected
 * children, discards neutral traffic with a fast prefilter, sends the rest
 * to the AI classifier in per-language batches, and turns confirmed threats
 * into push notifications for parents.
 *
 * Production values (patterns, thresholds, credentials, table layout) are
 * replaced with illustrative stubs.
 */

const BATCH_LIMIT        = 500;   // messages pulled per tick
const AI_BATCH_SIZE      = 50;    // messages per AI call
const SEVERITY_THRESHOLD = 3;     // 1..5, alerts fired at >= 3
const PUSH_RATE_PER_HOUR = 3;     // max pushes per parent per hour
const HARD_RUNTIME_SEC   = 240;   // stop before the next cron tick

// Single-flight guard: two overlapping ticks must never run.
$lock = fopen('/var/lock/ai-alerts.lock', 'c');
if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);
}

// Cursor survives restarts: plain file with the last processed message id.
$lastId = (int)@file_get_contents(STATE_FILE);

$messages = fetchIncomingMessages($lastId, BATCH_LIMIT); // active subscriptions only
if (!$messages) {
    touch(STATE_FILE); // heartbeat for the watchdog even on idle ticks
    exit(0);
}

// Stage 1: cheap prefilter. Roughly 99% of traffic ends here, which is what
// makes the pipeline economical. Real patterns are not published.
$suspicious = array_filter($messages, fn($m) => prefilterMatches($m['text']));

// Stage 2: group by the parent's language so the AI explains the threat
// in a language the parent actually reads (10 locales supported).
$fired = [];
foreach (groupBy($suspicious, 'parent_lang') as $lang => $batchset) {
    foreach (array_chunk($batchset, AI_BATCH_SIZE) as $batch) {
        foreach (classifyBatch($batch, $lang) as $verdict) { // see classifier.example.php
            if ($verdict['severity'] >= SEVERITY_THRESHOLD) {
                $fired[] = $verdict;
            } else {
                logSkipped($verdict); // "possibly missed" queue for human review
            }
        }
    }
}

foreach ($fired as $alert) {
    // Idempotency: a unique index on (client, source message id) plus
    // ON CONFLICT DO NOTHING makes re-processing safe.
    // Rate limiting: the alert is always stored and visible in the parent
    // cabinet, but push delivery is capped at PUSH_RATE_PER_HOUR.
    insertAlertNotification($alert, allowPush: underPushRate($alert['client_id'], PUSH_RATE_PER_HOUR));
}

// Cursor moves only after successful processing.
file_put_contents(STATE_FILE, $lastId = maxId($messages));

<?php
/**
 * CyberNanny AI Alerts — batched AI classification (sanitized showcase).
 *
 * One AI call classifies up to 50 messages. The model returns, per message:
 * a category, a severity score (1..5) and a one-line reason written in the
 * parent's language. The production prompt is longer and is not published:
 * it encodes hard "not a threat" rules (household hyperbole, ads, slang in
 * innocent context, adults discussing their own lives) that keep the false
 * positive rate low. A shortened illustration is shown instead.
 */

const CATEGORIES = ['grooming', 'violence', 'drugs', 'selfharm', 'sexual'];

function classifyBatch(array $messages, string $parentLang): array
{
    $system = <<<PROMPT
You classify messages received by a child. For each numbered message return
JSON: {idx, category: grooming|violence|drugs|selfharm|sexual|none,
severity: 1-5, reason}. "reason" must be one short sentence in {$parentLang}.
Only flag genuine danger to the child. Everyday hyperbole, advertising and
adults talking about their own lives are NOT threats.
PROMPT;

    $t0 = microtime(true);
    $response = aiComplete([
        'model'       => MODEL_LIGHTWEIGHT,   // light model for the stream; a stronger one serves weekly summaries
        'temperature' => 0.0,
        'system'      => $system,             // cached between calls to cut cost
        'messages'    => [numberedList($messages)],
    ]);

    // Every call is metered: batch size, tokens in/out, latency, cost.
    // This feeds the admin dashboard and keeps unit economics visible.
    logAiCost($response->usage, count($messages), microtime(true) - $t0);

    return parseVerdicts($response, $messages);
}

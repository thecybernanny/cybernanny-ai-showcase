<?php
/**
 * CyberNanny AI Advisor — PII sanitizer (sanitized showcase).
 *
 * Before any text reaches the AI provider, personally identifiable data is
 * stripped. The advisor receives an anonymized behavioural context (apps,
 * screen time, browser history topics, coarse location), never raw identity.
 * The AI provider does not train on user data.
 */

function sanitizeForAi(string $text): string
{
    $rules = [
        // phone numbers, incl. +998 / +7 formats with spaces and dashes
        '/\+?\d[\d\s\-\(\)]{7,}\d/u'                  => '[phone]',
        // email addresses
        '/[\w.+-]+@[\w-]+\.[\w.]+/u'                  => '[email]',
        // bank card numbers (13-19 digits, optional separators)
        '/\b(?:\d[ -]?){13,19}\b/u'                   => '[card]',
    ];
    return preg_replace(array_keys($rules), array_values($rules), $text);
}

/**
 * The advisor context is assembled from behavioural signals only.
 * Example of what the AI actually sees for "why is my child up at night":
 *
 *   device: "TECNO BG6" (1 of 2)
 *   screen_time_today: 5h 40m, top: YouTube 2h 10m, TikTok 1h 30m
 *   last_night_activity: 23:40-01:15 messenger + video
 *   location_24h: home -> school -> home (reverse-geocoded, coarse)
 *   browser_topics: gaming, music, homework
 *
 * Built-in ethics enforced at prompt level and by a pre-AI screener:
 *  - refuses tracking anyone except the user's own child;
 *  - no medical or psychiatric diagnoses;
 *  - bullying / self-harm topics get a warm response plus helpline numbers
 *    (1146 in Uzbekistan, 8-800-2000-122 in Russia).
 */

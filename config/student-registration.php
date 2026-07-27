<?php

$allowedDomains = array_values(array_filter(array_map(
    static fn (string $domain): string => mb_strtolower(trim($domain)),
    explode(',', (string) env('STUDENT_ALLOWED_EMAIL_DOMAINS', 'gmail.com')),
)));

return [
    'google_only' => (bool) env('STUDENT_REGISTRATION_GOOGLE_ONLY', true),
    'allowed_email_domains' => $allowedDomains,
    'require_join_code' => (bool) env('STUDENT_REGISTRATION_REQUIRE_JOIN_CODE', true),
    'auto_activate_after_onboarding' => (bool) env('STUDENT_AUTO_ACTIVATE_AFTER_ONBOARDING', true),
];

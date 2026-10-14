<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry DSN & Environment
    |--------------------------------------------------------------------------
    |
    | The DSN tells Sentry where to send events. If empty or null, Sentry
    | is safely disabled (e.g. during local tests or dev).
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    'release' => env('SENTRY_RELEASE', env('APP_VERSION', '1.0.0')),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring & Profiling Sample Rates
    |--------------------------------------------------------------------------
    |
    | 0.0 means no transactions are traced, 1.0 means all are captured.
    | Recommended production default is 0.1 - 0.2 (10-20% sampling).
    |
    */
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.05),

    /*
    |--------------------------------------------------------------------------
    | PII & Privacy Protection
    |--------------------------------------------------------------------------
    |
    | Do NOT send raw passwords, authorization headers, or customer secrets.
    |
    */
    'send_default_pii' => false,

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | Do not pollute error tracking with standard expected HTTP exceptions.
    |
    */
    'ignore_exceptions' => [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs & Queue Integration
    |--------------------------------------------------------------------------
    */
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => false, // Keep bindings false to prevent leaking sensitive credentials in SQL logs
        'queue_info' => true,
        'command_info' => true,
    ],

    'tracing' => [
        'queue_job_transactions' => true,
        'queue_jobs' => true,
        'sql_queries' => true,
        'views' => true,
    ],
];

<?php

namespace SearchJet\Laravel\Exceptions;

/**
 * HTTP 429: a rate limit or a plan quota was hit.
 *
 * $errorCode tells them apart, e.g. QUOTA_EXCEEDED or DAILY_LIMIT_EXCEEDED (search),
 * quota_exceeded or reindex_limit (indexing), or null for a plain per-minute throttle.
 */
class RateLimitException extends SearchJetException {}

# Security & Performance Test Suite

This document explains the comprehensive test suite designed to validate security and performance fixes identified in the Security & Performance Audit.

## Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test tests/SecurityAuditTest.php
composer test tests/PerformanceAuditTest.php

# Run with coverage
composer test-coverage
```

## Test Suites

### 1. SecurityAuditTest.php

Tests for **9 security vulnerabilities** identified in the audit.

#### Tests Included:

| Test | Issue | Status |
|------|-------|--------|
| `test_api_key_not_exposed_via_public_getter` | Security #2: Public API Key Getter | ⚠️ Will Fail Until Fixed |
| `test_https_url_enforcement` | Security #5: No HTTPS Enforcement | ⚠️ Will Fail Until Fixed |
| `test_sensitive_data_not_logged` | Security #1: API Key in Logs | ⏳ Needs Implementation |
| `test_query_size_validation` | Security #4: No Input Validation | ⚠️ Will Fail Until Fixed |
| `test_search_options_validation` | Security #4: No Input Validation | ⚠️ Will Fail Until Fixed |
| `test_cache_key_not_predictable` | Security #6: Predictable Cache Keys | ⚠️ Will Fail Until Fixed |
| `test_exception_messages_not_detailed` | Security #7: Exception Disclosure | ⏳ Needs Implementation |
| `test_model_indexes_only_intended_attributes` | Security #3: Excessive Indexing | ⏳ Needs Implementation |
| `test_api_key_validation_on_bootstrap` | Security #8: No Key Rotation | ⏳ Needs Implementation |
| `test_base_url_validation_strict` | Security #5: No HTTPS Enforcement | ⚠️ Will Fail Until Fixed |

**Expected Outcome:** Tests will fail until security issues are fixed. This is intentional - they document what needs to be implemented.

### 2. PerformanceAuditTest.php

Tests for **8 performance issues** identified in the audit.

#### Tests Included:

| Test | Issue | Status |
|------|-------|--------|
| `test_cache_key_generation_performance` | Performance #1: Inefficient Keys | ⚠️ Will Fail Until Fixed |
| `test_analytics_disabled_by_default` | Performance #2: Analytics Default | ⚠️ Will Fail Until Fixed |
| `test_bulk_indexing_performance` | Performance #5: Bulk Operations | ⏳ Needs Implementation |
| `test_default_attributes_retrieval` | Performance #7: Default Attrs ['*'] | ⚠️ Will Fail Until Fixed |
| `test_rate_limiting_implemented` | Performance #6: Rate Limiting | ⏳ Needs Implementation |
| `test_query_size_prevents_dos` | Performance #4: Query Validation | ⚠️ Will Fail Until Fixed |
| `test_retry_logic_implemented` | Performance #8: Retry Logic | ⏳ Needs Implementation |
| `test_connection_pooling_configured` | Performance #3: Connection Pool | ⏳ Needs Implementation |
| `test_cache_effectiveness` | Caching Validation | ⏳ Needs Implementation |
| `test_query_response_time_expectations` | Performance SLA Documentation | ℹ️ Documentation |
| `test_analytics_tracking_async` | Performance #2: Async Analytics | ⏳ Needs Implementation |

**Expected Outcome:** Tests document performance expectations and will validate fixes once implemented.

### 3. SearchJetClientTest.php (Updated)

Updated to not expose API key and follow security best practices.

---

## Understanding Test Results

### ✅ Green (Passing)
Test is passing - the implementation matches the expected behavior.

### ⚠️ Red (Failing)
Test is failing - indicates an unimplemented security or performance fix. **This is expected!** These tests fail by design to drive implementation of the fixes.

### ⏳ Incomplete/Pending
Test is marked as pending or needs additional implementation. Check the test for specific requirements.

### ℹ️ Information
Test documents a requirement or best practice without failing.

---

## Fixing Issues

When a test fails, follow these steps:

1. **Read the test** to understand what's expected
2. **Check SECURITY_PERFORMANCE_AUDIT.md** for detailed remediation
3. **Implement the fix** following the provided code examples
4. **Run the test** to verify the fix:
   ```bash
   composer test tests/SecurityAuditTest.php::test_name
   ```
5. **Commit the fix** with reference to the issue

### Example: Fixing Security #2 (Public API Key Getter)

**Test that will fail:**
```php
public function test_api_key_not_exposed_via_public_getter()
{
    $this->assertFalse(method_exists($client, 'getApiKey') && is_callable([$client, 'getApiKey']),
        'getApiKey() should not be a public method...');
}
```

**Steps to fix:**
1. Open `src/Services/SearchJetClient.php`
2. Remove the `getApiKey()` method or make it private
3. Run: `composer test tests/SecurityAuditTest.php::test_api_key_not_exposed_via_public_getter`
4. Test should now pass ✅

---

## Performance Benchmarks

The performance tests include expected metrics:

### Cache Key Generation
- **Target:** <2ms for 1000 generations
- **Current Issue:** Using `serialize()` and `md5()`
- **Fix:** Use `json_encode()` and `hash('sha256')`
- **Expected Improvement:** ~60% faster

### Query Response Time
- **Cache Hit:** <5ms
- **Cache Miss (no analytics):** <150ms
- **Cache Miss (with analytics):** <200ms

### Bulk Indexing
- **Batch Size:** 1000 documents
- **Expected Throughput:** 1000+ docs/sec
- **Should Support:** Progress callbacks

---

## Test Structure

```php
// Example test structure
public function test_security_issue()
{
    // Arrange
    $client = new SearchJetClient('key', 'https://api.com');

    // Act / Assert
    $this->assertTrue(
        condition,
        'Clear message explaining the security requirement'
    );
}
```

---

## CI/CD Integration

Add these tests to your CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run security tests
  run: composer test tests/SecurityAuditTest.php

- name: Run performance tests
  run: composer test tests/PerformanceAuditTest.php

- name: Run all tests
  run: composer test
```

---

## Documentation References

- [SECURITY_PERFORMANCE_AUDIT.md](./SECURITY_PERFORMANCE_AUDIT.md) - Detailed audit findings
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

## Progress Tracking

Use this checklist to track security and performance fixes:

### Security Fixes
- [ ] Remove `getApiKey()` public method
- [ ] Implement HTTPS URL enforcement
- [ ] Redact sensitive data from logs
- [ ] Add input validation (query size, options)
- [ ] Use SHA256 for cache keys
- [ ] Generic exception messages
- [ ] API key validation on bootstrap
- [ ] Request signing/verification
- [ ] Sensitive field filtering in models

### Performance Fixes
- [ ] Change analytics default to disabled
- [ ] Optimize cache key generation
- [ ] Implement query size limits
- [ ] Add connection pooling
- [ ] Implement actual rate limiting
- [ ] Change default attributes
- [ ] Add retry logic
- [ ] Support async analytics

---

## Questions?

Refer to the audit document or check test comments for detailed explanations:

```bash
# View a specific test
grep -A 20 "test_api_key_not_exposed" tests/SecurityAuditTest.php
```

All tests are self-documenting with clear comments explaining the security/performance concern.

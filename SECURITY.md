# Security Policy

## Supported Versions

We currently support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take the security of the SearchJet Laravel package seriously. If you discover a security vulnerability, please follow these steps:

### Please DO NOT

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed

### Please DO

1. **Email us directly** at security@searchjetengine.com
2. **Include the following information**:
   - Type of vulnerability
   - Full paths of source file(s) related to the vulnerability
   - Location of the affected source code (tag/branch/commit)
   - Step-by-step instructions to reproduce the issue
   - Proof-of-concept or exploit code (if available)
   - Impact of the vulnerability
   - How you think it could be exploited

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours
- **Updates**: We will provide regular updates on the progress of addressing the vulnerability
- **Resolution**: We aim to resolve critical vulnerabilities within 7 days
- **Credit**: With your permission, we will credit you for the discovery in our changelog and security advisories

## Security Best Practices

When using the SearchJet Laravel package:

1. **Never commit API keys** to version control
2. **Use environment variables** for sensitive configuration
3. **Keep dependencies updated** regularly
4. **Use HTTPS** for all API communications (default behavior)
5. **Implement rate limiting** to prevent abuse
6. **Enable auto-sync cautiously** in production environments
7. **Monitor logs** for suspicious activity

## Security Updates

Security updates will be released as patch versions and announced through:

- GitHub Security Advisories
- Package release notes
- Email notifications to registered users

## Disclosure Policy

We follow responsible disclosure principles:

1. Security issues are fixed as quickly as possible
2. Fixes are released as soon as they are available
3. Public disclosure follows after users have had time to update (typically 7-14 days)

## Questions

If you have questions about security that don't relate to vulnerabilities, please contact us at support@searchjetengine.com.

Thank you for helping keep SearchJet and its users safe!

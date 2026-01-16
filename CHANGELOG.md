# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-01-15

### Added

- Initial release of TubaPay PHP SDK
- Core authentication with OAuth2 token management
- Offer API for getting available installment options
- Transaction API for creating payment transactions
- Webhook handling with HMAC-SHA512 signature verification
- DTOs for all API requests and responses:
  - Customer, OrderItem, Offer, OfferItem, Transaction
  - Webhook payloads: StatusChanged, Payment, Invoice
- Agreement status enum with helper methods
- Environment enum for Test/Production
- Custom token storage interface for persistent token management
- Comprehensive exception hierarchy
- PHPStan level 8 static analysis
- PHPUnit test suite with 219 tests and 523 assertions

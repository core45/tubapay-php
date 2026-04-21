# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.2] - 2026-04-21

### Fixed

- `TransactionApi::createTransactionWithItems()` (and `createTransactionFromSelection()`, which wraps it) now throw `ValidationException` when passed more than one `OrderItem`, instead of silently emitting an undocumented plural `order.items` payload. TubaPay's `/api/v1/external/transaction/create` endpoint accepts exactly one `order.item` with a single `totalValue` — the reference WordPress integration (`tubapay-v2`) sends `$order->get_total()` as that value. The plural path was non-functional: TubaPay returned 200 OK but rendered the resulting agreement as `0.00 zł` in the checkout form. Callers with multiple order rows must aggregate them into a single item before calling (sum `totalValue`s, use a representative name such as `"Zamówienie nr {id}"`).
- Removed the unreachable plural branch from `TransactionApi::buildPayload()`; the method now always emits the documented singular `order.item`.

### Documentation

- README now documents the single-item constraint with a pointer to the WooCommerce plugin's aggregation pattern.

## [0.2.1] - 2026-04-19

### Fixed

- Moved `InvoicePosition` to its own PSR-4 autoloadable file so downstream packages can type-discover recurring invoice position DTOs.

## [0.2.0] - 2026-04-19

### Added

- Added consent DTO parsing on offers.
- Added UI text and content APIs for checkout labels, top bar content, and popup content.
- Added transaction metadata support matching the official WooCommerce plugin fields.
- Added checkout selection DTO and transaction creation from selected installments and consents.
- Added token response metadata and SDK connection checks.
- Added amount-only client offer helpers for available installment discovery.

## [0.1.2] - 2026-04-19

### Fixed

- Added support for the current TubaPay token response format used by the official WooCommerce plugin (`token` / `expires`).
- Updated offer and transaction request payloads to match the current TubaPay v2 API shape.
- Retry authenticated requests once after an unauthorized response by refreshing the access token.
- Added the missing direct `psr/log` runtime dependency used by the SDK logger API.

## [0.1.1] - 2026-04-19

### Fixed

- Added the required `PARTNER_CLIENT_CREDENTIALS` grant type to the TubaPay partner authentication request.

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

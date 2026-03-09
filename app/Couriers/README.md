# Courier Classes (app/Couriers)

This directory contains all courier-specific logic for handling webhook events and shipment processing. Each courier has a dedicated class to encapsulate its unique processing rules, authentication, and payload handling.

## Purpose

- Decouple courier-specific logic from controllers.
- Handle webhook verification, idempotency, and status normalization.
- Ensure the system is extensible for future courier integrations.
- Maintain a thin controller that delegates actual work to courier classes.

## Current Classes

- `PathaoCourier.php` - Handles Pathao webhooks, including:
  - Authentication via `auth_token`.
  - Idempotency checks using `event_hash`.
  - Inserting or updating shipments and shipment events.
  - Responding with the required Pathao integration headers.
- `SteadfastCourier.php` - Handles Steadfast webhooks, including:
  - Bearer token verification.
  - Idempotency check for duplicate events.
  - Shipment creation or update.
  - Event storage with normalized statuses.

## Implementation Notes

- Each courier class exposes a `handle()` method that accepts:
  - `$data` - Webhook JSON payload
  - `$authToken` - DB stored auth token
  - `$webhookSecret` - DB stored webhook secret (if required)
  - `$request` - CI `IncomingRequest` object
  - `$response` - CI `ResponseInterface` object
- The controller `CourierWebhook` delegates webhook handling to the appropriate courier class.
- Raw payloads can be stored in `courier_webhooks` table for auditing.
- Courier classes are \*\*independent\*\* and can be unit-tested without touching the controllers.

## Adding a New Courier

1.  Create a new class in this directory, e.g., `MyCourier.php`.
2.  Implement a public `handle()` method accepting the 5 standard parameters.
3.  Perform authentication and idempotency checks inside the class.
4.  Insert/update shipments and events as needed.
5.  Return a proper `$response` object.
6.  Update `CourierWebhook::receive()` to call your new courier class when the provider matches.

## Benefits of this Structure

- Thin controllers; courier logic fully encapsulated.
- Easy addition or removal of courier providers.
- Consistent webhook handling and response formatting.
- Idempotent, safe, and testable architecture.

## References

- Pathao integration docs for webhook headers.
- Steadfast API docs for bearer authentication.
- CodeIgniter 4 Models and Filters for database interaction.

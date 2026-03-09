# Courier Webhook & Shipment Tracking Service

A lightweight **backend microservice** built with **CodeIgniter 4** to manage shipment tracking and courier integrations. The system handles webhook updates from multiple courier providers, normalizes shipment statuses, stores event history, and exposes REST APIs for querying shipment details.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Architecture & Tech Stack](#architecture--tech-stack)
- [Database Structure](#database-structure)
- [API Endpoints](#api-endpoints)
- [Webhooks](#webhooks)
- [Directory Structure](#directory-structure)
- [Courier Logic (`app/Couriers`)](#courier-logic-appcouriers)
- [Setup & Configuration](#setup--configuration)
- [Future Enhancements](#future-enhancements)

---

## Project Overview

This microservice is designed to act as a **courier integration layer**. It:

- Receives webhook updates from courier providers (currently **Pathao** and **Steadfast**).
- Stores shipment data and event history in a normalized structure.
- Provides REST API endpoints for querying shipments by **merchant order ID** or **consignment ID**.
- Normalizes courier-specific events into internal shipment statuses.
- Supports easy addition of new courier providers.

---

## Features

- **Webhook handling** with idempotency to prevent duplicate events.
- **Shipment tracking APIs** with timeline/event history.
- **Database transactions** for safe insert/update operations.
- **Raw payload storage** for auditing and debugging.
- **Courier abstraction layer** for provider-specific logic.
- **Automatic migrations** via the `AutoMigrate` filter.
- **Production-ready design**: logging, error handling, and header compliance.

---

## Architecture & Tech Stack

- **Framework**: CodeIgniter 4
- **Language**: PHP 8+
- **Database**: MySQL / MariaDB
- **Architecture**: REST API microservice

**Key Principles**

- Controllers are thin; DB logic resides in models.
- Provider-agnostic shipment statuses.
- Idempotent webhook handling.
- Extensible for new courier providers.

---

## Database Structure

### `courier_providers`

Stores configuration for each courier.

```
| Column         | Type        | Notes                       |
|----------------|------------|-----------------------------|
| id             | INT        | Primary key                 |
| name           | VARCHAR    | Unique courier name         |
| webhook_secret | VARCHAR    | Optional secret for webhooks|
| auth_token     | VARCHAR    | API authentication token    |
| created_at     | TIMESTAMP  | Auto timestamp              |
```

### `shipments`

Stores shipments per courier consignment.

```
| Column            | Type     | Notes                          |
|------------------|---------|--------------------------------|
| id               | BIGINT  | Primary key                     |
| provider         | VARCHAR | Courier provider name           |
| consignment_id   | VARCHAR | Courier consignment ID          |
| merchant_order_id| VARCHAR | Normalized to `order_id`       |
| invoice          | VARCHAR | Optional                        |
| current_status   | VARCHAR | Normalized shipment status      |
| cod_amount       | DECIMAL | Optional                        |
| delivery_fee     | DECIMAL | Optional                        |
| created_at       | DATETIME| Timestamp                       |
| updated_at       | DATETIME| Timestamp                       |
```

### `shipment_events`

Stores timeline of shipment updates.

```
| Column           | Type     | Notes                         |
|-----------------|---------|-------------------------------|
| id              | BIGINT  | Primary key                   |
| shipment_id     | BIGINT  | Foreign key to `shipments`   |
| provider_event  | VARCHAR | Original courier event        |
| normalized_status | VARCHAR | Internal normalized status  |
| message         | TEXT    | Optional event message        |
| event_time      | DATETIME| Event timestamp               |
| event_hash      | VARCHAR | MD5 hash for idempotency      |
| created_at      | TIMESTAMP | Auto timestamp               |
```

### `courier_webhooks`

Stores raw webhook payloads.

```
| Column            | Type     | Notes                       |
|------------------|---------|-----------------------------|
| id               | BIGINT  | Primary key                 |
| provider         | VARCHAR | Courier provider             |
| consignment_id   | VARCHAR | Optional                     |
| merchant_reference| VARCHAR| Optional                     |
| payload          | JSON    | Raw webhook payload          |
| headers          | JSON    | Raw request headers          |
| received_at      | TIMESTAMP | Auto timestamp             |
```

---

## API Endpoints

### Shipments

```
| Method | Endpoint                            | Description                                  |
|--------|------------------------------------|----------------------------------------------|
| GET    | `/api/shipments/{order_id}`         | Retrieve shipment by merchant order ID      |
| GET    | `/api/shipments/consignment/{id}`  | Retrieve shipment by consignment ID         |
| GET    | `/api/shipments`                    | List all shipments                           |
| GET    | `/api/shipments/{order_id}/events` | Timeline of shipment events                  |
```

### Webhook

```
| Method | Endpoint                       | Notes                                     |
|--------|--------------------------------|-------------------------------------------|
| POST   | `/api/courier/{provider}`      | Receives courier webhook (`pathao`, `steadfast`) |
```

---

## Webhooks

- **Pathao**: Requires `X-Pathao-Merchant-Webhook-Integration-Secret` header in the response.
- **Steadfast**: Uses `Authorization: Bearer {auth_token}` header for verification.
- Idempotency is ensured via `event_hash` (MD5 of the payload).
- Raw webhook payloads are stored in `courier_webhooks` for auditing/debugging.

---

## Directory Structure

```text
app/
├── Config/
│   └── Routes.php
├── Controllers/
│   ├── Shipments.php
│   └── CourierWebhook.php
├── Models/
│   ├── ShipmentModel.php
│   └── ShipmentEventModel.php
├── Couriers/
│   ├── PathaoCourier.php
│   └── SteadfastCourier.php
└── Filters/
    └── AutoMigrate.php
```

## Courier Logic (`app/Couriers`)

All courier-specific logic is **moved to individual classes**:

- **PathaoCourier.php**: Handles Pathao webhook processing, idempotency, shipment insert/update, event insert, and returns required headers.
- **SteadfastCourier.php**: Handles Steadfast webhook in a similar manner.
- Each courier class is **independent of CI controller**, but **request/response objects are injected**.

This abstraction ensures:

- Easy addition of new couriers.
- Unit-testable courier logic.
- Controller remains thin.

---

## Setup & Configuration

1. Clone the repository and install dependencies.
2. Configure `.env` for database and environment variables.
3. Run the database migrations:

```bash
php spark migrate
```

4. Optional: enable AutoMigrate filter to auto-run migrations.

5. Configure couriers in courier_providers table (auth_token, webhook_secret).

6. Set webhook URLs in courier dashboards to /api/courier/{provider}.

## Future Enhancements

- Courier status normalization mapping table.

- Retry queue for failed webhook processing.

- Rate limiting and signature verification for webhooks.

- Admin interface for shipments and events.

- Multi-tenant support for multiple merchants.

- Monitoring and error tracking.

**Author:** SMD Electronics.
**Date:** 2026-03-09

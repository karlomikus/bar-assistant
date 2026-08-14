# DDD Strategic Patterns

> Sources:
> - [Domain-Driven Design: The Blue Book](https://www.domainlanguage.com/ddd/blue-book/) — Eric Evans (2003)
> - [DDD Resources](https://www.domainlanguage.com/ddd/) — Domain Language (Eric Evans)
> - [Bounded Context](https://martinfowler.com/bliki/BoundedContext.html) — Martin Fowler
> - [Domain Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html) — Martin Fowler
> - [Anti-Corruption Layer](https://docs.aws.amazon.com/prescriptive-guidance/latest/cloud-design-patterns/acl.html) — AWS
> - [Domain Analysis for Microservices](https://learn.microsoft.com/en-us/azure/architecture/microservices/model/domain-analysis) — Microsoft

## Overview

Strategic DDD patterns help decompose large systems into manageable parts with clear boundaries. They answer: **"How do we divide a complex domain?"**

**DDD is fundamentally collaborative.** The patterns below emerge from conversations, whiteboarding, and modeling sessions with domain experts—not from coding alone.

---

## Domain Discovery Techniques

### Event Storming

A workshop technique for discovering domain events, aggregates, and bounded contexts.

```
Orange sticky: Domain Event (past tense: "OrderPlaced")
Blue sticky: Command (imperative: "Place Order")
Yellow sticky: Aggregate (noun: "Order")
Pink sticky: External System / Policy
Purple sticky: Problem / Question
```

**Workshop flow:**
1. **Chaotic exploration** — Everyone adds events they know about
2. **Timeline ordering** — Arrange events chronologically
3. **Identify aggregates** — Group related events
4. **Find boundaries** — Where language changes = bounded context boundary
5. **Surface problems** — Mark unclear areas for follow-up

### Context Mapping Workshop

For existing systems, map how bounded contexts currently interact:
1. List all systems/services
2. Identify which team owns each
3. Draw relationships (upstream/downstream)
4. Label relationship types (ACL, Conformist, etc.)
5. Identify pain points in current integrations

---

## Ubiquitous Language

The foundation of DDD. A shared vocabulary between developers and domain experts that appears in:
- Code (class names, method names)
- Documentation
- Conversations
- UI labels

### Principles

1. **One language per bounded context** - Different contexts may use the same word differently
2. **Code reflects the language** - `Order.confirm()` not `Order.setStatus("confirmed")`
3. **Evolve together** - When language changes, code changes

### Example

```
❌ Technical language:
   "Set the order entity's status field to 2 and insert a record"

✅ Ubiquitous language:
   "Confirm the order and record that it was confirmed"
```

```typescript
// ❌ Technical, not ubiquitous
class Order {
  setStatus(status: number): void { this.status = status; }
}

// ✅ Ubiquitous language
class Order {
  confirm(): void {
    if (this.status !== OrderStatus.Pending) {
      throw new OrderCannotBeConfirmedException(this.id);
    }
    this.status = OrderStatus.Confirmed;
    this.confirmedAt = new Date();
    this.addDomainEvent(new OrderConfirmed(this.id));
  }
}
```

---

## Bounded Contexts

A **semantic boundary** where a particular domain model applies. Within a bounded context, terms have precise, unambiguous meaning.

> **Key insight:** Polysemy (same word, different meanings) across departments is natural, not a problem. The same term meaning different things in different contexts is expected—"the dominant boundary factor is human culture and language variation." — Martin Fowler

### Key Concepts

- Each bounded context has its **own ubiquitous language**
- Each bounded context has its **own model**
- The same real-world concept may have **different representations** in different contexts

### Example: E-Commerce System

```mermaid
flowchart TB
    subgraph ECommerce["E-Commerce System"]
        subgraph Sales["Sales Context"]
            SC1["Customer: id, email, preferences"]
            SC2["Order: items, total, status"]
        end
        subgraph Shipping["Shipping Context"]
            SH1["Recipient: name, address, phone"]
            SH2["Shipment: packages, carrier, trackingNo"]
        end
        subgraph Billing["Billing Context"]
            BC1["Payer: name, billingAddress, paymentMethod"]
            BC2["Invoice: lineItems, total, dueDate"]
        end
        subgraph Catalog["Catalog Context"]
            CC1["Product: name, description, price"]
            CC2["(no customer concept)"]
        end
    end

    style Sales fill:#3b82f6,stroke:#2563eb,color:white
    style Shipping fill:#10b981,stroke:#059669,color:white
    style Billing fill:#f59e0b,stroke:#d97706,color:white
    style Catalog fill:#8b5cf6,stroke:#7c3aed,color:white
```

**"Customer" means different things:**
- **Sales**: Email, preferences, order history
- **Shipping**: Delivery address, phone number
- **Billing**: Payment methods, billing address

### Bounded Context = Microservice Boundary

In microservices, each bounded context typically becomes a separate service:

```mermaid
flowchart LR
    subgraph Sales["Sales Service"]
        S1["Orders DB"]
        S2["Order API"]
    end
    subgraph Shipping["Shipping Service"]
        SH1["Shipments DB"]
        SH2["Shipping API"]
    end
    subgraph Billing["Billing Service"]
        B1["Invoices DB"]
        B2["Billing API"]
    end

    Sales -->|events| Shipping
    Shipping -->|events| Billing
    Sales -.->|Integration Events| Events[("Event Bus")]
    Shipping -.-> Events
    Billing -.-> Events

    style Sales fill:#3b82f6,stroke:#2563eb,color:white
    style Shipping fill:#10b981,stroke:#059669,color:white
    style Billing fill:#f59e0b,stroke:#d97706,color:white
```

---

## Subdomains

Areas of business expertise. Subdomains are **discovered**, not designed.

### Types

| Type | Description | Investment | Example |
|------|-------------|------------|---------|
| **Core** | Competitive advantage | High | Product recommendation engine |
| **Supporting** | Necessary but not unique | Medium | Order management |
| **Generic** | Commodity, buy/outsource | Low | Email sending, payments |

### Identification Questions

1. What makes us different from competitors? → **Core**
2. What do we need but isn't our specialty? → **Supporting**
3. What does everyone need the same way? → **Generic**

### Example: E-Commerce

```mermaid
flowchart TB
    subgraph Subdomains["Subdomains"]
        subgraph Core["CORE"]
            C1["Product search & recommendations"]
            C2["Pricing engine"]
            C3["Personalization"]
        end
        subgraph Supporting["SUPPORTING"]
            S1["Order management"]
            S2["Inventory"]
            S3["Customer support"]
            S4["Reporting"]
        end
        subgraph Generic["GENERIC"]
            G1["Authentication (Auth0)"]
            G2["Payments (Stripe)"]
            G3["Email (SendGrid)"]
            G4["File storage (S3)"]
        end
    end

    Core --> CoreStrat["Build in-house\nBest developers"]
    Supporting --> SuppStrat["Build or buy\nSolid but simple"]
    Generic --> GenStrat["Use third-party\nDon't reinvent"]

    style Core fill:#ef4444,stroke:#dc2626,color:white
    style Supporting fill:#f59e0b,stroke:#d97706,color:white
    style Generic fill:#6b7280,stroke:#4b5563,color:white
```

---

## Context Mapping

Describes relationships between bounded contexts.

### Relationship Patterns

#### Partnership
Two contexts succeed or fail together. Teams coordinate closely.

```mermaid
flowchart LR
    A["Context A"] <-->|"Partnership\nJoint planning\nShared success"| B["Context B"]

    style A fill:#3b82f6,stroke:#2563eb,color:white
    style B fill:#3b82f6,stroke:#2563eb,color:white
```

#### Shared Kernel
Two contexts share a subset of the domain model.

```mermaid
flowchart LR
    subgraph A["Context A"]
        SK["Shared Kernel"]
    end
    subgraph B["Context B"]
        B1[" "]
    end

    SK <-->|shared| B

    style A fill:#3b82f6,stroke:#2563eb,color:white
    style B fill:#10b981,stroke:#059669,color:white
    style SK fill:#f59e0b,stroke:#d97706,color:white
```

**Warning:** Shared kernels create coupling. Use sparingly.

#### Customer-Supplier
Upstream context provides what downstream needs.

```mermaid
flowchart LR
    U["Upstream\n(Supplier)"] -->|"Provides API"| D["Downstream\n(Customer)"]

    style U fill:#3b82f6,stroke:#2563eb,color:white
    style D fill:#10b981,stroke:#059669,color:white
```

#### Conformist
Downstream conforms to upstream's model with no negotiation power.

```mermaid
flowchart LR
    U["Upstream\n(Dictator)"] -->|"Take it or leave it"| D["Downstream\n(Conformist)\nUses their model"]

    style U fill:#ef4444,stroke:#dc2626,color:white
    style D fill:#6b7280,stroke:#4b5563,color:white
```

**Example:** Integrating with a third-party API (Stripe, AWS).

#### Anti-Corruption Layer (ACL)
Translation layer protecting your model from external models.

```mermaid
flowchart LR
    Ext["External\nContext"] --> ACL["ACL\nTranslator + Adapter"]
    ACL --> Your["Your\nContext"]

    ACL -.->|"Translates external\nmodel to your model"| Note[" "]

    style Ext fill:#ef4444,stroke:#dc2626,color:white
    style ACL fill:#f59e0b,stroke:#d97706,color:white
    style Your fill:#10b981,stroke:#059669,color:white
    style Note fill:none,stroke:none
```

**Use when:**
- Integrating with legacy systems
- Integrating with third-party APIs
- External model is messy or poorly designed

```typescript
// Anti-Corruption Layer Example
// infrastructure/external/stripe/stripe_payment_acl.ts

import Stripe from 'stripe';
import { Payment, PaymentStatus } from '@/domain/payment/payment';
import { Money } from '@/domain/shared/money';

export class StripePaymentACL {
  constructor(private readonly stripe: Stripe) {}

  async createPayment(payment: Payment): Promise<string> {
    const paymentIntent = await this.stripe.paymentIntents.create({
      amount: payment.amount.cents,
      currency: payment.amount.currency.toLowerCase(),
      metadata: {
        orderId: payment.orderId.value,
        customerId: payment.customerId.value,
      },
    });

    return paymentIntent.id;
  }

  translateStatus(stripeStatus: string): PaymentStatus {
    const mapping: Record<string, PaymentStatus> = {
      'requires_payment_method': PaymentStatus.Pending,
      'requires_confirmation': PaymentStatus.Pending,
      'requires_action': PaymentStatus.Pending,
      'processing': PaymentStatus.Processing,
      'succeeded': PaymentStatus.Completed,
      'canceled': PaymentStatus.Cancelled,
      'requires_capture': PaymentStatus.Authorized,
    };

    return mapping[stripeStatus] ?? PaymentStatus.Unknown;
  }

  translateWebhook(event: Stripe.Event): DomainEvent | null {
    switch (event.type) {
      case 'payment_intent.succeeded':
        const intent = event.data.object as Stripe.PaymentIntent;
        return new PaymentCompleted(
          PaymentId.from(intent.metadata.orderId),
          Money.fromCents(intent.amount, intent.currency.toUpperCase())
        );
      case 'payment_intent.payment_failed':
        return null;
      default:
        return null;
    }
  }
}
```

#### Open Host Service / Published Language
Expose a well-defined protocol for integration.

```mermaid
flowchart TB
    subgraph OHS["Open Host Service"]
        PL["Published Language\n(REST API, gRPC, Events Schema)"]
        BC["Your Bounded Context"]
    end

    PL --> A["Consumer A"]
    PL --> B["Consumer B"]
    PL --> C["Consumer C"]

    style OHS fill:#3b82f6,stroke:#2563eb,color:white
    style PL fill:#10b981,stroke:#059669,color:white
    style A fill:#6b7280,stroke:#4b5563,color:white
    style B fill:#6b7280,stroke:#4b5563,color:white
    style C fill:#6b7280,stroke:#4b5563,color:white
```

---

## Context Map Diagram

Visual representation of all bounded contexts and their relationships:

```mermaid
flowchart TB
    Identity["Identity Context\n(Generic - Auth0)"]
    Legacy["Legacy Catalog\n(Legacy)"]
    Sales["Sales Context\n(Core)"]
    Shipping["Shipping Context\n(Supporting)"]
    Billing["Billing Context\n(Supporting)"]
    Stripe["Stripe Gateway\n(Generic)"]

    Identity -->|Conformist| Sales
    Legacy -->|ACL| Sales
    Sales <-->|Customer-Supplier| Shipping
    Sales -->|Open Host Service| Billing
    Billing -->|Conformist| Stripe

    style Identity fill:#6b7280,stroke:#4b5563,color:white
    style Legacy fill:#9ca3af,stroke:#6b7280,color:white
    style Sales fill:#ef4444,stroke:#dc2626,color:white
    style Shipping fill:#f59e0b,stroke:#d97706,color:white
    style Billing fill:#f59e0b,stroke:#d97706,color:white
    style Stripe fill:#6b7280,stroke:#4b5563,color:white
```

---

## Integration Patterns

### Domain Events for Context Integration

```typescript
interface OrderPlaced {
  eventType: 'sales.order.placed';
  orderId: string;
  customerId: string;
  items: Array<{ productId: string; quantity: number; price: number }>;
  total: number;
  shippingAddress: Address;
  occurredAt: string;
}

class ShippingOrderPlacedHandler {
  async handle(event: OrderPlaced): Promise<void> {
    const shipment = Shipment.create({
      orderId: ShipmentOrderId.from(event.orderId),
      recipient: Recipient.fromAddress(event.shippingAddress),
      packages: this.calculatePackages(event.items),
    });

    await this.shipmentRepository.save(shipment);
  }
}

class BillingOrderPlacedHandler {
  async handle(event: OrderPlaced): Promise<void> {
    const invoice = Invoice.create({
      orderId: InvoiceOrderId.from(event.orderId),
      customerId: BillingCustomerId.from(event.customerId),
      lineItems: event.items.map(item => ({
        description: `Product ${item.productId}`,
        quantity: item.quantity,
        unitPrice: Money.fromNumber(item.price),
      })),
      total: Money.fromNumber(event.total),
    });

    await this.invoiceRepository.save(invoice);
  }
}
```

### Event Schema Registry

Define and version integration event schemas:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "$id": "https://api.company.com/events/sales/order-placed/v1.json",
  "title": "OrderPlaced",
  "description": "Published when an order is successfully placed",
  "type": "object",
  "required": ["eventType", "eventId", "orderId", "occurredAt"],
  "properties": {
    "eventType": { "const": "sales.order.placed" },
    "eventId": { "type": "string", "format": "uuid" },
    "orderId": { "type": "string", "format": "uuid" },
    "customerId": { "type": "string", "format": "uuid" },
    "total": { "type": "number", "minimum": 0 },
    "occurredAt": { "type": "string", "format": "date-time" }
  }
}
```

---

## Strategic Design Checklist

- [ ] Identify ubiquitous language terms with domain experts
- [ ] Map subdomains (core, supporting, generic)
- [ ] Define bounded context boundaries
- [ ] Document context map with relationships
- [ ] Design anti-corruption layers for external systems
- [ ] Define integration event schemas
- [ ] Ensure each context has its own data store

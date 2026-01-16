# TubaPay PHP SDK

A PHP SDK for integrating TubaPay BNPL (Buy Now, Pay Later) payment solutions.

## Requirements

- PHP 8.2 or higher
- Guzzle HTTP client 7.0+

## Installation

```bash
composer require core45/tubapay-php
```

## Quick Start

```php
use Core45\TubaPay\TubaPay;
use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\DTO\Customer;
use Core45\TubaPay\DTO\OrderItem;

// Create SDK instance
$tubapay = TubaPay::create(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    webhookSecret: 'your-webhook-secret',
    environment: Environment::Test, // or Environment::Production
);

// Create customer
$customer = new Customer(
    firstName: 'Jan',
    lastName: 'Kowalski',
    email: 'jan@example.com',
    phone: '519088975',
    street: 'Testowa',
    zipCode: '00-001',
    town: 'Warszawa',
);

// Create order item
$item = new OrderItem(
    name: 'Product Name',
    totalValue: 1000.00,
);

// 1. Get available installment options
$offer = $tubapay->offers()->createOffer(
    amount: 1000.00,
    customer: $customer,
    item: $item,
    externalRef: 'ORDER-123',
);

// Check available installments
$installments = $offer->getAvailableInstallments(); // [3, 6, 9, 12]

// 2. Create transaction with selected installments
$transaction = $tubapay->transactions()->createTransaction(
    customer: $customer,
    item: $item,
    installments: 6,
    callbackUrl: 'https://yoursite.com/webhook',
    externalRef: 'ORDER-123',
);

// Redirect customer to payment page
header('Location: ' . $transaction->transactionLink);
```

## Webhook Handling

```php
use Core45\TubaPay\DTO\Webhook\StatusChangedPayload;
use Core45\TubaPay\DTO\Webhook\PaymentPayload;
use Core45\TubaPay\DTO\Webhook\InvoicePayload;
use Core45\TubaPay\Security\SignatureVerifier;

// Get webhook data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TUBAPAY_CHECKSUM'] ?? null;

try {
    // Verify and parse webhook
    $webhook = $tubapay->verifyAndParseWebhook($payload, $signature);

    if ($webhook instanceof StatusChangedPayload) {
        // Handle status change
        $orderId = $webhook->externalRef;
        $status = $webhook->agreementStatus;

        if ($webhook->isAccepted()) {
            // Payment accepted - fulfill order
        } elseif ($webhook->isRejected()) {
            // Payment rejected - cancel order
        }
    }

    if ($webhook instanceof PaymentPayload) {
        // Handle payment notification
        $amount = $webhook->paymentAmount;
        $title = $webhook->paymentTitle;
    }

    if ($webhook instanceof InvoicePayload) {
        // Handle recurring invoice request
        $position = $webhook->getFirstPosition();
        $amount = $position?->totalAmount;
    }

    // Return 200 OK
    http_response_code(200);
    echo 'OK';
} catch (\Exception $e) {
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}
```

## Agreement Statuses

| Status | Description | Action |
|--------|-------------|--------|
| `draft` | Initial state | Wait |
| `registered` | Application submitted | Wait |
| `signed` | Documents signed | Wait |
| `accepted` | Approved - payment will be made | Fulfill order |
| `rejected` | Application rejected | Cancel order |
| `canceled` | Canceled by customer | Cancel order |
| `terminated` | Terminated by system | Cancel order |
| `withdrew` | Customer withdrew | Cancel order |
| `repaid` | Fully repaid | Order complete |
| `closed` | Agreement closed | Order complete |

```php
use Core45\TubaPay\Enum\AgreementStatus;

$status = AgreementStatus::from('accepted');

$status->isPending();    // true for draft, registered, signed
$status->isSuccessful(); // true for accepted, repaid, closed
$status->isFailed();     // true for rejected, canceled, terminated, withdrew
$status->isFinal();      // true if no more status changes expected
$status->willBePaid();   // true if merchant will receive payment
```

## Custom Token Storage

For production web applications, implement persistent token storage:

```php
use Core45\TubaPay\Http\TokenStorageInterface;

class DatabaseTokenStorage implements TokenStorageInterface
{
    public function getToken(): ?string
    {
        // Retrieve from database
    }

    public function setToken(string $token, int $expiresIn): void
    {
        // Store in database with expiration
    }

    public function hasValidToken(): bool
    {
        // Check if stored token exists and not expired
    }

    public function clearToken(): void
    {
        // Delete from database
    }
}

// Use custom storage
$tubapay = TubaPay::create(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    webhookSecret: 'your-webhook-secret',
    tokenStorage: new DatabaseTokenStorage(),
);
```

## Error Handling

```php
use Core45\TubaPay\Exception\TubaPayException;
use Core45\TubaPay\Exception\AuthenticationException;
use Core45\TubaPay\Exception\ValidationException;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\WebhookVerificationException;

try {
    $offer = $tubapay->offers()->createOffer(...);
} catch (AuthenticationException $e) {
    // Invalid credentials or token expired
} catch (ValidationException $e) {
    // Invalid request data
    $errors = $e->getErrors();
} catch (ApiException $e) {
    // API error
    $requestId = $e->getRequestId();
} catch (TubaPayException $e) {
    // Generic SDK error
    $context = $e->getContext();
}
```

## Testing

```bash
composer test
composer phpstan
```

## License

MIT License. See LICENSE file for details.

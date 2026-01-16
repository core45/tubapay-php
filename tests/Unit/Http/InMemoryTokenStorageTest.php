<?php

declare(strict_types=1);

namespace Core45\TubaPay\Tests\Unit\Http;

use Core45\TubaPay\Http\InMemoryTokenStorage;
use Core45\TubaPay\Http\TokenStorageInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryTokenStorageTest extends TestCase
{
    #[Test]
    public function test_implements_interface(): void
    {
        $storage = new InMemoryTokenStorage();

        $this->assertInstanceOf(TokenStorageInterface::class, $storage);
    }

    #[Test]
    public function test_initial_state_has_no_token(): void
    {
        $storage = new InMemoryTokenStorage();

        $this->assertNull($storage->getToken());
        $this->assertFalse($storage->hasValidToken());
    }

    #[Test]
    public function test_set_and_get_token(): void
    {
        $storage = new InMemoryTokenStorage();

        $storage->setToken('test-token-123', 3600);

        $this->assertSame('test-token-123', $storage->getToken());
        $this->assertTrue($storage->hasValidToken());
    }

    #[Test]
    public function test_clear_token(): void
    {
        $storage = new InMemoryTokenStorage();

        $storage->setToken('test-token', 3600);
        $this->assertTrue($storage->hasValidToken());

        $storage->clearToken();

        $this->assertNull($storage->getToken());
        $this->assertFalse($storage->hasValidToken());
    }

    #[Test]
    public function test_expired_token_is_invalid(): void
    {
        $storage = new InMemoryTokenStorage();

        // Set token that expires immediately (0 seconds)
        $storage->setToken('expired-token', 0);

        $this->assertFalse($storage->hasValidToken());
        $this->assertNull($storage->getToken());
    }

    #[Test]
    public function test_token_near_expiration_is_invalid(): void
    {
        $storage = new InMemoryTokenStorage();

        // Set token that expires in 30 seconds (within 60 second buffer)
        $storage->setToken('near-expiry-token', 30);

        // Should be invalid due to the 60-second buffer
        $this->assertFalse($storage->hasValidToken());
        $this->assertNull($storage->getToken());
    }

    #[Test]
    public function test_token_with_sufficient_time_is_valid(): void
    {
        $storage = new InMemoryTokenStorage();

        // Set token that expires in 120 seconds (beyond 60 second buffer)
        $storage->setToken('valid-token', 120);

        $this->assertTrue($storage->hasValidToken());
        $this->assertSame('valid-token', $storage->getToken());
    }

    #[Test]
    public function test_overwrite_token(): void
    {
        $storage = new InMemoryTokenStorage();

        $storage->setToken('token-1', 3600);
        $this->assertSame('token-1', $storage->getToken());

        $storage->setToken('token-2', 7200);
        $this->assertSame('token-2', $storage->getToken());
    }
}

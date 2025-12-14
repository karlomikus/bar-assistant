<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Prometheus\CollectorRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_returns_prometheus_format(): void
    {
        // Allow all IPs for testing
        config(['bar-assistant.metrics.allowed_ips' => ['*']]);

        $response = $this->get('/metrics');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }

    // public function test_metrics_endpoint_blocked_when_disabled(): void
    // {
    //     config(['bar-assistant.metrics.enabled' => false]);
    //     config(['bar-assistant.metrics.allowed_ips' => ['*']]);

    //     $response = $this->get('/metrics');

    //     $response->assertNotFound();
    // }

    public function test_metrics_endpoint_blocked_when_ip_not_whitelisted(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['192.168.1.1']]);

        $response = $this->get('/metrics');

        $response->assertForbidden();
    }

    public function test_metrics_endpoint_accessible_with_whitelisted_ip(): void
    {
        // Mock the request IP
        config(['bar-assistant.metrics.allowed_ips' => ['127.0.0.1']]);

        $response = $this->get('/metrics', [
            'REMOTE_ADDR' => '127.0.0.1'
        ]);

        $response->assertOk();
    }

    public function test_metrics_endpoint_accessible_with_wildcard(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['*']]);

        $response = $this->get('/metrics');

        $response->assertOk();
    }

    public function test_metrics_endpoint_returns_404_when_no_ips_configured(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => []]);

        $response = $this->get('/metrics');

        $response->assertNotFound();
    }

    public function test_metrics_endpoint_returns_empty_string_on_registry_error(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['*']]);

        // Create a mock registry that will throw an exception
        $mockRegistry = $this->createMock(CollectorRegistry::class);
        $mockRegistry->method('getMetricFamilySamples')
            ->willThrowException(new \Exception('Test exception'));

        $this->app->instance(CollectorRegistry::class, $mockRegistry);

        $response = $this->get('/metrics');

        $response->assertOk();
        $response->assertSeeText('', false);
    }

    public function test_metrics_endpoint_allows_private_ips_in_local_environment(): void
    {
        $this->app['env'] = 'local';
        config(['bar-assistant.metrics.allowed_ips' => []]);

        // Private IP ranges
        $response = $this->get('/metrics', [
            'REMOTE_ADDR' => '192.168.1.1'
        ]);

        $response->assertOk();
    }

    public function test_metrics_endpoint_supports_multiple_whitelisted_ips(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['192.168.1.1', '10.0.0.1', '127.0.0.1']]);

        $response = $this->get('/metrics', [
            'REMOTE_ADDR' => '127.0.0.1'
        ]);

        $response->assertOk();
    }

    public function test_metrics_endpoint_filters_empty_ip_entries(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['', '127.0.0.1', '']]);

        $response = $this->get('/metrics', [
            'REMOTE_ADDR' => '127.0.0.1'
        ]);

        $response->assertOk();
    }

    public function test_metrics_endpoint_content_type_header(): void
    {
        config(['bar-assistant.metrics.allowed_ips' => ['*']]);

        $response = $this->get('/metrics');

        $response->assertOk();
        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
    }
}

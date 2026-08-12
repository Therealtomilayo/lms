<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class BootstrapIntegrationTest extends TestCase
{
    public function testHealthCheckEndpoint(): void
    {
        $router = new Router();
        $router->get('/health', function (Request $req): Response {
            return Response::json([
                'status' => 'healthy',
                'app' => Config::get('app.name'),
            ]);
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/health']);
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('healthy', $data['status']);
    }
}

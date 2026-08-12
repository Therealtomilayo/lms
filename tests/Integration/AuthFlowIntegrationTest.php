<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AuthController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthFlowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepository;
    private AuthService $authService;
    private AuthController $authController;
    private Router $router;

    protected function setUp(): void
    {
        Session::destroy();
        Session::start();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(30) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `user_roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `role` VARCHAR(30) NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                UNIQUE (`user_id`, `role`)
            );

            CREATE TABLE `user_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `session_hash` VARCHAR(64) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `last_seen_at` DATETIME NOT NULL,
                `user_agent_hash` VARCHAR(64) NULL,
                `ip_hash` VARCHAR(64) NULL,
                `revoked_at` DATETIME NULL
            );

            CREATE TABLE `password_reset_tokens` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `token_hash` VARCHAR(64) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `requested_ip` VARCHAR(45) NULL,
                `used_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL
            );
        ");

        $this->userRepository = new UserRepository($this->pdo);
        $this->authService = new AuthService($this->userRepository);
        $this->authController = new AuthController($this->authService);

        $this->router = new Router();
        $this->router->get('/login', [$this->authController, 'showLogin']);
        $this->router->post('/login', [$this->authController, 'login'], [CsrfMiddleware::class]);
        $this->router->get('/forgot-password', [$this->authController, 'showForgotPassword']);
        $this->router->post('/forgot-password', [$this->authController, 'forgotPassword'], [CsrfMiddleware::class]);
        $this->router->get('/reset-password/{token}', [$this->authController, 'showResetPassword']);
        $this->router->post('/reset-password', [$this->authController, 'resetPassword'], [CsrfMiddleware::class]);
    }

    public function testShowLoginFormRendersHtml(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/login']);
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Sign in to your account', $response->getContent());
        $this->assertStringContainsString('name="_csrf_token"', $response->getContent());
    }

    public function testLoginEndpointRedirectsToDashboard(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-flow-1',
            'name' => 'Principal Admin',
            'email' => 'principal@claret.edu',
            'password_hash' => password_hash('Pass12345!', PASSWORD_DEFAULT),
        ], ['admin']);

        $token = Csrf::generate();
        $request = new Request([], [
            '_csrf_token' => $token,
            'email' => 'principal@claret.edu',
            'password' => 'Pass12345!',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login']);

        $response = $this->router->dispatch($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admin/dashboard', $response->getHeader('Location'));
    }

    public function testForgotPasswordAndResetFlow(): void
    {
        $user = $this->userRepository->create([
            'uuid' => 'u-flow-2',
            'name' => 'Teacher Rose',
            'email' => 'rose@claret.edu',
            'password_hash' => password_hash('OldRosePass!', PASSWORD_DEFAULT),
        ], ['teacher']);

        // 1. Submit Forgot Password Form
        $csrf = Csrf::generate();
        $forgotReq = new Request([], [
            '_csrf_token' => $csrf,
            'email' => 'rose@claret.edu',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/forgot-password']);

        $forgotResp = $this->router->dispatch($forgotReq);
        $this->assertSame(302, $forgotResp->getStatusCode());
        $this->assertSame('/forgot-password', $forgotResp->getHeader('Location'));

        // Retrieve generated token from DB
        $stmt = $this->pdo->query("SELECT * FROM password_reset_tokens WHERE user_id = {$user->id} ORDER BY id DESC LIMIT 1");
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($tokenRecord);

        // 2. View Reset Password Form using known token
        // Let's create a known plain token for testing
        $plainToken = 'test-token-123456';
        $this->pdo->exec("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES ({$user->id}, '" . hash('sha256', $plainToken) . "', '" . date('Y-m-d H:i:s', time() + 3600) . "', '" . date('Y-m-d H:i:s') . "')");

        $viewResetReq = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/reset-password/' . $plainToken]);
        $viewResetResp = $this->router->dispatch($viewResetReq);
        $this->assertSame(200, $viewResetResp->getStatusCode());
        $this->assertStringContainsString('Create new password', $viewResetResp->getContent());

        // 3. Submit New Password
        $resetReq = new Request([], [
            '_csrf_token' => $csrf,
            'token' => $plainToken,
            'password' => 'NewRosePass123!',
            'password_confirmation' => 'NewRosePass123!',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/reset-password']);

        $resetResp = $this->router->dispatch($resetReq);
        $this->assertSame(302, $resetResp->getStatusCode());
        $this->assertSame('/login', $resetResp->getHeader('Location'));

        // 4. Verify Login with New Password
        $loginReq = new Request([], [
            '_csrf_token' => $csrf,
            'email' => 'rose@claret.edu',
            'password' => 'NewRosePass123!',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login']);

        $loginResp = $this->router->dispatch($loginReq);
        $this->assertSame(302, $loginResp->getStatusCode());
        $this->assertSame('/teacher/dashboard', $loginResp->getHeader('Location'));
    }
}

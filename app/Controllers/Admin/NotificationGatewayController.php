<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Config;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\NotificationLogRepository;
use App\Repositories\ParentRepository;
use App\Services\NotificationService;

/**
 * Controller for Admin External Notification Gateway, Drivers Health, Test Console, and Delivery Auditing
 */
class NotificationGatewayController extends Controller
{
    private NotificationService $notificationService;
    private NotificationLogRepository $logRepo;
    private AcademicRepository $academicRepo;
    private ParentRepository $parentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?NotificationService $notificationService = null,
        ?NotificationLogRepository $logRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?ParentRepository $parentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->notificationService = $notificationService ?? new NotificationService();
        $this->logRepo = $logRepo ?? new NotificationLogRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
    }

    /**
     * Gateway Dashboard & Status Center
     * Route: GET /admin/notifications/gateway
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator privileges required.');
        }

        $stats = $this->logRepo->getStats();
        $recentLogs = $this->logRepo->getRecent(10);

        $driverInfo = [
            'mail' => [
                'mailer' => (string)Config::get('mail.mailer', 'log'),
                'host' => (string)Config::get('mail.host', '127.0.0.1'),
                'port' => (int)Config::get('mail.port', 587),
                'from_address' => (string)Config::get('mail.from_address', 'notifications@claret.edu.ng'),
                'from_name' => (string)Config::get('mail.from_name', 'Claret International School'),
            ],
            'sms' => [
                'gateway' => (string)Config::get('sms.gateway', 'log'),
                'sender_id' => (string)Config::get('termii.sender_id', 'ClaretSch'),
                'termii_configured' => !empty(Config::get('termii.api_key')),
                'twilio_configured' => !empty(Config::get('twilio.sid')),
            ],
            'whatsapp' => [
                'gateway' => (string)Config::get('whatsapp.gateway', 'log'),
            ],
        ];

        return Response::html($this->render('admin/notifications/gateway', [
            'title' => 'External Notification Gateway — Claret LMS Admin',
            'headerTitle' => 'External Notification Gateway',
            'headerSubtitle' => 'Configure and monitor outbound multi-channel communications (Email, SMS, WhatsApp) across parent, student, and faculty cohorts.',
            'user' => $userContext,
            'stats' => $stats,
            'driverInfo' => $driverInfo,
            'recentLogs' => $recentLogs,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ], 'layouts/admin'));
    }

    /**
     * Dispatch a test transmission through chosen channel
     * Route: POST /admin/notifications/gateway/test
     */
    public function sendTest(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator privileges required.');
        }

        $channel = trim((string)$request->post('channel', 'email'));
        $recipient = trim((string)$request->post('recipient', ''));
        $message = trim((string)$request->post('message', ''));

        if (empty($recipient)) {
            return $this->redirectWithError('/admin/notifications/gateway', 'Recipient email address or phone number is required.');
        }

        if (empty($message)) {
            $message = "This is an official gateway diagnostic transmission from Claret International School LMS dispatched at " . date('Y-m-d H:i:s') . ".";
        }

        $result = $this->notificationService->sendTestNotification($channel, $recipient, $message);

        if ($result->success) {
            $ref = $result->messageId ? " (Reference: {$result->messageId})" : '';
            return $this->redirectWithSuccess(
                '/admin/notifications/gateway',
                "Test " . strtoupper($channel) . " message sent successfully via " . strtoupper($result->gateway) . " driver!{$ref}"
            );
        }

        return $this->redirectWithError(
            '/admin/notifications/gateway',
            "Dispatch failed: " . ($result->error ?: 'Unknown error')
        );
    }

    /**
     * Outbound Notification Logs Audit Trail
     * Route: GET /admin/notifications/logs
     */
    public function logs(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator privileges required.');
        }

        $channel = $request->get('channel') ?: null;
        $status = $request->get('status') ?: null;
        $eventType = $request->get('event_type') ?: null;
        $search = $request->get('search') ?: null;

        $logs = $this->logRepo->getRecent(100, $channel, $status, $eventType, $search);
        $stats = $this->logRepo->getStats();

        return Response::html($this->render('admin/notifications/logs', [
            'title' => 'Notification Delivery Logs — Claret LMS Admin',
            'headerTitle' => 'Outbound Notification Audit Logs',
            'headerSubtitle' => 'Historical delivery audit records, gateway references, and status codes for all automated and manual transmissions.',
            'user' => $userContext,
            'logs' => $logs,
            'stats' => $stats,
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
            'selectedEventType' => $eventType,
            'search' => $search,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ], 'layouts/admin'));
    }

    /**
     * Broadcast message to selected cohort
     * Route: POST /admin/notifications/broadcast
     */
    public function broadcast(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator privileges required.');
        }

        $channel = trim((string)$request->post('channel', 'email'));
        $targetGroup = trim((string)$request->post('target_group', 'all_parents'));
        $title = trim((string)$request->post('title', ''));
        $body = trim((string)$request->post('body', ''));

        if (empty($title) || empty($body)) {
            return $this->redirectWithError('/admin/notifications/gateway', 'Title and message body are required for broadcast.');
        }

        // Collect recipients based on group
        $recipients = [];
        if ($targetGroup === 'all_parents') {
            $parents = $this->parentRepo->getAllWithDetails(500);
            foreach ($parents as $p) {
                if ($channel === 'email' && !empty($p->user?->email)) {
                    $recipients[] = ['id' => $p->userId, 'recipient' => $p->user->email, 'name' => $p->user->name];
                } elseif (($channel === 'sms' || $channel === 'whatsapp') && !empty($p->emergencyContactPhone)) {
                    $recipients[] = ['id' => $p->userId, 'recipient' => $p->emergencyContactPhone, 'name' => $p->user?->name ?? 'Parent'];
                }
            }
        }

        if (empty($recipients)) {
            return $this->redirectWithError('/admin/notifications/gateway', 'No valid contact records found for the chosen target audience.');
        }

        $sentCount = 0;
        foreach ($recipients as $rec) {
            if ($channel === 'email') {
                $html = $this->notificationService->renderEmailTemplate('generic_bulletin', [
                    'title' => $title,
                    'bodyText' => $body,
                ], $title);
                $res = $this->notificationService->sendEmail($rec['recipient'], $title, $html, $body, $rec['id'], 'custom_broadcast');
            } elseif ($channel === 'sms') {
                $res = $this->notificationService->sendSms($rec['recipient'], "{$title}: {$body}", $rec['id'], 'custom_broadcast');
            } elseif ($channel === 'whatsapp') {
                $res = $this->notificationService->sendWhatsApp($rec['recipient'], "*{$title}*\n\n{$body}", $rec['id'], 'custom_broadcast');
            }
            if ($res->success) {
                $sentCount++;
            }
        }

        return $this->redirectWithSuccess(
            '/admin/notifications/logs',
            "Broadcast processed: {$sentCount} of " . count($recipients) . " recipients reached via " . strtoupper($channel) . "."
        );
    }
}

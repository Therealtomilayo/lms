<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ParentService;

/**
 * Controller for Child Switching and Detailed Single-Child Academic Overview
 */
class ChildController extends Controller
{
    private ParentService $parentService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ParentService $parentService = null
    ) {
        parent::__construct($authenticator);
        $this->parentService = $parentService ?? new ParentService();
    }

    /**
     * Set active selected child in session after validating ownership.
     * Route: POST /parent/children/{studentId}/select
     */
    public function select(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->input('student_id', 0));
        }

        try {
            // Strictly validate ownership before storing in session
            $student = $this->parentService->validateChildAccess($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $student->id);

            $redirectUrl = (string)($request->input('redirect_to') ?? "/parent/children/{$student->id}");
            if (!str_starts_with($redirectUrl, '/parent')) {
                $redirectUrl = "/parent/children/{$student->id}";
            }

            $this->setFlash($request, 'success', "Switched active student view to {$student->name}.");
            return Response::redirect($redirectUrl);
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Show detailed academic overview and status for a linked child.
     * Route: GET /parent/children/{studentId}
     */
    public function show(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            // Re-validate parent-child access predicate
            $overviewData = $this->parentService->getChildOverview($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $studentId);

            $children = $this->parentService->getLinkedChildren($userContext);

            return Response::html($this->render('parent/children/show', array_merge($overviewData, [
                'title' => "{$overviewData['student']->name} — Student Profile & Academic Overview",
                'children' => $children,
                'selectedChild' => $overviewData['student'],
                'user' => $userContext,
                'csrf_token' => Session::get('_csrf_token', ''),
            ]), 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }
}

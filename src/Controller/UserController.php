<?php

namespace App\Controller;

use App\Service\User\UserManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private UserManagementService $managementService
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->managementService->indexUsers($request->query->all()),
        ]);
    }

    #[Route('/user/block', name: 'app_user_block', methods: ['POST'])]
    public function blockUser(Request $request): Response
    {
        return $this->returnResponse(
            $this->managementService->blockUser(
                $request->request->all('userIds')
            )
        );
    }

    #[Route('/user/unblock', name: 'app_user_unblock', methods: ['POST'])]
    public function unblockUser(Request $request): Response
    {
        return $this->returnResponse(
            $this->managementService->unblockUser(
                $request->request->all('userIds')
            )
        );
    }

    #[Route('/user/delete', name: 'app_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request): Response
    {
        return $this->returnResponse(
            $this->managementService->deleteUser(
                (array) $request->request->all('userIds'),
                $request->getSession()
            )
        );
    }

    #[Route('/user/activate/{token}', name: 'app_user_activate', methods: ['GET'])]
    public function activateUser(string $token): Response
    {
        return $this->returnResponse(
            $this->managementService->activateUser($token)
        );
    }

    private function returnResponse(bool $result): Response
    {
        return $result
            ? $this->json(['status' => 'ok'])
            : $this->json(['status' => 'error'], Response::HTTP_BAD_REQUEST);
    }
}

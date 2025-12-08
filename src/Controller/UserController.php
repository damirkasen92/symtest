<?php

namespace App\Controller;

use App\Service\User\UserManagementService;
use App\Dto\UserManagementDto;
use App\Exception\UserManagementException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private UserManagementService $managementService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->managementService->indexUsers($request->query->all()),
        ]);
    }

    private function hasErrors(UserManagementDto $dto)
    {
        $errors = $this->validator->validate($dto);

        if (\count($errors) === 0)
            return false;

        return true;
    }

    #[Route('/user/block', name: 'app_user_block', methods: ['POST'])]
    public function blockUser(Request $request): Response
    {
        $dto = UserManagementDto::fromRequest($request);

        if ($this->hasErrors($dto))
            return $this->json(['status' => 'error'], Response::HTTP_BAD_REQUEST);

        try {
            $this->managementService->blockUser($dto->userIds);
        } catch (UserManagementException $e) {
            return $this->json(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/user/unblock', name: 'app_user_unblock', methods: ['POST'])]
    public function unblockUser(Request $request): Response
    {
        $dto = UserManagementDto::fromRequest($request);

        if ($this->hasErrors($dto))
            return $this->json(['status' => 'error'], Response::HTTP_BAD_REQUEST);

        try {
            $this->managementService->unblockUser($dto->userIds);
        } catch (UserManagementException $e) {
            return $this->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/user/delete', name: 'app_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request): Response
    {
        $dto = UserManagementDto::fromRequest($request);

        if ($this->hasErrors($dto))
            return $this->json(['status' => 'error'], Response::HTTP_BAD_REQUEST);

        try {
            $this->managementService->deleteUser($dto->userIds);
        } catch (UserManagementException $e) {
            return $this->json(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/user/activate/{token}', name: 'app_user_activate', methods: ['GET'])]
    public function activateUser(string $token): Response
    {
        if ($this->managementService->activateUser($token))
            return $this->render('user/success_user_activation.html.twig');

        $this->addFlash('error', 'Something went wrong!');
        return $this->redirectToRoute('app_index');
    }
}

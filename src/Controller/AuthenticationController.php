<?php

namespace App\Controller;

use App\Service\User\UserAuthenticationService;
use Src\Dto\UserRegistrationDto;
use Src\Exception\RegistrationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthenticationController extends AbstractController
{
    public function __construct(
        private UserAuthenticationService $userAuthenticationService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/login', name: 'app_login_show', methods: ['GET'])]
    public function loginShow(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error)
            $this->addFlash('error', $error->getMessageKey());

        return $this->render('user/login.html.twig');
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(): void
    {
    }

    #[Route('/register', name: 'app_register_show', methods: ['GET'])]
    public function registerShow(): Response
    {
        return $this->render('user/register.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $dto = UserRegistrationDto::fromRequest($request);

        if ($this->hasErrors($dto))
            return $this->redirectToRoute('app_register');

        try {
            $this->userAuthenticationService->register($dto);
            $this->addFlash('success', 'Registration successful. Welcome!');
        } catch (RegistrationException $e) {
            $this->addFlash(
                'error',
                $e->getMessage()
            );

            return $this->redirectToRoute('app_register');
        }

        return $this->redirectToRoute('app_index');
    }

    private function hasErrors($dto): bool
    {
        $errors = $this->validator->validate($dto);

        if (\count($errors) === 0)
            return false;

        foreach ($errors as $error) {
            $this->addFlash(
                'error',
                \gettype($error) === 'object' ? $error->getMessage() : $error
            );
        }

        return true;
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }
}

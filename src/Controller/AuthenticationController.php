<?php

namespace App\Controller;

use App\Service\User\UserAuthenticationService;
use App\Service\User\UserManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthenticationController extends AbstractController
{
    public function __construct(
        private UserAuthenticationService $userAuthenticationService
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

    // I think about DTO pattern, but for this simple case... I don't know
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, Security $security): Response
    {
        $name = $request->request->getString('name');
        $email = $request->request->getString('email');
        $plainPassword = $request->request->getString('password');

        $result = $this->userAuthenticationService
            ->register($name, $email, $plainPassword);

        // eehhhh..
        if (!$result['success']) {
            foreach ($result['errors'] as $error) {
                $this->addFlash('error', 
                    gettype($error) === 'object' ? $error->getMessage() : $error
                );
            }

            return $this->redirectToRoute('app_register');
        }

        $security->login($result['user']);
        $this->addFlash('success', 'Registration successful. Welcome!');
        
        return $this->redirectToRoute('app_index');
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Symfony will handle the logout process i guess
    }
}

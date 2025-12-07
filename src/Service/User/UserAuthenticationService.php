<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAuthenticationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private MailerInterface $mailer,
        private ValidatorInterface $validator,
        private RequestStack $requestStack
    ) {
    }

    // I think about DTO pattern, but for this simple case... I don't know
    public function register(string $name, string $email, string $plainPassword): array
    {
        $user = new User();

        $user->setName($name);
        $user->setEmail($email);
        $user->setPlainPassword($plainPassword);

        $hashedPassword = $this->hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $verificationToken = $this->generateVerificationToken();
        $user->setVerificationToken($verificationToken);

        $errors = $this->validator->validate($user);

        if (\count($errors) > 0)
            return ['success' => false, 'errors' => $errors];

        try {
            $this->em->persist($user);
            $this->em->flush();

            $this->sendVerificationEmail(
                $this->mailer,
                $email,
                $verificationToken
            );

            return ['success' => true, 'errors' => [], 'user' => $user];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['An error occurred during registration. Please try again.']];
        }
    }

    // simple random token generator
    private function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    // queued email sending
    private function sendVerificationEmail(MailerInterface $mailer, string $emailAddress, string $verificationToken): void
    {
        // or use url generator
        $email = (new Email())
            ->from('damir.kasen92@gmail.com')
            ->to($emailAddress)
            ->subject('Please confirm your email address')
            ->html("<p>Thanks for registering. 
                Please confirm your email by clicking 
                <a href='https://{$this->requestStack->getCurrentRequest()->getHost()}/user/activate/{$verificationToken}'>here</a>.
                </p>"
            );

        $mailer->send($email);
    }

}
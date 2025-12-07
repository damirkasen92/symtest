<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
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
        private RequestStack $requestStack,
        private FlashBagInterface $flashBag,
        private Security $security
    ) {
    }

    // I think about DTO pattern, but for this simple case... I don't know
    /**
     * Summary of register
     * @param string $name
     * @param string $email
     * @param string $plainPassword
     * @throws Exception
     * @return void
     */
    public function register(string $name, string $email, string $plainPassword): void
    {
        $user = new User();

        $user->setName($name);
        $user->setEmail($email);
        $user->setPlainPassword($plainPassword);

        $hashedPassword = $this->hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $verificationToken = $this->generateVerificationToken();
        $user->setVerificationToken($verificationToken);

        $this->handleRegisterErrors($user);
        $this->persistRegisterData($user, $email, $verificationToken);
    }

    /**
     * Summary of handleRegisterErrors
     * @param User $user
     * @throws Exception
     * @return void
     */
    private function handleRegisterErrors(User $user): void
    {
        $errors = $this->validator->validate($user);

        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $this->flashBag->add(
                    'error',
                    \gettype($error) === 'object' ? $error->getMessage() : $error
                );
            }

            throw new Exception('Registration error');
        }
    }

    /**
     * Summary of persistRegisterData
     * @param User $user
     * @param string $email
     * @param string $verificationToken
     * @throws Exception
     * @return void
     */
    private function persistRegisterData(User $user, string $email, string $verificationToken): void
    {
        try {
            $this->em->persist($user);
            $this->em->flush();

            $this->sendVerificationEmail(
                $this->mailer,
                $email,
                $verificationToken
            );

            $this->security->login($user);
            $this->flashBag->add('success', 'Registration successful. Welcome!');
        } catch (Exception $e) {
            $this->flashBag->add(
                'error',
                'An error occurred during registration. Please try again.'
            );

            throw new Exception('An error occurred during registration. Please try again.');
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
            ->html(
                "<p>Thanks for registering. 
                Please confirm your email by clicking 
                <a href='https://{$this->requestStack->getCurrentRequest()->getHost()}/user/activate/{$verificationToken}'>here</a>.
                </p>"
            );

        $mailer->send($email);
    }

}
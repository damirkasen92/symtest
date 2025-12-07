<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Src\Dto\UserRegistrationDto;
use Src\Exception\RegistrationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
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
        private Security $security,
        private Session $session
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
    public function register(UserRegistrationDto $dto): void
    {
        $user = new User();

        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setPlainPassword($dto->password);

        $hashedPassword = $this->hasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);

        $verificationToken = $this->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $this->persistRegisterData($user);
    }

    /**
     * Summary of persistRegisterData
     * @param User $user
     * @param string $email
     * @param string $verificationToken
     * @throws Exception
     * @return void
     */
    private function persistRegisterData(User $user): void
    {
        try {
            $this->em->persist($user);
            $this->em->flush();

            $this->sendVerificationEmail($user);
            $this->security->login($user);
        } catch (Exception $e) {
            throw new RegistrationException('An error occurred during registration. Please try again.');
        }
    }

    // simple random token generator
    private function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    // queued email sending
    private function sendVerificationEmail(User $user): void
    {
        // or use url generator
        $email = new Email()
            ->from('damir.kasen92@gmail.com')
            ->to($user->getEmail())
            ->subject('Please confirm your email address')
            ->html(
                "<p>Thanks for registering. 
                Please confirm your email by clicking 
                <a href='https://{$this->requestStack->getCurrentRequest()->getHost()}/user/activate/{$user->getVerificationToken()}'>here</a>.
                </p>"
            );

        $this->mailer->send($email);
    }

}
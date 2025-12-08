<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Dto\UserRegistrationDto;
use App\Exception\RegistrationException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAuthenticationService
{
    private const TOKEN_SIZE = 16;

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private MailerInterface $mailer,
        private ValidatorInterface $validator,
        private RequestStack $requestStack,
        private Security $security,
        private string $mailerFrom
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
        $user->setPlainPassword($dto->plainPassword);

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
        return bin2hex(random_bytes(self::TOKEN_SIZE));
    }

    // queued email sending
    private function sendVerificationEmail(User $user): void
    {
        // or use url generator
        $email = new TemplatedEmail()
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Please confirm your email address')
            ->htmlTemplate("emails/user-registration.html.twig")
            ->context([
                'host' => $this->requestStack->getCurrentRequest()->getHost(),
                'token' => $user->getVerificationToken()
            ]);

        $this->mailer->send($email);
    }

}
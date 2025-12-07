<?php

namespace App\Service\User;

use App\Entity\User;
use App\Enum\User\Status;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Src\Exception\UserManagementException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

// KISS, YAGNI
// But i think i overcomplicated it ( lack of experience... )

class UserManagementService
{
    private EntityRepository $userRepository;

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private SessionInterface $session
    ) {
        // maybe it is not efficient, because it will be loaded on every call
        // It needs in each "post" method here in this class
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function indexUsers(array $queryOrder): array
    {
        $query = $this->userRepository->createQueryBuilder('u')
            ->leftJoin(
                'u.activities',
                'a',
                'WITH',
                'a.id = (SELECT MAX(a2.id) 
                    FROM App\Entity\UserActivity a2
                    WHERE a2.user = u.id
                )'
            )
            ->addSelect('a');

        // i made it simple, because there are few fields to order by
        $query = $this->getOrdering($query, $queryOrder);

        return $query
            ->getQuery()
            ->getResult();
    }

    private function getOrdering(QueryBuilder $queryBuilder, array $queryOrder): QueryBuilder
    {
        if (empty($queryOrder))
            return $queryBuilder;

        $prefixTable = key($queryOrder) === 'last_activity_date' ? 'a' : 'u';

        return $queryBuilder->orderBy(
            $prefixTable . '.' . key($queryOrder),
            current($queryOrder)
        );
    }

    private function safeFlush(): bool
    {
        $this->em->beginTransaction();
        try {
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            return false;
        }
    }

    private function canChangeUserStatus(User $user, Status $newStatus): bool
    {
        return $user->getStatus()->canTransitionTo($newStatus);
    }

    public function activateUser(string $token): bool
    {
        $user = $this->userRepository
            ->findOneBy(['verification_token' => $token]);

        if (
            !$user || $user->getStatus === Status::BLOCKED || !$this->canChangeUserStatus(
                $user,
                Status::ACTIVE
            )
        )
            return false;

        $user->setVerificationToken(null);
        $user->setStatus(Status::ACTIVE);

        return $this->safeFlush();
    }

    /**
     * @throws UserManagementException
     */
    private function findUsersBy(
        array $userIds
    ): array 
    {
        $users = $this->userRepository->findBy(['id' => $userIds]);

        if (!$users)
            throw new UserManagementException('Users were not found');

        return $users;
    }

    /**
     * @throws UserManagementException
     */
    public function blockUser(
        array $userIds
    ): bool {
        return $this->setStatusUsers($userIds, Status::BLOCKED);
    }

    // i have no idea what to do here. Because unvirified users should not get here. So i put an active status 
    /**
     * @throws UserManagementException
     */
    public function unblockUser(
        array $userIds
    ): bool {
        return $this->setStatusUsers($userIds, Status::ACTIVE);
    }

    /**
     * @throws UserManagementException
     */
    private function setStatusUsers(array $userIds, Status $newStatus)
    {
        $users = $this->findUsersBy($userIds);

        foreach ($users as $user) {
            if (!$this->canChangeUserStatus($user, $newStatus)) continue;
            $user->setStatus($newStatus);
        }

        return $this->safeFlush();
    }

    /**
     * @throws UserManagementException
     */
    public function deleteUser(
        array $userIds
    ): bool {
        $users = $this->findUsersBy($userIds);
        $currentUser = $this->security->getUser();
        $invalidateSession = false;

        foreach ($users as $user) {
            $this->em->remove($user);

            if (
                $currentUser
                && method_exists($currentUser, 'getId')
                && $currentUser->getId() === $user->getId()
            )
                $invalidateSession = true;
        }

        if ($invalidateSession) {
            $this->tokenStorage->setToken(null);
            $this->session->invalidate();
        }

        return $this->safeFlush();
    }
}
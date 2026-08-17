<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Note: the admin table must be sorted, not shown in insert order.
     * Important: last login time is the default sort as required by the task.
     * Nota bene: users who never logged in still appear, after those who did.
     *
     * @return User[]
     */
    public function findSortedForTable(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.lastLoginAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int|string> $ids
     *
     * @return User[]
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUnverified(): array
    {
        return $this->findBy(['status' => UserStatus::Unverified]);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}

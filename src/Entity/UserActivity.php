<?php

namespace App\Entity;

use App\Repository\UserActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// attributes are everywhere 

#[ORM\Entity(repositoryClass: UserActivityRepository::class)]
class UserActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $last_activity_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLastActivityDate(): ?\DateTimeImmutable
    {
        return $this->last_activity_date;
    }

    public function setLastActivityDate(\DateTimeImmutable $last_activity_date): static
    {
        $this->last_activity_date = $last_activity_date;

        return $this;
    }
}

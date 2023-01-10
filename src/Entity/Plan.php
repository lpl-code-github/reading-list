<?php

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Id]
    #[ORM\Column]
    private ?int $bookId = null;

    #[ORM\Column]
    private ?int $readPage = null;

    #[ORM\Column]
    private ?int $status = null;

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     */
    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }


    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getBookId(): ?int
    {
        return $this->bookId;
    }

    public function setBookId(int $bookId): self
    {
        $this->bookId = $bookId;

        return $this;
    }

    public function getReadPage(): ?int
    {
        return $this->readPage;
    }

    public function setReadPage(int $readPage): self
    {
        $this->readPage = $readPage;

        return $this;
    }
}

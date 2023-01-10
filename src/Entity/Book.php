<?php

namespace App\Entity;

use App\Repository\BookRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    private ?string $author = null;

    #[ORM\Column()]
    private ?int $page = null;

    #[ORM\Column(length: 4)]
    private ?string $pubYear = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdOn = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modifiedOn = null;

    #[ORM\Column(type: Types::SMALLINT, insertable: false)]
    private ?int $active = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $userId = null;

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }



    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * @param string|null $author
     */
    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * @param int|null $page
     */
    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string|null
     */
    public function getPubYear(): ?string
    {
        return $this->pubYear;
    }

    /**
     * @param string|null $pubYear
     */
    public function setPubYear(?string $pubYear): void
    {
        $this->pubYear = $pubYear;
    }

    /**
     * @return string
     */
    public function getCreatedOn(): string
    {
        return date('Y-m-d', $this->createdOn->getTimestamp());
    }


    /**
     * @throws Exception
     */
    public function setCreatedOn(): void
    {
        $this->createdOn = new DateTime('now', new DateTimeZone('Asia/Shanghai'));

    }

    /**
     * @return string
     */
    public function getModifiedOn(): string
    {
        return date('Y-m-d', $this->modifiedOn->getTimestamp());
    }


    /**
     * @throws Exception
     */
    public function setModifiedOn(): void
    {
        $this->modifiedOn = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    }

    /**
     * @return int|null
     */
    public function getActive(): ?int
    {
        return $this->active;
    }

    /**
     * @param int|null $active
     */
    public function setActive(?int $active): void
    {
        $this->active = $active;
    }
}

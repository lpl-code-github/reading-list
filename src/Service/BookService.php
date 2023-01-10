<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\PlanRepository;
use App\Util\CustomException\NotFoundException;
use App\Util\CustomException\ParamErrorException;

use Doctrine\DBAL\Exception;

class BookService
{
    private BookRepository $bookRepository;
    private PlanRepository $planRepository;

    public function __construct(BookRepository $bookRepository, PlanRepository $planRepository)
    {
        $this->bookRepository = $bookRepository;
        $this->planRepository = $planRepository;
    }

    /**
     * Getting books
     * @param int $userId User ID
     * @param array|null $authors Author's array
     * @param array|null $sort Array of fields to be sorted
     * @param array|null $return Array of fields to query
     * @return array
     * @throws Exception
     */
    public function findBooks(int $userId, array $authors = null, array $sort = null, array $return = null): array
    {
        // query
        return $this->bookRepository->findBooks($authors, $sort, $return, $userId);
    }

    /**
     * Creating a Book
     * The book author, book name and user ID are unique constraints
     * @param int $userId User ID
     * @param array $resource Input parameter conversion array
     * @return Book
     * @throws ParamErrorException
     * @throws \Exception
     */
    public function saveBook(int $userId, array $resource): Book
    {
        // Query whether the book already exists for the user, and throw an exception if it exists
        if ($this->bookRepository->findOneBy(["name" => $resource['name'], "author" => $resource['author'], "userId" => $userId, 'active' => IS_NOT_DELETED]) != null) {
            throw new ParamErrorException("The book already exists, please reset the name or author.");
        }

        $book = new Book();
        $book->setName($resource['name']);
        $book->setAuthor($resource['author']);
        $book->setPage($resource['page']);
        $book->setPubYear($resource['pub_year']);
        $book->setUserId($userId);
        $book->setCreatedOn();

        $this->bookRepository->save($book, true);
        return $book;
    }

    /**
     * Modifying a book, partial update
     * @param int $userId
     * @param int $id
     * @param array $resource
     * @return Book
     * @throws NotFoundException
     * @throws ParamErrorException
     * @throws \Exception
     */
    public function updateBook(int $userId, int $id, array $resource): Book
    {
        // Query whether the book still exists
        $book = $this->bookRepository->findOneBy(["id" => $id, "userId" => $userId, "active" => IS_NOT_DELETED]);
        if ($book == null) {
            throw new NotFoundException("The book does not exist.");
        }

        // Flag to determine whether the bookTitle or bookAuthor has been modified
        $ModifyUniqueFlag = false;

        // Partial update
        if (array_key_exists('name', $resource)) {
            $name = $resource["name"];
            $ModifyUniqueFlag = ($name != $book->getName());
            $book->setName($name);
        }
        if (array_key_exists('author', $resource)) {
            $author = $resource["author"];
            $ModifyUniqueFlag = ($author != $book->getAuthor());
            $book->setAuthor($resource["author"]);
        }
        // If the uniqueness constraint is modified, check whether it already exists
        if ($ModifyUniqueFlag && $this->bookRepository->findOneBy(["name" => $book->getName(), "author" => $book->getAuthor(), 'active' => IS_NOT_DELETED]) !== null) {
            throw new ParamErrorException("The book already exists");
        }
        if (array_key_exists('page', $resource)) {
            // If the number of pages is modified, the status of the plan needs to be modified
            $plan = $this->planRepository->findOneBy(["userId" => $userId, "bookId" => $id]);
            if ($plan != null) {
                $readPage = $plan->getReadPage();
                $page = $resource['page'];

                if ($page < $readPage) {
                    throw new ParamErrorException("The number of pages " . $page . " set will be less than the number of reading pages " . $readPage . " in the plan");
                }
                if ($readPage == $page) {
                    $plan->setStatus(Read);
                }
                if ($readPage < $page) {
                    $plan->setStatus(Reading);
                }
            }
            $book->setPage($resource['page']);
        }
        if (array_key_exists('pub_year', $resource)) {
            $book->setPubYear($resource['pub_year']);
        }

        // Set update time
        $book->setModifiedOn();

        $this->bookRepository->save($book, true);
        return $book;
    }

    /**
     * Deleting a book
     * @throws NotFoundException
     * @throws \Exception
     */
    public function delBookById(int $userId, int $id): Book
    {
        $book = $this->bookRepository->findOneBy(["id" => $id, "userId" => $userId]);
        // If null is found, deletion fails
        if ($book == null) {
            throw new NotFoundException("Not found id=" . $id);
        }

        //  The status of the updated book is deleted
        if ($book->getActive() == IS_NOT_DELETED) {
            // Set update time
            $book->setModifiedOn();
            $this->bookRepository->remove($book, true);
        }

        // If the plan exists, delete the reading plan
        $plan = $this->planRepository->findOneBy(["userId" => $userId, "bookId" => $id]);
        if ($plan != null) {
            $this->planRepository->remove($plan, true);
        }

        // Idempotency considerations: If it has been deleted, return true directly
        return $book;
    }
}
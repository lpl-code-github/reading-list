<?php

namespace App\Service;

use App\Entity\Plan;
use App\Repository\BookRepository;
use App\Repository\PlanRepository;
use App\Util\CustomException\NotFoundException;
use App\Util\CustomException\ParamErrorException;

class PlanService
{
    private BookRepository $bookRepository;
    private PlanRepository $planRepository;

    public function __construct(BookRepository $bookRepository, PlanRepository $planRepository)
    {
        $this->bookRepository = $bookRepository;
        $this->planRepository = $planRepository;
    }

    /**
     * Set plan
     * @param int $userId
     * @param array $resource
     * @return Plan
     * @throws NotFoundException
     * @throws ParamErrorException
     */
    public function savePlan(int $userId, array $resource): Plan
    {
        $bookId = $resource['book_id'];
        $readPage = $resource['read_page'];

        // If no book is found, return 404
        $book = $this->bookRepository->findOneBy(['id' => $bookId, 'userId' => $userId, 'active' => IS_NOT_DELETED]);
        if ($book == null) {
            throw new NotFoundException('The book with book id ' . $bookId . ' is not found');
        }

        // If the book exists, judge whether the number of updated pages is less than or equal to the number of book pages, otherwise make an error
        if ($readPage > $book->getPage()) {
            throw new ParamErrorException('The number of reading pages should be less than or equal to the number of book pages.');
        }

        // Update if the task exists, otherwise add
        $p = $this->planRepository->findOneBy(['userId' => $userId, 'bookId' => $bookId]);
        if ($p == null) {
            $p = new Plan();
            $p->setReadPage($readPage)->setBookId($bookId)->setUserId($userId);
        } else {
            $p->setReadPage($readPage);
        }

        // Set Status
        if ($readPage == $book->getPage()) {
            $p->setStatus(Read);
        }
        if ($readPage < $book->getPage()) {
            $p->setStatus(Reading);
        }

        $this->planRepository->save($p, true);
        return $p;
    }
}
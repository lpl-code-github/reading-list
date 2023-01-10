<?php

namespace App\Controller;

use App\Service\BookService;
use App\Util\AuthUtil;
use App\Util\CustomException\NotFoundException;
use App\Util\CustomException\ParamErrorException;
use App\Util\Result;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    private AuthUtil $authUtil;

    public function __construct(AuthUtil $authUtil)
    {
        $this->authUtil = $authUtil;
    }

    /**
     * Getting books controller method
     * @param Request $request
     * @param BookService $bookService
     * @return JsonResponse
     * @throws ParamErrorException
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/books', name: 'find_books', methods: 'GET')]
    public function findBooks(Request $request, BookService $bookService): JsonResponse
    {
        // Get query parameters
        $authors = json_decode($request->query->get('authors'));
        $sort = json_decode($request->query->get('sort'));
        $return = json_decode($request->query->get('return'));
        $userId = $this->authUtil->getUserId($request);

        // Verification parameters
        $this->checkQueryParam($request);

        $result = new Result();
        return $this->json($result->success($bookService->findBooks($userId, $authors, $sort, $return)));
    }


    /**
     * Creating a Book
     * @param Request $request
     * @param BookService $bookService
     * @return JsonResponse
     * @throws ParamErrorException
     */
    #[Route('/books', name: 'save_book', methods: 'POST')]
    public function saveBook(Request $request, BookService $bookService): JsonResponse
    {
        // Get request body parameters
        try {
            $resource = $request->toArray();
        } catch (Exception $e) {
            throw new ParamErrorException('Missing request body in json format or parameter format is not JSON');
        }

        // Verify request body parameters
        $this->checkBookParam($resource, false);

        $userId = $this->authUtil->getUserId($request);
        $book = $bookService->saveBook($userId, $resource);

        $result = new Result();
        return $this->json($result->success(
            array(
                "book" => array(
                    "id" => $book->getId(),
                    "name" => $book->getName(),
                    "author" => $book->getAuthor(),
                    "page" => $book->getPage(),
                    "pub_year" => $book->getPubYear(),
                    "created_on" => $book->getCreatedOn()
                )
            )
        ));
    }

    /**
     * Modifying a book, Support partial update
     * @param int $id
     * @param Request $request
     * @param BookService $bookService
     * @return JsonResponse
     * @throws ParamErrorException
     * @throws NotFoundException
     */
    #[Route('/books/{id}', name: 'updateBook', requirements: ["id" => "\d+"], methods: 'PUT')]
    public function updateBook(int $id, Request $request, BookService $bookService): JsonResponse
    {
        // Get request body parameters
        try {
            $resource = $request->toArray();
            if (count($resource) == 0) {
                throw new ParamErrorException('Missing parameter');
            }
        } catch (Exception $e) {
            throw new ParamErrorException('Missing parameter or parameter format is not JSON');
        }

        // Verify request body parameters
        $this->checkBookParam($resource, true);

        $userId = $this->authUtil->getUserId($request);
        $book = $bookService->updateBook($userId, $id, $resource);

        $result = new Result();
        return $this->json($result->success(
            array(
                "book" => array(
                    "id" => $book->getId(),
                    "name" => $book->getName(),
                    "author" => $book->getAuthor(),
                    "page" => $book->getPage(),
                    "pub_year" => $book->getPubYear(),
                    "created_on" => $book->getCreatedOn(),
                    "modified_on" => $book->getModifiedOn()
                )
            )
        ));
    }

    /**
     * @throws NotFoundException
     * @throws ParamErrorException
     */
    #[Route('/books/{id}', name: 'delete_book', requirements: ["id" => "\d+"], methods: 'DELETE')]
    public function delBookById(int $id,Request $request, BookService $bookService): JsonResponse
    {
        // Input parameter verification
        if ($id == null || $id <= 0) {
            throw new ParamErrorException("parameter id error");
        }

        $userId = $this->authUtil->getUserId($request);
        $book = $bookService->delBookById($userId,$id);

        $result = new Result();
        return $this->json($result->success(
            array(
                "book" => array(
                    "id" => $book->getId(),
                    "created_on" => $book->getCreatedOn(),
                    "modified_on" => $book->getModifiedOn()
                )
            ))
        );
    }

    /*
     *  The following sections are the parameter verification methods
     */

    /**
     * Parameter verification method to be called for Getting books
     * @param Request $request
     * @return void
     * @throws ParamErrorException
     */
    function checkQueryParam(Request $request)
    {
        $queryParam = array("authors", "sort", "return"); // List of allowed parameters

        foreach ($queryParam as $qp) {
            $inputBag = $request->query->get($qp); // Json string
            if ($inputBag==null){
                break;
            }

            $paramValue = json_decode($inputBag); // If the format is correct, it can be parsed into an array
            // There is a request parameter, but the parsing error is wrong, the format is wrong
            if ($paramValue == null) {
                throw new ParamErrorException('Parameter format error');
            }

            // Call respective check methods
            match ($qp) {
                "authors" => $this->checkAuthors($paramValue),
                "sort" => $this->checkSort($paramValue),
                "return" => $this->checkReturn($paramValue),
            };
        }
    }

    /**
     * Verify whether the author array conforms to the specification
     * @param array $paramValue
     * @return void
     * @throws ParamErrorException
     */
    private function checkAuthors(array $paramValue=[])
    {
        foreach ($paramValue as $p) {
            $this->checkAuthor($p);
        }
    }

    /**
     * Verify whether the array of sorting fields conforms to the specification
     * @param array $paramValue
     * @return void
     * @throws ParamErrorException
     */
    private function checkSort(array $paramValue=[])
    {
        $allowColumn = array("pub_year.desc", "pub_year.asc", "name.desc", "name.asc");// Define a parameter value that allows sorting

        // Only [1~2] parameters are allowed
        $paramCount = count($paramValue);
        if ($paramCount > 2) {
            throw new ParamErrorException("More than two parameters");
        }

        // Traverse the parameter list and compare whether it is legal
        foreach ($paramValue as $p) {
            if (!in_array($p, $allowColumn)) {
                throw new ParamErrorException('The parameter "' . $p . '" is illegal');
            }
        }

        // The loop is placed before this step. First judge whether there are valid parameters, and then intercept
        if (($paramCount == 2 && (explode('.', $paramValue[0])[0] == explode('.', $paramValue[1])[0]))) {
            throw new ParamErrorException('Parameter exception');
        }
    }

    /**
     * Verify whether the array of fields to be returned conforms to the specification
     * @param mixed $paramValue
     * @return void
     * @throws ParamErrorException
     */
    private function checkReturn(array $paramValue=[])
    {
        foreach ($paramValue as $p) {
            switch ($p) {
                case 'modified_on':
                case 'author':
                case 'pub_year':
                case 'page':
                case 'read_page':
                case 'status':
                case 'created_on':
                case 'name':
                    break;
                default:
                    throw new ParamErrorException('The field to be returned does not exist.');
            }
        }
    }


    /**
     * Verification of updated or created parameters
     * @param array $resource
     * @param bool $allowBlank
     * @return void
     * @throws ParamErrorException
     */
    private function checkBookParam(array $resource, bool $allowBlank)
    {
        $paramArray = array("name", "author", "page", "pub_year"); // Parameters to be verified
        foreach ($paramArray as $item) {
            if (array_key_exists($item, $resource)) {
                // If there are parameters, call the verification method corresponding to the parameters
                $param = $resource[$item];
                match ($item) {
                    "name" => $this->checkName($param),
                    "author" => $this->checkAuthor($param),
                    "page" => $this->checkPage($param),
                    "pub_year" => $this->checkPubYear($param),
                };
            } else if (!$allowBlank) {
                // No parameter exists and $allowBlank is false
                throw new ParamErrorException('Missing parameter');
            }
        }
    }

    /**
     * Verify book name
     * @param string $name
     * @return void
     * @throws ParamErrorException
     */
    private function checkName(string $name)
    {
        if (empty($name)) {
            throw new ParamErrorException('The book name is empty');
        }
        if (strlen($name) > 50) {
            throw new ParamErrorException('The book name is empty or too long.');
        }
    }

    /**
     * Verify book author
     * @param string $author
     * @return void
     * @throws ParamErrorException
     */
    private function checkAuthor(string $author)
    {
        if (empty($author)) {
            throw new ParamErrorException('The book author is empty');
        }
        if (strlen($author) > 20) {
            throw new ParamErrorException('The book author is empty or too long.');
        }
    }

    /**
     * Verify book pubYear
     * @param string $pubYear
     * @return void
     * @throws ParamErrorException
     */
    private function checkPubYear(string $pubYear)
    {
        if (empty($pubYear)) {
            throw new ParamErrorException('The book published time is empty.');
        }
        if (strlen($pubYear) > 4) {
            throw new ParamErrorException('The book published time is empty or too long.');
        }
        try {
            $pubYearNum = intval($pubYear);
            if ($pubYearNum > date("Y") || $pubYearNum < 0) {
                throw new ParamErrorException('The book published time is not legal.');
            }
        } catch (Exception $e) {
            throw new ParamErrorException('The book published time is not legal.');
        }
    }

    /**
     * Verify book page
     * @param int $page
     * @return void
     * @throws ParamErrorException
     */
    private function checkPage(int $page)
    {
        if (!preg_match("/^[1-9]\d{0,4}$/", $page) == 1) {
            throw new ParamErrorException('The number of pages of a book must be between 0 and 99999.');
        }
    }


}
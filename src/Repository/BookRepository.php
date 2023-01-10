<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use const IS_NOT_DELETED;
use Doctrine\ORM\Query\Expr;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        $entity->setActive(IS_DELETED);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查询图书列表（全部）
     * @throws \Doctrine\DBAL\Exception
     */
    public function findBooks(array $authors = null, array $sort = null, array $return =null, int $userId = 1): array
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->select("b.id");
        if ($return != null) { // 数组不等于空
            foreach ($return as $r) {
                match ($r) {
                    "name" => $queryBuilder->addSelect("b.name"),
                    "author" => $queryBuilder->addSelect("b.author"),
                    "pub_year" => $queryBuilder->addSelect("b.pubYear"),
                    "page" => $queryBuilder->addSelect("b.page"),
                    "read_page" => $queryBuilder->addSelect("COALESCE(p.readPage,0) as read_page"),
                    "status" => $queryBuilder->addSelect("COALESCE(p.status,0) as status"),
                    "created_on" => $queryBuilder->addSelect("date_format(b.createdOn, '%Y-%m-%d') as created_on"),
                    "modified_on" => $queryBuilder->addSelect("date_format(b.modifiedOn, '%Y-%m-%d') as modified_on"),
                };
            }
        } else { // 否则查询全部字段
            $queryBuilder->addSelect(
                "b.name",
                "b.author",
                "b.pubYear",
                "b.page",
                "COALESCE(p.readPage,0) as read_page",
                "COALESCE(p.status,0) as status",
                "date_format(b.createdOn, '%Y-%m-%d') as created_on",
                "date_format(b.modifiedOn, '%Y-%m-%d') as modified_on"
            );
        }

        $queryBuilder->leftJoin('App\Entity\Plan', 'p', Expr\Join::WITH, 'p.bookId = b.id');
        $queryBuilder->andWhere('b.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('b.active = :active')
            ->setParameter('active',IS_NOT_DELETED);
        if ($authors!=null){
            $queryBuilder->andWhere('b.author in (:authors)')
                ->setParameter('authors', $authors);
        }
        if ($sort!=null){
            foreach ($sort as $s){
                $s = explode('.', $s);
                match ($s[0]) {
                    "pub_year" =>$queryBuilder->addOrderBy('b.pubYear',$s[1] ),
                    "name" =>$queryBuilder->addOrderBy('b.name',$s[1] )
                };

            }
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }
}

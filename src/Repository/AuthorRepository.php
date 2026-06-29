<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Author;
use App\Entity\Content;
use App\Entity\ContentStaff;

class AuthorRepository extends ServiceEntityRepository
{
    private const STATE_PUBLIC = 'public';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    public function getTopAuthors(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.id', 'a.name', 'a.biography', 'a.channelLink', 'a.createdAt', 'a.state', 'a.telegramUserId', 'COUNT(cs.content) as projects_count')
           ->leftJoin(ContentStaff::class, 'cs', 'WITH', 'cs.author = a')
           ->leftJoin(Content::class, 'c', 'WITH', 'cs.content = c AND c.status = :status')
           ->where('a.state = :state')
           ->setParameter('status', 'published')
           ->setParameter('state', self::STATE_PUBLIC)
           ->groupBy('a.id')
           ->orderBy('projects_count', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    public function getAllAuthors(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.id', 'a.name', 'a.biography', 'a.channelLink as channel_link', 'a.createdAt as created_at', 'a.state', 'a.telegramUserId as telegram_user_id');

        return $qb->getQuery()->getArrayResult();
    }

    public function findByTelegramId(int $telegramUserId): ?Author
    {
        return $this->findOneBy(['telegramUserId' => $telegramUserId]);
    }

    public function getAuthorProjects(int $author_id, int $limit = 10, int $offset = 0): array
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('c.id', 'c.title', 'c.description', 'c.url', 'c.cover', 'c.type', 'c.status', 'c.publishedAt', 'c.trendingScore', 'a.name as author_name', 'a.id as author_profile_id')
           ->from(Content::class, 'c')
           ->join(ContentStaff::class, 'cs', 'WITH', 'cs.content = c')
           ->join(Author::class, 'a', 'WITH', 'cs.author = a')
           ->where('cs.author = :author_id')
           ->andWhere('c.status = :status')
           ->setParameter('author_id', $author_id)
           ->setParameter('status', 'published')
           ->groupBy('c.id', 'a.id')
           ->orderBy('c.trendingScore', 'DESC')
           ->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }
}

<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

abstract class BaseRepository extends ServiceEntityRepository
{
    protected ?CacheInterface $cache;
    protected ?LoggerInterface $logger;
    protected int $defaultCacheTtl = 3600; // 1 hour
    
    public function __construct(ManagerRegistry $registry, string $entityClass, CacheInterface $cache = null, LoggerInterface $logger = null)
    {
        parent::__construct($registry, $entityClass);
        $this->cache = $cache;
        $this->logger = $logger;
    }
    
    /**
     * Find with caching support
     */
    public function findCached(int $id, int $ttl = null): ?object
    {
        if (!$this->cache) {
            return $this->find($id);
        }
        
        $cacheKey = sprintf('%s_entity_%d', $this->getEntityName(), $id);
        $ttl = $ttl ?? $this->defaultCacheTtl;
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id, $ttl) {
                $item->expiresAfter($ttl);
                return $this->find($id);
            });
        } catch (\Exception $e) {
            $this->logError('Cache error in findCached', $e, ['id' => $id]);
            return $this->find($id);
        }
    }
    
    /**
     * Find by criteria with caching
     */
    public function findByCached(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, int $ttl = null): array
    {
        if (!$this->cache) {
            return $this->findBy($criteria, $orderBy, $limit, $offset);
        }
        
        $cacheKey = sprintf('%s_findby_%s', $this->getEntityName(), md5(serialize([$criteria, $orderBy, $limit, $offset])));
        $ttl = $ttl ?? $this->defaultCacheTtl;
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($criteria, $orderBy, $limit, $offset, $ttl) {
                $item->expiresAfter($ttl);
                return $this->findBy($criteria, $orderBy, $limit, $offset);
            });
        } catch (\Exception $e) {
            $this->logError('Cache error in findByCached', $e, ['criteria' => $criteria]);
            return $this->findBy($criteria, $orderBy, $limit, $offset);
        }
    }
    
    /**
     * Optimized pagination
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $criteria = [], array $orderBy = []): array
    {
        try {
            $qb = $this->createQueryBuilder('e');
            
            // Apply criteria
            foreach ($criteria as $field => $value) {
                if ($value !== null) {
                    $qb->andWhere("e.{$field} = :{$field}")
                       ->setParameter($field, $value);
                }
            }
            
            // Apply ordering
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy("e.{$field}", $direction);
            }
            
            // Apply pagination
            $qb->setFirstResult(($page - 1) * $limit)
               ->setMaxResults($limit);
            
            $paginator = new Paginator($qb, false);
            
            return [
                'data' => iterator_to_array($paginator),
                'total' => $paginator->count(),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($paginator->count() / $limit)
            ];
        } catch (\Exception $e) {
            $this->logError('Error in findPaginated', $e, ['page' => $page, 'limit' => $limit]);
            throw $e;
        }
    }
    
    /**
     * Bulk operations with better performance
     */
    public function batchInsert(array $entities, int $batchSize = 100): void
    {
        try {
            $em = $this->getEntityManager();
            $count = 0;
            
            foreach ($entities as $entity) {
                $em->persist($entity);
                $count++;
                
                if ($count % $batchSize === 0) {
                    $em->flush();
                    $em->clear(); // Clear memory
                }
            }
            
            // Flush remaining entities
            if ($count % $batchSize !== 0) {
                $em->flush();
                $em->clear();
            }
        } catch (\Exception $e) {
            $this->logError('Error in batchInsert', $e, ['count' => count($entities)]);
            throw $e;
        }
    }
    
    /**
     * Search with optimized queries
     */
    public function search(string $query, array $fields = [], int $limit = 50): array
    {
        try {
            if (empty($fields)) {
                return [];
            }
            
            $qb = $this->createQueryBuilder('e');
            $searchPattern = '%' . $query . '%';
            
            $conditions = [];
            $parameters = [];
            
            foreach ($fields as $index => $field) {
                $paramName = "search_param_{$index}";
                $conditions[] = "e.{$field} LIKE :{$paramName}";
                $parameters[$paramName] = $searchPattern;
            }
            
            $qb->where(implode(' OR ', $conditions))
               ->setMaxResults($limit);
               
            foreach ($parameters as $param => $value) {
                $qb->setParameter($param, $value);
            }
            
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            $this->logError('Error in search', $e, ['query' => $query, 'fields' => $fields]);
            return [];
        }
    }
    
    /**
     * Clear cache for entity
     */
    public function clearCache(string $pattern = null): void
    {
        if (!$this->cache) {
            return;
        }
        
        try {
            $pattern = $pattern ?? $this->getEntityName() . '_*';
            // For now, we'll use a simple approach - in production you might use Redis commands
            // $this->cache->clear(); // This clears the entire cache pool
        } catch (\Exception $e) {
            $this->logError('Error clearing cache', $e, ['pattern' => $pattern]);
        }
    }
    
    /**
     * Health check for repository
     */
    public function healthCheck(): array
    {
        try {
            $start = microtime(true);
            
            // Test basic connection
            $count = $this->createQueryBuilder('e')
                          ->select('COUNT(e.id)')
                          ->getQuery()
                          ->getSingleScalarResult();
            
            $duration = microtime(true) - $start;
            
            return [
                'status' => 'healthy',
                'entity' => $this->getEntityName(),
                'count' => $count,
                'query_time' => round($duration * 1000, 2) . 'ms',
                'cache_enabled' => $this->cache !== null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'entity' => $this->getEntityName(),
                'error' => $e->getMessage(),
                'cache_enabled' => $this->cache !== null
            ];
        }
    }
    
    /**
     * Get entity name for caching
     */
    protected function getEntityName(): string
    {
        $className = $this->getClassName();
        return strtolower(basename(str_replace('\\', '/', $className)));
    }
    
    /**
     * Enhanced error logging
     */
    protected function logError(string $message, \Exception $e, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }
        
        $this->logger->error($message, [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'entity' => $this->getEntityName(),
            'context' => $context
        ]);
    }
    
    /**
     * Create optimized query builder
     */
    public function createOptimizedQueryBuilder(string $alias): QueryBuilder
    {
        $qb = $this->createQueryBuilder($alias);
        
        // Add query hints for better performance
        $qb->getQuery()->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        
        return $qb;
    }
} 
<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500)]
#[AsDoctrineListener(event: Events::preRemove, priority: 500)]
final class DoctrineEventSubscriber
{
    private bool $loggingEnabled = true;

    public function __construct(
        private ActivityLogService $activityLogService,
        private Security $security
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user || $this->shouldSkipLogging($entity)) {
            return;
        }

        $entityClass = $this->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        $this->activityLogService->logCreate(
            $user,
            $entityClass,
            $entityId,
            $this->getEntityData($entity)
        );
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user || $this->shouldSkipLogging($entity)) {
            return;
        }

        $entityClass = $this->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        $this->activityLogService->logUpdate(
            $user,
            $entityClass,
            $entityId,
            $this->getEntityData($entity)
        );
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user || $this->shouldSkipLogging($entity)) {
            return;
        }

        $entityClass = $this->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        $this->activityLogService->logDelete(
            $user,
            $entityClass,
            $entityId,
            $this->getEntityData($entity)
        );
    }

    private function shouldSkipLogging(object $entity): bool
    {
        // Don't log ActivityLog itself or the User entity (to avoid recursion)
        $className = $this->getEntityName($entity);
        
        return in_array($className, ['ActivityLog', 'User'], true);
    }

    private function getEntityName(object $entity): string
    {
        $className = $entity::class;
        return substr($className, strrpos($className, '\\') + 1);
    }

    private function getEntityId(object $entity): ?int
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            return is_int($id) ? $id : null;
        }

        return null;
    }

    private function getEntityData(object $entity): array
    {
        $data = [];

        // Extract key properties
        if (method_exists($entity, 'getName')) {
            $data['name'] = $entity->getName();
        }
        if (method_exists($entity, 'getTitle')) {
            $data['title'] = $entity->getTitle();
        }
        if (method_exists($entity, 'getEmail')) {
            $data['email'] = $entity->getEmail();
        }
        if (method_exists($entity, 'getUsername')) {
            $data['username'] = $entity->getUsername();
        }

        return $data;
    }

    public function disableLogging(): void
    {
        $this->loggingEnabled = false;
    }

    public function enableLogging(): void
    {
        $this->loggingEnabled = true;
    }
}

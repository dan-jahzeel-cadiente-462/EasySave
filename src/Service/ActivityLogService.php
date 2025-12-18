<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    public function log(
        ?User $user,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $details = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDescription($description ?? "$action on $entityType");
        $log->setDetails($details);
        
        // Get IP address and User Agent from request
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logLogin(User $user): void
    {
        $this->log($user, 'LOGIN', 'User', $user->getId(), $user->getUsername() . ' logged in');
    }

    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT', 'User', $user->getId(), $user->getUsername() . ' logged out');
    }

    public function logCreate(User $user, string $entityType, int $entityId, ?array $data = null): void
    {
        $this->log($user, 'CREATE', $entityType, $entityId, "Created new $entityType", $data);
    }

    public function logUpdate(User $user, string $entityType, int $entityId, ?array $data = null): void
    {
        $this->log($user, 'UPDATE', $entityType, $entityId, "Updated $entityType", $data);
    }

    public function logDelete(User $user, string $entityType, int $entityId, ?array $data = null): void
    {
        $this->log($user, 'DELETE', $entityType, $entityId, "Deleted $entityType", $data);
    }
}

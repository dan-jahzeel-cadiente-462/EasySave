<?php
require __DIR__ . '/vendor/autoload.php';

use App\Service\EmailVerificationService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;

class DummyEntityManager implements EntityManagerInterface {
    public function getCache(){}
    public function getClassMetadata($className){}
    public function getConnection(){}
    public function getExpressionBuilder(){}
    public function beginTransaction(){}
    public function transactional($func){}
    public function commit(){}
    public function rollback(){}
    public function createQuery($dql = ''){}
    public function createNamedQuery($name){}
    public function createNativeQuery($sql, $rsm){}
    public function createNamedNativeQuery($name){}
    public function createQueryBuilder(){}
    public function flush($entity = null){}
    public function find($className, $id){}
    public function getReference($entityName, $id){}
    public function getPartialReference($entityName, $identifier){}
    public function clear($entityName = null){}
    public function close(){}
    public function persist($entity){}
    public function remove($entity){}
    public function refresh($entity){}
    public function detach($entity){}
    public function merge($entity){}
    public function copy($entity, $deep = false){}
    public function lock($entity, $lockMode, $lockVersion = null){}
    public function getEventManager(){}
    public function getConfiguration(){}
    public function isOpen(){}
    public function getUnitOfWork(){}
    public function getHydrator($hydrationMode){}
    public function newHydrator($hydrationMode){}
    public function getProxyFactory(){}
    public function getFilters(){}
    public function isFiltersStateClean(){}
    public function hasFilters(){}
}

class DummyRepository implements UserRepository {
}

class DummyMailer implements MailerInterface {
    public function send(RawMessage $message, Envelope $envelope = null): void {}
}

$service = new EmailVerificationService(
    new DummyEntityManager(),
    new DummyRepository(),
    new DummyMailer(),
    new NullLogger(),
    'noreply@example.com'
);

$user = new User();
$user->setUsername('testuser');
$user->setEmail('test@example.com');

$html = $service->renderVerificationEmailTemplate($user, 'https://example.com/verify/token');
$text = $service->renderVerificationEmailText($user, 'https://example.com/verify/token');

echo "HTML length: " . strlen($html) . PHP_EOL;
echo strpos($html, '100%%') !== false ? "FOUND 100%%\n" : "NOT FOUND 100%%\n";
echo strpos($html, '100%') !== false ? "FOUND 100%\n" : "NOT FOUND 100%\n";

echo "Text output OK\n";

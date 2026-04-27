<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth fields to User entity';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        
        // Add googleId field
        if (!$table->hasColumn('google_id')) {
            $table->addColumn('google_id', Types::STRING, [
                'length' => 255,
                'notnull' => false,
                'default' => null,
            ]);
            $table->addUniqueIndex(['google_id'], 'UNIQ_GOOGLE_ID');
        }

        // Add provider field for tracking OAuth provider
        if (!$table->hasColumn('provider')) {
            $table->addColumn('provider', Types::STRING, [
                'length' => 50,
                'notnull' => false,
                'default' => 'local',
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');
        
        if ($table->hasColumn('google_id')) {
            $table->dropIndex('UNIQ_GOOGLE_ID');
            $table->dropColumn('google_id');
        }

        if ($table->hasColumn('provider')) {
            $table->dropColumn('provider');
        }
    }
}

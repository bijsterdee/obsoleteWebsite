<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1400278227.
 * Generated on 2014-05-17 00:10:27 by www-data
 */
class PropelMigration_1400278227
{

    public function preUp($manager)
    {
        // add the pre-migration code here
    }

    public function postUp($manager)
    {
        // add the post-migration code here
    }

    public function preDown($manager)
    {
        // add the pre-migration code here
    }

    public function postDown($manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return array (
  'fayntic-services' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP INDEX `account_email_email` ON `account_email`;

CREATE INDEX `account_email_email` ON `account_email` (`email`);

DROP INDEX `account_email_alias_source` ON `account_email_alias`;

CREATE INDEX `account_email_alias_source` ON `account_email_alias` (`source`);

ALTER TABLE `account_product`

  CHANGE `price` `price` decimal(5,2),

  CHANGE `btw` `btw` decimal(5,2);

ALTER TABLE `account_support`

  CHANGE `is_registered` `is_registered` tinyint(1) unsigned DEFAULT false;

ALTER TABLE `account_web`

  CHANGE `is_pma` `is_pma` tinyint(1) unsigned DEFAULT false,

  CHANGE `is_webmail` `is_webmail` tinyint(1) unsigned DEFAULT false,

  CHANGE `is_active` `is_active` tinyint(1) unsigned DEFAULT false;

ALTER TABLE `language`

  CHANGE `is_default` `is_default` tinyint(1) unsigned DEFAULT false;

ALTER TABLE `product`

  CHANGE `category` `category` enum(\'bandwidth\', \'bouncer\', \'database\', \'diskspace\', \'domain\', \'email\', \'ftp\'),

  CHANGE `type` `type` enum(\'single\', \'multiple\') DEFAULT \'single\',

  CHANGE `price` `price` decimal(5,2) DEFAULT 0.00,

  CHANGE `btw` `btw` decimal(5,2) DEFAULT 21.00,

  CHANGE `period_unit` `period_unit` enum(\'second\', \'minute\', \'hour\', \'day\', \'month\', \'year\') DEFAULT \'month\',

  CHANGE `is_available` `is_available` tinyint(1) unsigned DEFAULT false;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return array (
  'fayntic-services' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP INDEX `account_email_email` ON `account_email`;

CREATE INDEX `account_email_email` ON `account_email` (`email`(191));

DROP INDEX `account_email_alias_source` ON `account_email_alias`;

CREATE INDEX `account_email_alias_source` ON `account_email_alias` (`source`(191));

ALTER TABLE `account_product`

  CHANGE `price` `price` DECIMAL(5,2),

  CHANGE `btw` `btw` DECIMAL(5,2);

ALTER TABLE `account_support`

  CHANGE `is_registered` `is_registered` tinyint(1) unsigned DEFAULT 0;

ALTER TABLE `account_web`

  CHANGE `is_pma` `is_pma` tinyint(1) unsigned DEFAULT 0,

  CHANGE `is_webmail` `is_webmail` tinyint(1) unsigned DEFAULT 0,

  CHANGE `is_active` `is_active` tinyint(1) unsigned DEFAULT 0;

ALTER TABLE `language`

  CHANGE `is_default` `is_default` tinyint(1) unsigned DEFAULT 0;

ALTER TABLE `product`

  CHANGE `category` `category` enum(\'bandwidth\',\'bouncer\',\'database\',\'diskspace\',\'domain\',\'email\',\'ftp\'),

  CHANGE `type` `type` enum(\'single\',\'multiple\') DEFAULT \'single\',

  CHANGE `price` `price` DECIMAL(5,2) DEFAULT 0.00,

  CHANGE `btw` `btw` DECIMAL(5,2) DEFAULT 21.00,

  CHANGE `period_unit` `period_unit` enum(\'second\',\'minute\',\'hour\',\'day\',\'month\',\'year\') DEFAULT \'month\',

  CHANGE `is_available` `is_available` tinyint(1) unsigned DEFAULT 0;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

}
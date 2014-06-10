INSERT INTO `language` (`id`, `identifier`, `is_default`, `created_at`, `updated_at`) VALUES
  (1, 'en_US', 1, NOW(), NOW()), -- English
  (2, 'nl_NL', 0, NOW(), NOW()); -- Dutch

INSERT INTO `language_i18n` (`id`, `locale`, `name`) VALUES
  (1, 'en_US', 'English (United States)'),
  (1, 'nl_NL', 'Engels (Verenigde Staten)'),
  (2, 'en_US', 'Dutch'),
  (2, 'nl_NL', 'Nederlands');

INSERT INTO `country` (`id`, `identifier`, `created_at`, `updated_at`) VALUES
  (1, 'nl_NL', NOW(), NOW()), -- The Netherlands
  (2, 'nl_BE', NOW(), NOW()), -- Belgium
  (3, 'en_US', NOW(), NOW()); -- The United States

INSERT INTO `country_i18n` (`id`, `locale`, `name`) VALUES
  (1, 'en_US', 'The Netherlands'),
  (1, 'nl_NL', 'Nederland'),
  (2, 'en_US', 'Belgium'),
  (2, 'nl_NL', 'België'),
  (3, 'en_US', 'The United States'),
  (3, 'nl_NL', 'De Verenigde Staten');

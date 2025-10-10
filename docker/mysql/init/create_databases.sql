CREATE DATABASE IF NOT EXISTS laravel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_user'@'%';
GRANT ALL PRIVILEGES ON laravel_test.* TO 'laravel_user'@'%';

FLUSH PRIVILEGES;
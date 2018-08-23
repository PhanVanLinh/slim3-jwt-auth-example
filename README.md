# PHPMySlimServer

Fork from (Slim3 JWT authentication example) https://github.com/letsila/slim3-jwt-auth-example

### Some customize
- Naming convention
- Create database
```
    CREATE TABLE `tokens` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `value` text,
      `user_id` int(11) DEFAULT NULL,
      `created_date` int(11) DEFAULT NULL,
      `expiration_date` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Some note
- Run server: `php -S 0.0.0.0:8080 -t public public/index.php`

## License
MIT

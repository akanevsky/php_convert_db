# What is PHP_Convert_DB

PHP_Convert_DB is a PHP script for converting a MySQL database from one encoding to a different one. It can also be used to straighten out encoding mess in your database. One example of when this might be useful is when you have cp1251 text stored in latin1 fields, and you are unable to manage any of your data through scripts like phpMyAdmin because all you see is weird characters.

# Using PHP_Convert_DB v0.1

- Dump your MySQL database structure (do not dump data)
- In the dump, fix all encoding and collation declarations so that they are as you would like to see them in the migrated database
- Import the fixed dump to a new blank database
- Open php_convert_db.php and configure the class so that the settings match your setup
- Run index.php, and all your data should be transferred over to the new database with the correct encoding

Enjoy!

## Requirements:

- Tested on: Apache
- PHP 5.2+
- MySQL 5+
- PDO Extension for PHP (MySQL)

### How to contribute

Please base all your pull requests and development off of the `develop` branch.
The `master` branch is for tagged releases only.

### Main Developers:

- [Anton Kanevsky](http://about.me/akanevsky)

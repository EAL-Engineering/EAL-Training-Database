# Training Management System / Operator Database

A PHP-based application designed to handle operator database tracking and certification management.

## Requirements

* PHP 5.4 or higher
* MySQL or MariaDB
* A web server (Apache/Nginx)

## Installation & Setup

1. **Clone the repository:**

    git clone (<https://github.com/EAL-Engineering/EAL-Training-Database.git>)
    cd EAL-Training-Database

2. **Configure the database connection:**
   Copy the provided example configuration file to create your active, local configuration:

    cp config.example.php config.php

   *Note: `config.php` is intentionally ignored by git to prevent accidental credential leaks.*

3. **Update the configuration:**
   Open `config.php` in your text editor and update the `$databaseHost`, `$databaseUsername`, `$databasePassword`, and `$databaseName` variables to match your database environment.

4. **Database Initialization:**
   *(Import the required database schema. See `schema.sql` if applicable.)*

## License

This project is licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).

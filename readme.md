
## Dependencies

- PHP >= 5.6.4
- Composer
- Laravel Framework 5.3
- Maria DB / MySQL
- Web server

## Setup Guide

- Maria DB / MySQL
  - Create a new blank database
  - Create a database user with full permissions over the database
- Clone this repository (*where HIV_RAPID_PT_HOME represents the folder you are cloning to).
  `git clone git@github.com:APHL-Global-Health/HIV-Rapid-PT.git HIV_RAPID_PT_HOME`
- Install Laravel and other PHP dependencies
  ```
   cd HIV_RAPID_PT_HOME
   composer install
  ```
- Create a configuration file (.env) and update the relevant details
  ```
   cd HIV_RAPID_PT_HOME
   cp .env.example .env
  ```
  - make the requisite changes to this file such as the database name and access credentials
  - create the application key
    `php artisan key:generate`
  - create and seed the database
    `php artisan migrate --seed`
  - Point your web server to the `HIV_RAPID_PT_HOME/public' folder.
    - Ensure that the webserver user has write permissions to the `HIV_RAPID_PT_HOME/storage` folder.

## Contributing

Thank you for considering contributing to the HIV Rapid PT system! Send a pull request and we'll check it out. A detailed contribution guide should be out soon!

## License

The HIV Rapid PT system is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

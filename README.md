# Popup Galleries of San Cris

This is the admin side of the popup galleries project.

Requirements
------------

* PHP 8.4
* postgres and PDO-SQLite PHP extension enabled;
* and the [usual Symfony application requirements][2].

Installation
------------

```bash
git clone git@github.com:survos-sites/pgsc && cd pgsc
composer install
bin/console doctrine:schema:update --force
bin/console doctrine:fixtures:load -n
bin/console survos:user:create test@test.com test --roles ROLE_ADMIN
symfony server:start -d
symfony open:local
```

Admin from fixtures:

    'email' => 'admin@example.com',
    'plainPassword' => 'adminpass',


**Option 2.** [Download Composer][6] and use the `composer` binary installed
on your computer to run these commands:

```bash
# you can create a new project based on the Symfony Demo project...
composer create-project symfony/symfony-demo my_project

# ...or you can clone the code repository and install its dependencies
git clone https://github.com/symfony/demo.git my_project
cd my_project/
composer install
```

**Option 3.** Click the following button to deploy this project on Platform.sh,
the official Symfony PaaS, so you can try it without installing anything locally:

<p align="center">
<a href="https://console.platform.sh/projects/create-project?template=https://raw.githubusercontent.com/symfonycorp/platformsh-symfony-template-metadata/main/symfony-demo.template.yaml&utm_content=symfonycorp&utm_source=github&utm_medium=button&utm_campaign=deploy_on_platform"><img src="https://platform.sh/images/deploy/lg-blue.svg" alt="Deploy on Platform.sh" width="180px" /></a>
</p>

Usage
-----

There's no need to configure anything before running the application. There are
2 different ways of running this application depending on your needs:

**Option 1.** [Download Symfony CLI][4] and run this command:

```bash
cd my_project/
symfony serve
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

**Option 2.** Use a web server like Nginx or Apache to run the application
(read the documentation about [configuring a web server for Symfony][3]).

On your local machine, you can run this command to use the built-in PHP web server:

```bash
cd my_project/
php -S localhost:8000 -t public/
```

Tests
-----

Execute this command to run tests:

```bash
cd my_project/
./bin/phpunit
```

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/setup.html#technical-requirements
[3]: https://symfony.com/doc/current/setup/web_server_configuration.html
[4]: https://symfony.com/download
[5]: https://symfony.com/book
[6]: https://getcomposer.org/

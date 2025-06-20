# Popup Galleries of San Cris

This is the admin side of the popup galleries project.

<a href="https://asciinema.org/a/bJMOlPe5F4mFLY0Rl6fiJSOp3" target="_blank"><img src="https://asciinema.org/a/bJMOlPe5F4mFLY0Rl6fiJSOp3.svg" /></a>


Requirements
------------

* PHP 8.4
* postgres and PDO-SQLite PHP extension enabled;
* and the [usual Symfony application requirements][2].

## Notes

This was removed during development, but maybe someday will be re-added.

```bash
composer req agence-adeliom/easy-media-bundle
```


Installation
------------

```bash
git clone git@github.com:survos-sites/pgsc && cd pgsc
composer install
bin/console doctrine:schema:update --force
bin/console doctrine:fixtures:load -n
symfony server:start -d
symfony open:local --path=/en/artist/edit/1
```

Admin from fixtures:

    'email' => 'admin@example.com',
    'plainPassword' => 'adminpass',

# For maps

```bash
bin/console workflow:iterate App\\Entity\\Location --marking=new --transition=geocode
```



## Database

![Database Diagram](./assets/images/db.svg)


## Project Goals

To administer the Popup Galleries of San Cris exposition, and provide the data for the associated mobile app.

As an administrator, I can

* Add/Edit Locations
* Add/Edit Artists
* Add/Edit Artwork
* Manage Users
* Print reports 
  * Artwork by Artist 
  * Artwork by Location
  * Catalog
* Trigger requests for automatic translations of the database

As an artist, I can

* Add/Edit my artwork, including pricing, description, etc.
* Update my profile (bio, photo, etc.)
* Give admin permissions to another user for my artwork

As a registered user, from the website, I can

* "Like" or clap for pieces I like
* See links to purchase
* Share items on Social media

As a Visitor, I can

* See the artist and locations
* See artwork with QR codes
* Link to the mobile app


The mobile app requirements are listed elsewhere, this is for the desktop-based website.
.

# Developer notes

```bash
composer config repositories.ezmeadia '{"type": "vcs", "url": "git@github.com:tacman/easy-media-bundle.git"}'
composer req tacman/easy-media-bundle:dev-tac
```

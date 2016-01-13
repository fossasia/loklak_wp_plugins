# WordPress 4.4.1-ja + pg4wp2 Heroku

This project is a template for installing and running [WordPress](http://wordpress.org/) on [Heroku](http://www.heroku.com/). The repository comes bundled with:
* [PostgreSQL for WordPress](http://wordpress.org/extend/plugins/postgresql-for-wordpress/)
* [Amazon S3 and Cloudfront](https://wordpress.org/plugins/amazon-s3-and-cloudfront/)
* [WP Sendgrid](https://wordpress.org/plugins/wp-sendgrid/)
* [Wordpress HTTPS](https://wordpress.org/plugins/wordpress-https/)

## Installation

Use the Deploy to Heroku button, or use the old fashioned way described below.

<a href="https://heroku.com/deploy?template=https://github.com/macminiosx/wordpress-ja-pg4wp2-heroku/tree/master">
  <img src="https://www.herokucdn.com/deploy/button.png" alt="Deploy">
</a>

Clone the repository from Github

    $ git clone git@github.com:macminiosx/wordpress-ja-pg4wp2-heroku.git

With the [Heroku gem](http://devcenter.heroku.com/articles/heroku-command), create your app

    $ cd wordpress-heroku
    $ heroku create --ssh-git --stack cedar-14
    Creating strange-turtle-1234... done, stack is cedar
    http://strange-turtle-1234.herokuapp.com/ | git@heroku.com:strange-turtle-1234.git
    Git remote heroku added

Add a database to your app

    $ heroku addons:create heroku-postgresql:hobby-dev
    Adding heroku-postgresql:dev to strange-turtle-1234... done, v2 (free)
    Attached as HEROKU_POSTGRESQL_COLOR
    Database has been created and is available
    Use `heroku addons:docs heroku-postgresql:dev` to view documentation

Promote the database (replace COLOR with the color name from the above output)

    $ heroku pg:promote HEROKU_POSTGRESQL_COLOR
    Promoting HEROKU_POSTGRESQL_COLOR to DATABASE_URL... done

Add the ability to send email (i.e. Password Resets etc)

    $ heroku addons:add sendgrid:starter
    Adding sendgrid:starter on your-app... done, v14 (free)
    Use `heroku addons:docs sendgrid` to view documentation.

Create a new branch for any configuration/setup changes needed

    $ git checkout -b production

Store unique keys and salts in Heroku environment variables. Wordpress can provide random values [here](https://api.wordpress.org/secret-key/1.1/salt/).

    heroku config:set AUTH_KEY='put your unique phrase here' \
      SECURE_AUTH_KEY='put your unique phrase here' \
      LOGGED_IN_KEY='put your unique phrase here' \
      NONCE_KEY='put your unique phrase here' \
      AUTH_SALT='put your unique phrase here' \
      SECURE_AUTH_SALT='put your unique phrase here' \
      LOGGED_IN_SALT='put your unique phrase here' \
      NONCE_SALT='put your unique phrase here'

Create .htpasswd

    $ echo "USERNAME:CRYPT PASSWOER" > .htpasswd

Deploy to Heroku

    $ git push heroku production:master
    -----> Deleting 0 files matching .slugignore patterns.
    -----> PHP app detected

     !     WARNING: No composer.json found.
           Using index.php to declare PHP applications is considered legacy
           functionality and may lead to unexpected behavior.

    -----> No runtime requirements in composer.json, defaulting to PHP 5.6.2.
    -----> Installing system packages...
           - PHP 5.6.2
           - Apache 2.4.10
           - Nginx 1.6.0
    -----> Installing PHP extensions...
           - zend-opcache (automatic; bundled, using 'ext-zend-opcache.ini')
    -----> Installing dependencies...
           Composer version 1.0-dev (ffffab37a294f3383c812d0329623f0a4ba45387) 2014-11-05 06:04:18
           Loading composer repositories with package information
           Installing dependencies
           Nothing to install or update
           Generating optimized autoload files
    -----> Preparing runtime environment...
           NOTICE: No Procfile, defaulting to 'web: vendor/bin/heroku-php-apache2'
    -----> Discovering process types
           Procfile declares types -> web

    -----> Compressing... done, 78.5MB
    -----> Launcing... done, v5
           http://strange-turtle-1234.herokuapp.com deployed to Heroku

    To git@heroku:strange-turtle-1234.git
      * [new branch]    production -> master

After deployment WordPress has a few more steps to setup and thats it!

## Usage

Because a file cannot be written to Heroku's file system, updating and installing plugins or themes should be done locally and then pushed to Heroku.

## Updating

Updating your WordPress version is just a matter of merging the updates into
the branch created from the installation.

    $ git pull # Get the latest

Using the same branch name from our installation:

    $ git checkout production
    $ git merge master # Merge latest
    $ git push heroku production:master

WordPress needs to update the database. After push, navigate to:

    http://your-app-url.herokuapp.com/wp-admin

WordPress will prompt for updating the database. After that you'll be good
to go.

## Deployment optimisation

If you have files that you want tracked in your repo, but do not need deploying (for example, *.md, *.pdf, *.zip). Then add path or linux file match to the `.slugignore` file & these will not be deployed.

Examples:
```
path/to/ignore/
bin/
*.md
*.pdf
*.zip
```


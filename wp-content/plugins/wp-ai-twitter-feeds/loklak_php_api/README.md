# Loklak PHP

This is the PHP Client and PHP API for accessing the Loklak API
It contains a series of calls that could be directly used for the requested JSON responses

This library is bundled with Requests library in PHP keeping it standalone and works by just plugging it into the `lib/` folder.

This is intended to be the generic PHP request library to loklak for integration into PHP websites and CMS frameworks like wordpress, drupal, joomla etc..,

## How to run PHPUnit tests?

### *AMP Solution Stack or Command Line Users

Install `PHPUnit` using `phar` or `composer`. 
Refer to [this](https://phpunit.de/manual/current/en/installation.html) for installation details. 

Once PHPUnit is installed, open XAMPP shell(for Windows users) / Terminal (for Ubuntu/Mac Users). 
Go to project root and type the following command:
`phpunit Tests`
This will execute all tests in `Tests` directory. 
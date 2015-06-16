#Contributing

### Getting Started

Submit a ticket for your issue, assuming one does not already exist.
  * Raise it on our [Issue Tracker](https://github.com/deliciousbrains/wp-amazon-web-services)
  * Clearly describe the issue, including steps to reproduce the bug (if applicable).
  * If it's a bug, make sure you fill in the earliest version that you know has the issue as well as the version of WordPress you're using.

## Making Changes

* Fork the repository on GitHub
* From the `develop` branch on your forked repository, create a new branch and make your changes
  * It is suggested that your new branch use a name that briefly describes the feature or issue.
  * Ensure you stick to the [WordPress Coding Standards](http://codex.wordpress.org/WordPress_Coding_Standards)
* When committing, use a [well-formed](http://robots.thoughtbot.com/5-useful-tips-for-a-better-commit-message) [commit](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html) [message](http://who-t.blogspot.com/2009/12/on-commit-messages.html)
* Push the changes to your fork and submit a pull request to the `develop` branch of the plugin's repository

## Code Documentation

* Code comments should be added to all new functions/methods.
* Comments should tell you the "what" & "why". You'll typically want a one-liner that says what it does, hopefully why, but not how. Only very rarely should they tell you how (e.g. when the code is necessarily complex).
* Also see the [WordPress PHP Documentation Standards](http://make.wordpress.org/core/handbook/inline-documentation-standards/php-documentation-standards/) doc for general guidelines and best practices.
* We currently suggest implementing the `@param` & `@return` PHPdoc tags for every function/method if applicable.

At this point you're waiting on us to merge your pull request. We'll review all pull requests, and make suggestions and changes if necessary.

# Additional Resources
* [GitHub Help — Forking](https://help.github.com/articles/fork-a-repo)
* [GitHub Help — Syncing a Fork](https://help.github.com/articles/syncing-a-fork)
* [GitHub Help — Pull Requests](https://help.github.com/articles/using-pull-requests#before-you-begin)
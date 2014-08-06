# Pocket Priority Tags docs
This CLI tool allows you to handle your ever-growing [Pocket](http://getpocket.com) reading list using tags to rank 
articles based on social networks relevance. 

## How it works

Each item from your reading list is weighted based on its number of Facebook likes/shares, Google+ +1s and Twitter
retweets.

After that, `001`, `010` and `040` tags are assigned to the respective TOP1, TOP10 and TOP40 items. Items belonging 
to more than one group are tagged with both.

Also, items with relevance higher than 100 are tagged with `zz-ROUNDED_RELEVANCE_VALUE` tags.

![Result](https://cloud.githubusercontent.com/assets/1196324/3821358/095b0006-1d0b-11e4-95eb-6a38f06a7ee9.png)

## Installation

PocketPriorityTags uses [Composer](http://getcomposer.org/doc/00-intro.md) so you only need the following
command to set all the dependencies.

```
composer install
```

## Authenticate

The [Pocket API](http://getpocket.com/developer/) uses [OAuth like authentication](http://getpocket.com/developer/docs/authentication)
but as the tool is conceived as a CLI —and that I'm lazy when coding sleep deprivating dirty scripts for me— no OAuth redirect is 
possible so you'll need some extra work before to start using it.

### Get Consumer Key

You'll need to [register an app](http://getpocket.com/developer/apps/new) with Pocket, and use the generated consumer 
key provided in order for you to be able to connect to the API.

### Get Request Token

I just did once for my app. You can do it with [jshawl/pocket-oauth-php](https://github.com/jshawl/pocket-oauth-php) and
add it along with the Consumer Key to `OAuthConfig.php` file.

```php
private static $consumerKey = '1234-abcd1234abcd1234abcd1234';
private static $accessToken = 'a1234567-1a2b-12ab-abcd-a12345';
```

## Usage

### Preview

```
$ php pocket-priority-tags.php --dry-run
```

### Apply changes

```
$ php pocket-priority-tags.php
```

### Cron job

I wrote PocketPriorityTags as a CLI tool because is not a fast script so my recommendation is that you schedule it with 
`crontab`'s help. You can't run too instances of the script so don't worry to schedule it too often.

## Misc

### Motivation

I just wanted to try Composer, so I spent a few sleep hours searching for compatible libs and adapting my quick and
ugly PHP script into something that other people can enjoy.

And yes, before you ask, I use PHP for internet related task automation, and I like it.


### Contributing

Contributions are encouraged and welcome; to keep things organised, all bugs and requests should be
opened in the GitHub issues tab for the main project.

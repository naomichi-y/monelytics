Laravel User Agent
==================

[![Latest Stable Version](http://img.shields.io/github/release/jenssegers/laravel-agent.svg)](https://packagist.org/packages/jenssegers/agent) [![Total Downloads](http://img.shields.io/packagist/dm/jenssegers/agent.svg)](https://packagist.org/packages/jenssegers/agent) [![Build Status](http://img.shields.io/travis/jenssegers/laravel-agent.svg)](https://travis-ci.org/jenssegers/laravel-agent) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-agent.svg)](https://coveralls.io/r/jenssegers/laravel-agent)

A user agent class for Laravel, based on [Mobile Detect](https://github.com/serbanghita/Mobile-Detect) with extended functionality.

Installation
------------

Install using composer:

```bash
composer require jenssegers/agent
```

Add the service provider in `app/config/app.php`:

```php
'Jenssegers\Agent\AgentServiceProvider',
```

And add the Agent alias to `app/config/app.php`:
```php
'Agent' => 'Jenssegers\Agent\Facades\Agent',
```

Basic Usage
-----------

All of the original [Mobile Detect](https://github.com/serbanghita/Mobile-Detect) functionality is still available, check out more examples over at https://github.com/serbanghita/Mobile-Detect/wiki/Code-examples

### Is?

Check for a certain property in the user agent.

```php
Agent::is('Windows');
Agent::is('Firefox');
Agent::is('iPhone');
Agent::is('OS X');
```

### Magic is-method

Magic method that does the same as the previous `is()` method:

```php
Agent::isAndroidOS();
Agent::isNexus();
Agent::isSafari();
```

### Mobile detection

Check for mobile device:

```php
Agent::isMobile();
Agent::isTablet();
```

### Match user agent

Search the user agent with a regular expression:

```php
Agent::match('regexp');
```

Additional Functionality
------------------------

Since the original library was inspired on CodeIgniter, I decided to add some additional functionality:

### Accept languages

Get the browser's accept languages. Example:

```php
$languages = Agent::languages();
// ['nl-nl', 'nl', 'en-us', 'en']
```

### Device name

Get the device name, if mobile. (iPhone, Nexus, AsusTablet, ...)

```php
Agent::device();
```

### Operating system name

Get the operating system. (Ubuntu, Windows, OS X, ...)

```php
Agent::platform();
```

### Browser name

Get the browser name. (Chrome, IE, Safari, Firefox, ...)

```php
Agent::browser();
```

### Desktop detection

Check if the user is a desktop.

```php
Agent::isDesktop();
```

*This checks if a user is not a mobile device, tablet or robot.*

### Robot detection

Check if the user is a robot.

```php
Agent::isRobot();
```

### Browser/platform version

MobileDetect recently added a `version` method that can get the version number for components. To get the browser or platform version you can use:

```php
$browser = Agent::browser();
$version = Agent::version($browser);

$platform = Agent::platform();
$version = Agent::version($platform);
```

*Note, the version method is still in beta, so it might not return the correct result.*

## License

Laravel User Agent is licensed under [The MIT License (MIT)](LICENSE).

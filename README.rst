Sismo: Your Continuous Testing Server
=====================================

`Sismo`_ is a *Continuous Testing Server* written in PHP.

Unlike more "advanced" Continuous Integration Servers (like Jenkins), Sismo
does not try to do more than getting your code, running your tests, and send
you notifications.

What makes Sismo special?
-------------------------

Sismo has been optimized to run *locally* on your computer for your *Git*
projects. Even if it can test *remote* repositories, Sismo is better used as a
local ``post-commit`` hook. Whenever you commit changes locally, Sismo runs
the tests and give you *immediate* feedback *before* you actually push your
modifications to the remote repository. So, Sismo is a nice complement to your
Continuous Integration Server.

Sismo is *language and tool agnostic*. Just give it a command that knows how
to run your tests and returns a non-zero exit code when tests do not pass.

Sounds good? There is more. Sismo is insanely *easy to install* (there is only
one PHP file to download), *easy to configure*, and it comes with a *gorgeous
web interface*.

.. image:: http://sismo-project.org/images/sismo-home.png

Installation
------------

Installing Sismo is as easy as downloading the `sismo.php`_ file and put it
somewhere under your web root directory. That's it, the CLI tool and the web
interface is packed into a single PHP file.

Note that Sismo needs at least PHP 5.3.2 to run.

Configuration
-------------

By default, Sismo reads its configuration from ``~/.sismo/config.php``::

    <?php

    $projects = array();

    // create a Growl notifier (for MacOS X)
    $notifier = new Sismo\GrowlNotifier('pa$$word');

    // create a DBus notifier (for Linux)
    //$notifier = new Sismo\DBusNotifier();

    // add a local repository hosted on Github
    $projects[] = new Sismo\GithubProject('Twig (Local)', '/Users/fabien/Twig', $notifier);

    // add a remote Github repository
    $projects[] = new Sismo\GithubProject('Twig', 'fabpot/Twig', $notifier);

    // add a project with custom settings
    $sf2 = new Sismo\Project('Symfony');
    $sf2->setRepository('https://github.com/symfony/symfony.git');
    $sf2->setBranch('master');
    $sf2->setCommand('./vendors.sh; phpunit');
    $sf2->setSlug('symfony-local');
    $sf2->setUrlPattern('https://github.com/symfony/symfony/commit/%commit%');
    $sf2->addNotifer($notifier);
    $projects[] = $sf2;

    return $projects;

For notifications, you can also use any Cruise Control "tray" software as
Sismo also exposes an XML file in the Cruise Control format::

    http://path/to/sismo.php/dashboard/cctray.xml

Use `CCMenu`_ on Mac, `CCTray`_ on Windows, `JCCTray`_ on Windows or Linux, or
`CCMonitor`_ for Firefox.

Using Sismo
-----------

Build all configured projects by running the ``build`` command:

    $ php sismo.php build --verbose

If a build fails, Sismo will send notifications. You can also check the build
output from the web interface:

.. image:: http://sismo-project.org/images/sismo-project.png

The ``build`` command is quite powerful and has many options. Learn more by
appending ``--help``:

    $ php sismo.php build --help

To make Sismo run whenever you commit some changes, use this ``post-commit``
script:

    #!/bin/sh

    php /path/to/sismo.php --quiet build symfony-local `git log -1 HEAD --pretty="%H"` &

``symfony-local`` is the slug of the project. You can also create a
``post-merge`` script if you want to run Sismo when you merge branches.

Limitations
-----------

Sismo is small and simple and it will stay that way. Sismo will never have the
following:

* a queue (if a project is already being built, newer commits are ignored);
* a web interface for configuration;
* metrics support;
* plugin support;
* other SCM support;
* slaves support;
* built-in authentication.

... and probably the feature you have in mind right now and all the ones you
will think of later on ;)

Tips and Recipes
----------------

Change the default Location
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Set the following environment variables to customize the default locations use
by Sismo::

    # in a .htaccess or httpd.conf Apache configuration file

    SetEnv SISMO_DATA_PATH "/path/to/sismo/data"
    SetEnv SISMO_CONFIG_PATH "/path/to/sismo/config"

    # for the CLI tool

    export SISMO_DATA_PATH=/path/to/sismo/data/
    export SISMO_CONFIG_PATH=/path/to/sismo/config/

Tracking multiple Branches
~~~~~~~~~~~~~~~~~~~~~~~~~~

To track multiple branches of a project, just make their names unique and set
the branch name::

    $projects[] = new Sismo\GithubProject('Twig (master branch)', '/Users/fabien/Twig');

    $projects[] = new Sismo\GithubProject('Twig (feat-awesome branch)', '/Users/fabien/Twig@feat-awesome');

Note that Sismo uses the same clone for projects sharing the same repositories
URL.

Running Sismo for Remote Repositories
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using Sismo for remote repositories is as simple as adding the Sismo building
tool in a crontab entry:

    0 12 * * * php /path/to/sismo.php --quiet

History in the Web Interface
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The build history for a project in the web interface is different from the
project history. It is sorted in the order of the builds so that the latest
build output is always at your fingertips.

.. _Sismo:     http://sismo-project.org/
.. _sismo.php: http://sismo-project.org/get/sismo.php
.. _CCMenu:    http://ccmenu.sourceforge.net/
.. _CCTray:    http://confluence.public.thoughtworks.org/display/CCNET/CCTray
.. _CCMonitor: http://code.google.com/p/cc-monitor/
.. _JCCTray:   http://sourceforge.net/projects/jcctray/

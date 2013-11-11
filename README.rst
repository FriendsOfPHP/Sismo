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
modifications to the remote repository. So, Sismo is a nice *complement* to
your Continuous Integration Server.

Sismo is *language and tool agnostic*. Just give it a command that knows how
to run your tests and returns a non-zero exit code when tests do not pass.

Sounds good? There is more. Sismo is insanely *easy to install* (there is only
one PHP file to download), *easy to configure*, and it comes with a *gorgeous
web interface*.

.. image:: http://sismo.sensiolabs.org/images/sismo-home.png

Installation
------------

Installing Sismo is as easy as downloading the `sismo.php`_ file and put it
somewhere under your web root directory. That's it, the CLI tool and the web
interface is packed into a single PHP file.

Note that Sismo needs at least PHP 5.3.3 to run.

Configuration
-------------

By default, Sismo reads its configuration from ``~/.sismo/config.php``:

.. code-block:: php

    <?php

    $projects = array();

    // create a Growl notifier (for MacOS X)
    $notifier = new Sismo\Notifier\GrowlNotifier('pa$$word');

    // create a DBus notifier (for Linux)
    //$notifier = new Sismo\Notifier\DBusNotifier();

    // create a CrossFinger notifier (notify on failed or recovering build)
    //$notifier = new Sismo\Contrib\CrossFingerNotifier($notifier);
    // or if you want to chain multiple notifiers
    //$notifier = new Sismo\Contrib\CrossFingerNotifier(array($mailNotifier, $myAwesomeNotifier));

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
    $sf2->addNotifier($notifier);
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

.. code-block:: text

    $ php sismo.php build --verbose

If a build fails, Sismo will send notifications. Use the ``output`` command to
see the latest build output of a project:

.. code-block:: text

    $ php sismo.php output twig

If you have configured Sismo to be accessible from the web interface, you can
also check the build outputs there:

.. image:: http://sismo.sensiolabs.org/images/sismo-project.png

If your web server runs under a different user than the one you use on the
CLI, you will need to set some environment variables in your virtual host
configuration:

.. code-block:: apache

    SetEnv SISMO_DATA_PATH "/path/to/sismo/data"
    SetEnv SISMO_CONFIG_PATH "/path/to/sismo/config.php"

The ``build`` command is quite powerful and has many options. Learn more by
appending ``--help``:

.. code-block:: bash

    $ php sismo.php build --help

To make Sismo run whenever you commit some changes, save this script in your
project as ``.git/hooks/post-commit`` and make sure it's executable:

.. code-block:: bash

    #!/bin/sh

    nohup php /path/to/sismo.php --quiet --force build symfony-local `git log -1 HEAD --pretty="%H"` &>/dev/null &

``symfony-local`` is the slug of the project. You can also create a
``post-merge`` script if you want to run Sismo when you merge branches.

If you are running Sismo (with the single PHP file) with PHP 5.4.0, you can
use the Sismo build-in web server:

.. code-block:: bash

    $ php sismo.php run localhost:9000

And then open the browser and point it to http://localhost:9000/sismo.php

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

Set the following environment variables to customize the default locations
used by Sismo:

.. code-block:: apache

    # in a .htaccess or httpd.conf Apache configuration file

    SetEnv SISMO_DATA_PATH "/path/to/sismo/data"
    SetEnv SISMO_CONFIG_PATH "/path/to/sismo/config.php"

    # for the CLI tool

    export SISMO_DATA_PATH=/path/to/sismo/data/
    export SISMO_CONFIG_PATH=/path/to/sismo/config.php

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

.. code-block:: text

    0 12 * * * php /path/to/sismo.php --quiet

For GitHub projects, and other systems that support post-receive URL hooks,
you can set up Sismo to build automatically when a new revision is pushed.
You need to set an environment variable in your Apache configuration:

.. code-block:: apache

    # in a .htaccess or httpd.conf Apache configuration file

    SetEnv SISMO_BUILD_TOKEN "YOUR_TOKEN"

You can also set an environment variable in your config file
(``~/.sismo/config.php``):

.. code-block:: php

    putenv('SISMO_BUILD_TOKEN=YOUR_TOKEN');

Replace YOUR_TOKEN with something more secure, as anyone with this token
could use it to trigger builds. Then set your post-receive URL appropriately.
For example::

    http://path/to/sismo.php/your_project/build/YOUR_TOKEN

History in the Web Interface
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The build history for a project in the web interface is different from the
project history. It is sorted in the order of the builds so that the latest
build output is always at your fingertips.

Adding a Notifier
~~~~~~~~~~~~~~~~~

Sismo comes with the most common notifiers but you can create new ones very
easily: extend the `Sismo\Notifier\Notifier` abstract class and implement the
`notify()` method:

.. code-block:: php

    public function notify(Commit $commit)
    {
        // do something with the commit
    }

The `Commit`_ object has many methods that gives you a lot of information
about the commit and its build. You can also get general information about the
project by calling `getProject()`_.

Use Sismo with composer
~~~~~~~~~~~~~~~~~~~~~~~

If a majority of yours projects use `composer`_, you can configure Sismo
to install dependency before running `phpunit`. Add the following code
to your config file:

.. code-block:: php

    Sismo\Project::setDefaultCommand('if [ -f composer.json ]; then composer install --dev; fi && phpunit');

.. _Sismo:        http://sismo.sensiolabs.org/
.. _sismo.php:    http://sismo.sensiolabs.org/get/sismo.php
.. _CCMenu:       http://ccmenu.sourceforge.net/
.. _CCTray:       http://confluence.public.thoughtworks.org/display/CCNET/CCTray
.. _CCMonitor:    http://code.google.com/p/cc-monitor/
.. _JCCTray:      http://sourceforge.net/projects/jcctray/
.. _Commit:       http://sismo.sensiolabs.org/api/index.html?q=Sismo\Commit
.. _getProject(): http://sismo.sensiolabs.org/api/index.html?q=Sismo\Project
.. _composer:     https://getcomposer.org/doc/00-intro.md#globally

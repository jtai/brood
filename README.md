What is Brood?
==============

Brood is a flexible deployment framework written in PHP. Brood leverages
[Gearman](http://gearman.org/) to deploy to multiple servers in parallel.
Deploys are triggered by a Gearman client, the Overlord. Most deployment tasks
(actions) are executed by Gearman workers running on each server called Drones.
Each action is encapsulated in a PHP class. What servers to deploy to and what
actions to run on them are defined in an XML config file.


Why Brood?
==========

We created Brood because we wanted a simple, flexible deployment system that
exploited parallelism to do deploys as quickly as possible. We found [Fabric](http://fabfile.org/)
to be simple and flexible, but lacking fine-grained control over parallelism
during the deploy process. On the other hand, we found [Capistrano](http://capify.org/) to be too
Rails-centric for our PHP applications.

There are no pre-determined deployment phases in Brood, nor do you specify on
the command line which deploy tasks to be executed. Actions simply run one
after the other as specified in the config file. When you specify that an
action should be run, you also specify which hosts or host groups the action
should be run on. By default, the action will be run on all hosts in parallel,
but you can specify a maximum concurrency for each host group. This allows you
to effortlessly do things such as: run `git pull` on all servers simultaneously,
then run the minifier on all static asset servers simultaneously, then restart
Apache on two of the application servers and one of the static asset servers at
a time until all servers have been restarted.

Brood divides the responsibility of coordinating actions and the responsibility
of actually executing actions into separate entities: the Overlord and the
Drone. In this respect, it is more similar to [Chef](http://www.opscode.com/chef/) than Fabric or Capistrano
which both execute commands on servers directly with SSH. This design keeps the
implementation of both the Overlord and the Drone very simple. The Overlord
submits jobs to the Gearman job server in batches, and each Drone picks up jobs
and executes the requested action. The hard work of parallelization is handled
by Gearman.


Requirements
============

  * [PHP](http://php.net/) >= 5.3
  * SimpleXML PHP extension (included with PHP by default)
  * [Gearman job server](https://launchpad.net/gearmand)
  * [Gearman PHP extension](http://pecl.php.net/package/gearman)
  * A way to ensure the Drones are restarted when they exit, e.g., [Supervisor](http://supervisord.org/)

Brood tries to autodetect the user triggering the deploy by calling
`posix_getlogin()` from the POSIX PHP extension. You can bypass this call by
explicitly passing the user to the Overlord with the `-u` option.

The `Announce\NewRelic` action requires the cURL PHP extension.


Installation
============

Distribute Brood
----------------

Install the Brood source code on each server you wish to deploy to. If you are
already using git, cloning the Brood repository may be the easiest way to
accomplish this.

If you intend to run the Overlord on a separate machine, install the Brood
source code on that machine, too. Drones will be long-running processes; the
Overlord will only run for the duration of the deploy. You can run the Overlord
on one of the servers you are deploying to, on your SCM or CI server, on your
laptop, or any combination of the above as long as the Overlord can connect to
the Gearman job server.

Both the `bin/drone.php` and `bin/overlord.php` scripts are thin wrappers
around `Drone`/`Overlord` classes that do the bulk of the work. Instead of
using the `bin/overlord.php` script, you may want to implement a different
wrapper to integrate the Overlord with your preferred method of triggering
deploys -- your CI server, a post-commit hook, [Hubot](http://hubot.github.com/), carrier pigeon, etc.

If your application is a PHP application, and especially if you write your own
`overlord.php` script, you may opt to copy the `library/Brood` directory into
your application's library directory and commit it to your application's source
code repository. After the initial deploy, it is possible to deploy updates to
Brood with Brood, provided you don't push an update that breaks Brood.

Configure Brood
---------------

You will need to write a Brood config file that describes which servers to
deploy to and what actions to run on each server. An example config file is
distributed with Brood, but read the Brood Configuration File section
below for a more complete reference.

We recommend naming your config file `brood.xml` and committing it to your
application's source code repository so that changes are revisioned and deploys
will automatically keep the file up to date. This is not a requirement, however
-- you can put your config file on a shared filesystem or even copy it manually
to the Overlord and each Drone, as long as the Overlord and Drones can read the
file when they start up. In fact, the Drones only need the config file to find
the Gearman job server. The Overlord sends each Drone a copy of its configuration
via Gearman every time it instructs a Drone to perform an action.

Configure Drone to Restart Automatically
----------------------------------------

The `bin/drone.php` script is a Gearman worker -- it connects to the Gearman
job server and waits for a Gearman job from the Overlord. When it receives a
job, it performs the requested action, then exits. This is because the action
it just performed (e.g., `git pull`) may have altered the source code of one
the action classes used later in the deploy process. Exiting after every action
guarantees the next action will start with the latest code.

The Drone will also exit if the if the job timeout is reached or the connection
to the Gearman job server is interrupted.

Thus, the Drone must be supervised by some other process that will restart it
when it exits. There are several tools designed to do this, including [Supervisor](http://supervisord.org/),
[daemontools](http://cr.yp.to/daemontools.html), and [runit](http://smarden.org/runit/). How to configure the Drone to restart automatically
will vary depending on which supervisor you choose.

Run `php bin/drone.php -h` to get a list of supported options. In particular,
you may need to use the `-c` option to specify the path your Brood config file.


The Brood Configuration File
============================

The Brood config file specifies three main things:

  1. How to connect to the Gearman job server
  2. What hosts to deploy to
  3. What actions to run on those hosts

The Brood config file is an XML file; the top-level element is `<brood>`.

Connecting to Gearman
---------------------

The Gearman configuration is used by the Overlord and Drones to determine how
to connect to the Gearman job server.

Each config file should have exactly one `<gearman>` element. A `timeout`
attribute can be specified to set the timeout for Gearman socket I/O activity.
Within the `<gearman>` element, there should be at least one `<server>`
element.

```xml
<brood>
    <gearman timeout="3600000">
        <server>gearmand.example.com</server>
    </gearman>
</brood>
```

Host Groups
-----------

You can specify groups of hosts by including one or more `<hostgroup>` elements.
Hosts in a group typically perform the same function and may be behind the same
load balancer. When you specify actions (covered in the next section), you can
control the maximum concurrency that an action is performed within a host group
-- for example, you may want to only restart the web servers two at a time so
the site doesn't go down during your deploy.

Each `<hostgroup>` element should have a `name` attribute so you can refer to
it later in your action definitions.

```xml
<brood>
    <hostgroup name="www">
        <!-- ... -->
    </hostgroup>
    <hostgroup name="static">
        <!-- ... -->
    </hostgroup>
</brood>
```

Each `<hostgroup>` element should have one or more `<host>` elements. Each
`<host>` element can have an `alias` attribute to specify a shorter name for
the host to be used in log output.

```xml
<brood>
    <hostgroup name="static">
        <host alias="static1">static1.example.com</host>
        <host alias="static1">static2.example.com</host>
    </hostgroup>
</brood>
```

Actions
-------

For Brood to do anything useful, you will need to define one or more actions.
Each action is specified with an `<action>` element and a required `class`
attribute that names the PHP class that performs that action. The PHP class
should implement the `Brood\Action\Action` interface, or extend from
`Brood\Action\AbstractAction` (which also implements the interface). For more
information about writing your own action classes, see the Writing Custom
Actions section below.

Each action will be run in the order it appears in the config file. Actions are
run serially, so the first action must complete on all hosts before Brood moves
on to the next action.

```xml
<brood>
    <action class="Brood\Action\Announce\Email">
        <!-- ... -->
    </action>
    <action class="Brood\Action\Distribute\Git">
        <!-- ... -->
    </action>
    <action class="Brood\Action\Restart\Apache">
        <!-- ... -->
    </action>
</brood>
```

For every action, you must specify one or more hosts to run the action on.
There are three ways to do this.

If you want the action to run on the Overlord itself, add an empty `<overlord>`
element inside the `<action>` element. The action will be run in the Overlord
process and will not be dispatched via Gearman. A side effect of this is that
the action will not be run in parallel with other hosts. If you are running
the Overlord on one of the servers being deployed to, it may be better to run a
Drone on the server and have the Overlord dispatch to the Drone running on the
same server. The `<overlord>` element is primarily meant for tasks that only
run on the Overlord, such as sending e-mail notifications about the deploy.

```xml
<brood>
    <action class="Brood\Action\Announce\Email">
        <overlord />
    </action>
</brood>
```

If you want the action to run on a group of hosts, add a `<hostgroup>` element
inside the `<action>` element. You can have more than one `<hostgroup>` element
in an `<action>` element.

```xml
<brood>
    <action class="Brood\Action\Distribute\Git">
        <hostgroup>www</hostgroup>
        <hostgroup>static</hostgroup>
    </action>
</brood>
```

The action will be run on all hosts in the host group(s) in parallel. To limit
the concurrency within a group, set the `concurrency` attribute on the
`<hostgroup>` element. Given the host group definitions in the example Brood
config file, the following example will restart Apache on www1, www2, and
static1 in parallel. When Apache has been restarted on all three hosts, Apache
will be restarted on www3, www4, and static2 in parallel.

```xml
<brood>
    <action class="Brood\Action\Restart\Apache">
        <hostgroup concurrency="2">www</hostgroup>
        <hostgroup concurrency="1">static</hostgroup>
    </action>
</brood>
```

You can also specify that an action be run on individual hosts by adding
`<host>` elements to an `<action>` element. The action will be run on these
hosts in parallel along with any hosts specified by group.

Actions can also take parameters. Some parameters are required; other
parameters are optional. The action may either use a default value or not
perform some part of the action if the parameter is not set. The specific
parameters and whether or not they are required varies from action to action,
but all parameters are specified the same way, with a `<parameters>` element in
the `<action>` element. Each parameter has its own element in the
`<parameters>` element.

In the example below, the `to`, `from`, and `subject` parameters are all
required by the `Announce\Email` action. Some parameters can be repeated -- in
this case, the `to` parameter can be specified multiple times to send
announcements to more than one address.

```xml
<brood>
    <action class="Brood\Action\Announce\Email">
        <overlord />
        <parameters>
            <to>team@example.com</to>
            <from>deployments@example.com</from>
            <subject>Deployment</subject>
        </parameters>
    </action>
</brood>
```

In the example below, the `sudo` parameter is optional. If it is present, the
`Distribute\Git` action will be run after sudo-ing to the specified user.

```xml
<brood>
    <action class="Brood\Action\Distribute\Git">
        <hostgroup>www</hostgroup>
        <hostgroup>static</hostgroup>
        <parameters>
            <sudo>deploy</sudo>
            <directory>/var/www/exampleapp</directory>
        </parameters>
    </action>
</brood>
```

Actions Included with Brood
===========================

A small number of actions are distributed with Brood. If you write
generally-applicable actions (see the Writing Custom Actions section below),
please contribute them to the project!

HellowWorld
-----------

The `HelloWorld` action simply logs an informational message: "Hello world!".
It is used to test communication between the Overlord and the Drones when
installing Brood.

Changelog\Git
-------------

The `Changelog\Git` action uses `git diff` to generate a summary of changed
files from the currently-deployed ref and the ref being deployed. The changelog
is added to the in-memory config and is available for subsequent `Announce`
actions.

Required Parameters

  * `directory` - Path to git repository
  * `prev_ref` - Currently-deployed ref
  * `ref` - ref being deployed

Optional Parameters

  * `diff_url` - `sprintf()`-formatted URL that links to a diff of the changes being deployed, e.g., `https://github.com/user/project/compare/%s...%s`
  * `sudo` - Run `git` as this user

Parameters Added to Global Config

  * `changelog`

Announce\Email
--------------

The `Announce\Email` action sends an e-mail announcing the deploy.

Required Parameters

  * `to` (multiple allowed)
  * `from`
  * `subject`

Optional Parameters

  * `user` - User that triggered the deploy
  * `message` - Short message describing the changes being deployed
  * `changelog` - List of changed files being deployed

Announce\NewRelic
-----------------

The `Announce\NewRelic` action makes a request to [New Relic](http://newrelic.com/)'s deployment
API to note that a deploy occurred.

Required Parameters

  * `api_key`
  * `app_name` or `application_id` (multiple allowed)

Optional Parameters

  * `user` - User that triggered the deploy
  * `message` - Short message describing the changes being deployed
  * `changelog` - List of changed files being deployed
  * `ref` - ref being deployed

Distribute\Git
--------------

The `Distribute\Git` action does a `git pull` to update the source code on the
target server. The repository should already be on the right branch and have a
default remote specified.

Required Parameters

  * `directory` - Path to git repository

Optional Parameters

  * `ref` - Do a `git reset --hard` to this ref, used if you want to roll back to a previous ref
  * `clean` - If this empty element is present, a `git clean -dxf` will be run to ensure the source tree only contains tracked files
  * `sudo` - Run `git` as this user

Restart\Apache
--------------

The `Restart\Apache` action restarts the Apache web server. You may want to do
this at the end of your deploy to clear the APC cache or pick up new
configuration settings.

The action tries to detect the name of the init script automatically to account
for differences between RedHat-based and Debian-based distributions.

Optional Parameters

  * `verb` - Pass this verb to the init script, defaults to `restart`
  * `sudo` - Run init script as this user

Restart\Varnish
---------------

The `Restart\Varnish` action restarts Varnish. You may want to do this at the
end of your deploy to clear the Varnish cache or pick up new configuration
settings.

Optional Parameters

  * `verb` - Pass this verb to the init script, defaults to `restart`
  * `sudo` - Run init script as this user


Writing Custom Actions
======================

Only a few actions are distributed with Brood, but you can easily write your
own actions. If you write generally-applicable actions, please contribute them
to the project! Each action is a PHP class that implements the `Brood\Action\Action`
interface. The `Brood\Action\AbstractAction` class implements a few convenience
functions, so it is recommended to extends this class instead of implementing
the interface directly.

The interface only defines two methods: `setContext()` and `execute()`. The
action dispatcher calls `setContext()` to inject the `GearmanJob` object, the
config file, etc. into the action object.  If you are extending the abstract
class, you should not need to implement `setContext()` at all.

The `execute()` method takes no arguments. A typical `execute()` implementation
will call `getParameter()` or `getRequiredParameter()` to get its configuration,
call `log()` to log an informational message about the action it is about to
perform, then performs the action, possibly calling `sudo()` (a wrapper around
`exec()`) to execute another binary on the system.

Sub-Actions
-----------

If your custom action wraps another action, you can create an instance of the
sub-action and use the `proxyContext()` method to proxy your action's context
to the sub-action. Then call `execute()` on the sub-action to run it.

Unless your action and sub-action must be run atomically, consider making your
action completely separate from the sub-action and using the config file to run
one after the other. For example, if you have some application-specific
compilation or minification steps to perform, these can be written as a
separate action from the `Distribute\Git` action and run directly afterward in
the config file. On the other hand, if you need to take a server out of the
load balancer, restart Apache, then re-insert it into the load balancer, you
must write an action that wraps the `Restart\Apache` action. If they are
separate actions, all servers will be removed from the load balancer, then all
servers will have Apache restarted, then all servers will be re-inserted into
the load balancer. Obviously, your site will be down for the duration of the
restart.

Loading Your Action Class
-------------------------

Brood's autoloader automatically loads all classes in the `Brood` namespace. If
you are writing an application-specific action, use the `file` attribute of the
`<action>` element in your config file to specify the file that contains your
action class.

```xml
<brood>
    <action class="Foo\Brood\Action\CustomStuff" file="/var/www/foo/library/Foo/Brood/Action/CustomStuff.php">
        <!-- ... -->
    </action>
</brood>
```

If you are writing a generally-applicable action (e.g., support for version
control systems), please consider putting it in the `Brood\Action` namespace
and contributing it back to the project!


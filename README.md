[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/shopwareLabs/psh/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shopwareLabs/psh/?branch=master)[![Build Status](https://scrutinizer-ci.com/g/shopwareLabs/psh/badges/build.png?b=master)](https://scrutinizer-ci.com/g/shopwareLabs/psh/build-status/master)[![codecov](https://codecov.io/gh/shopwareLabs/psh/branch/master/graph/badge.svg)](https://codecov.io/gh/shopwareLabs/psh)

[![Latest Stable Version](https://poser.pugx.org/shopware/psh/v/stable)](https://packagist.org/packages/shopware/psh)
[![Total Downloads](https://poser.pugx.org/shopware/psh/downloads)](https://packagist.org/packages/shopware/psh)
[![Latest Unstable Version](https://poser.pugx.org/shopware/psh/v/unstable)](https://packagist.org/packages/shopware/psh)
[![License](https://poser.pugx.org/shopware/psh/license)](https://packagist.org/packages/shopware/psh)

PSH - PHP shell helper
====================

**Keep using your standard shell scripts**

PSH is intended to be a **simple** and **easy** alternative to other build script solutions.


Table of contents
------------

* [Introduction](#introduction)
* [Installation](#installation)
 * [Through composer](#through-composer)
 * [As a PHAR archive (preferred)](#as-a-phar-archive-preferred)
 * [Build it yourself](#build-it-yourself)
* [Usage](#usage)
* [Configuration](#configuration)
    * [Paths](#paths)
    * [Placeholders](#placeholders)
    * [Constants](#constants)
    * [Variables](#variables)
    * [Dotenv](#dotenv)
    * [Require](#require)
    * [Templates](#templates)
    * [Environments](#environments)
    * [Headers](#headers)
    * [Overriding configuration file](#overriding-configuration-file)
    * [Importing configuration files](#importing-configuration-files)
* [PSH-Scripts](#sh-scripts)
    * [Defining placeholders](#defining-placeholders)
    * [Including other actions](#including-other-actions)
    * [Including other scripts](#including-other-scripts)
    * [On demand templates](#on-demand-templates)
    * [Open a ssh connection to another machine](#open-a-ssh-connection-to-another-machine)
    * [Ignoring if a statement errored](#ignoring-if-a-statement-errored)
    * [Breaking statements into multiple lines](#breaking-statements-into-multiple-lines)
    * [Description](#description)
    * [Downsides](#downsides)
* [BASH-Scripts](#bash-scripts)
* [Executing it](#executing-it)
* [Bash Autocompletion](#bash-autocompletion)

Introduction
------------

You do not have to learn a new - and in most cases much more verbose - language, but can scale your existing skills 
on the command line.

Key benefits are:

* Share your existing shell scripts with your team members
* Add error handling if single statement in the *sh* scripts fails
* Replace placeholders in *sh* scripts with variables
* Overload variables and scripts in an environment configuration
 
Installation
------------

Although you can use PSH as a composer dependency, we recommend to use the **PHAR archive** instead. PSH only communicates through
the shell with your application and therefore does not need any influence on your other project dependencies.

### Through composer

Locally:

```sh
composer require shopware/psh --dev
```

Globally:

```sh
composer global require shopware/psh
```

### As a PHAR archive

Download `psh.phar` to your local environment. 

```sh
wget https://shopwarelabs.github.io/psh/psh.phar # PHP7 Version
chmod +x psh.phar
```

### As a PHAR archive via phive (preferred)

```sh
phive install psh
```

If you want to know how to install phive please click [here](https://phar.io/#Install). 

### Build it yourself

PSH is used to build itself. You need to clone the repository and install the composer dependencies by yourself first.

```sh
git clone https://github.com/shopwareLabs/psh.git
cd psh
composer install # assuming you have composer installed globally
composer bin box install # box is needed to build phar file

./psh unit # verify your installation by executing the test suite.
./psh build 
```

This will create a release phar in the `build/psh.phar` directory. The project itself requires PHP 7.2+. 

Usage
------------

> Notice: The YAML configuration format is deprecated and will be removed in version 2.0. If you need the old documentation, please refer to [older versions](https://github.com/shopwareLabs/psh/blob/v1.3.0/README.md) of this document

PSH is a CLI application. Before you can use it you need to create a configuration file in your project root named `.psh.xml` or `.psh.xml.dist`.

## Configuration

The minimum required file looks like this:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<psh xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopwareLabs/psh/master/resource/config.xsd">

</psh>
```
The root element (`<psh>`) can contain one many or all if the following configuration options.

#### Paths

In order to use psh as a script executor, you need to define the locations in which to search.

```xml
<path>deployment/scripts</path>
<path>test/scripts</path>
<path>more/scripts</path>
```

PSH will then search in all these locations for `*.sh` files. These scripts can then be executed through PSH.

> Notice: If the script name starts with a dot (`.`) it will be excluded from the listing, but is callable like any other script. `> psh.phar .hidden-action`

#### Placeholders

Placeholders in your scripts looks like this:

```sh
ln -s __PATH__
```

The placeholder `__PATH__` now needs to be part of your configuration file as either a constant or a variable.

> Notice: All placeholders must be written in uppercase in scripts. Even if defined otherwise in configuration, replacement only works uppercase. With (sic!) add the end of a placeholder it will be escaped. As an example `__DIR__(sic!)`.  

#### Constants

Constants are the basic solution to placeholder replacements. You define placeholders in your config like this:

```xml
<placeholder>
    <const name="PATH">/var/www</const>
</placeholder>
```

This will then execute 

```sh
ln -s /var/www
```

#### Variables

With variables you can use the output of one line shell statements in your scripts.
  
```xml
<placeholder>
    <dynamic name="PATH">echo $HOME</dynamic>
</placeholder>
```

The Variables get executed before the actual statement is executed, but you can imagine the outcome to be equivalent to:

```sh
ln -s `echo $HOME`
```

#### Dotenv

With dotenv you have the ability to load .env-files of your project.

```xml
<placeholder>
    <dotenv>.env</dotenv>
</placeholder>
```

You can also configure multiple paths to .env files.

```xml
<placeholder>
    <dotenv>.env</dotenv>
    <dotenv>.env2</dotenv>
</placeholder>
```

`.env2` overrides `.env` in this example.

Example:

.psh.xml
```xml
<path>dev-ops/common/actions"</path>
<placeholder>
    <dotenv>.env</dotenv>
</placeholder>
```

.env
```dotenv
TEST=mytest
```

dev-ops/common/actions/test.sh
```bash
#!/usr/bin/env bash

echo __TEST__
```

#### Require

It may be necessary to require a placeholder to be set, but can't set right away. One such example might be a system dependent path. PSH allows you to communicate this to the user by adding this:

```xml
<placeholder>
    <require name="FOO" description="Foo needs to be a reference to bar"/>
</placeholder>
```

Now unless foo is set, it is not possible to execute any psh script. The description is optional and can be omitted.

#### Templates

If your application depends on files that are not part of your repository because they differ for different systems (Typically `*.dist` files), 
you can use templates to achieve automatic deployment of these files.

```xml
<template 
    source="templates/consts.tpl" 
    destination="app/consts.php"
/>
```

This reads the contents of `templates/consts.tpl`, replaces placeholders with constants or variables from your configuration and writes the result to `app/consts.php`.

It is even possible to use placeholders in template destinations:

```xml
<template
    source="templates/consts.tpl"
    destination="app/consts-__ENVIRONMENT__.php"
/>
```

#### Environments

Environments are used to extend or overwrite your base configuration. You can add more scripts, redefine or add constants or variables. 
A environment called `foo` may look like this:

```xml
<environment name="foo">

    <path>foo/sh/scripts</path>
    <path>bar/sh/scripts</path>
    
    <placeholder>
        <const name="TEST">1</const>
        <dynamic name="ID">id</dynamic>   
    </placeholder>

</environment>
```

This environment loads all scripts from `foo/sh/scripts` and `bar/sh/scripts`, adds a constant `TEST` and a variable `ID`. 
If you want to call a script in this environment you have to prefix your call with `foo:`.

In order to exclude a whole environment from the listing add the `hidden` attribute to the environment tag and set it to `true`, like this: 

```xml
<environment name="internal" hidden="true">
    <path>internal/only/scripts</path>
</environment>
```

These scripts can be executed like any regular script, they will just not be shown in the listing.

#### Headers

Optionally - and just for fun - you can output a ASCII header in front of every PSH execution.

```xml
    <header><![CDATA[
         _
     ___| |__   ___  _ ____      ____ _ _ __ ___
    / __| '_ \ / _ \| '_ \ \ /\ / / _` | '__/ _ \
    \__ \ | | | (_) | |_) \ V  V / (_| | | |  __/
    |___/_| |_|\___/| .__/ \_/\_/ \__,_|_|  \___|
                    |_|
    ]]></header>

```

#### Overriding configuration file

You can place a `.psh.xml.override` inside your directory where the `.psh.xml` is located to override the specific configurations.

> Notice: You can overwrite a XML config file with a YAML file to ease the migration from one format to the other.

#### Importing configuration files

You can import environments, actions and placeholders by using the import statement and telling psh to look in another place.

```xml
<import path="another/config/file/location" />
```

These directories should contain a `psh.xml` or `psh.xml.dist`. If no file is found a warning is issued but no braking error, since it may very well be that psh is currently installing or downloading the files. You can also use a glob pattern like "tools/**/config"

> Notice: This happens through merging the different configurations into one. Be aware that you might overwrite base configuration. 

## PSH-Scripts

Although most of your existing sh scripts should work just fine, you may find some of the following additions useful or necessary.

Keep in mind: **Commands will be validated for successful execution -> All failures will fail the script!**

#### Defining placeholders

In order to ensure that your scripts are reusable you can add placeholders that PSH will replace with configured values. All placeholders
start and end with `__`, and contain only upper case letters, numbers, and single `_` characters.

```sh
__TEST_IT__
```

#### Including other actions

It is possible to include other scripts by it's name.

```sh
ACTION: build # default environment or
ACTION: pipelines:build # if it's in an environment
```

The benefit of this instead of `Including other scripts` is that you don't have to deal with absolute or relative paths in general.

#### Including other scripts

Prefixing a line with `INCLUDE: ` will treat the remaining part of the line as the path to another script to be included and executed here.

```sh
INCLUDE: my/sub/script.sh
```

If the path is relative, PSH will attempt to load the script relative to the location of the current script or relative to the configuration file.

#### On demand templates

Prefixing a line with `TEMPLATE: ` will trigger an on demand template creation. The remaining part of the line then must look like this: `SOURCE_PATH:DESTINATION_PATH`

```sh
TEMPLATE: ../templates/template.ini.tpl:../destination/template.ini
```

Notice that all paths here must be **relative** to the script location.

#### Defer execution to the background

Execute the script in the background, so the following command gets executed right away

```sh
D: php generate_some_things.php
```

#### Wait for all deferred commands to execute

If you then want to wait for all results, just add a `WAIT` in there.

```sh
WAIT:
```


#### Open a ssh connection to another machine

Many dev-ops script open a SSH channel to a locally running virtual machine / container or a remote staging / test system. If you do this 
through PSH you have to prefix the line with `TTY:` 

```sh
TTY: vagrant ssh
```

#### Ignoring if a statement errored

Contrary to your usual shell scripts, to PSH it matters if a sh statement fails or not. If you need it to ignore errors, you have to prefix the line with `I:`

```sh
I: rm -R sometimes/there
```
#### Breaking statements into multiple lines

If a single shell statement is to long for a single line, you can break it in PSH and intend it with three spaces in the next line. 
PSH will then concatenate it prior to execution and execute it all in one.

```sh
bin/phpunit
    --debug
    --verbose
```

#### Description

You can add a description to a script which will be printed when the command list will be displayed.

```sh
#!/usr/bin/env bash
#DESCRIPTION: My useful comment.
```

#### Downsides

* `export` statements and internal variables do not work, since the statements do **no longer share a single environment**.
* Statements that change the flow of a script do not work out of the box.

## BASH-Scripts

PSH allows you to execute bash scripts directly. Most features from the above described PSH-Scripts do not work in this part of the runtime, but placeholder usage is still possible an encouraged.

So if you have Bash scripts that you want PSH to execute directly just add a second line after the shebang:

```bash
#!/usr/bin/env bash
# <PSH_EXECUTE_THROUGH_CMD>

FOO="BAR"

echo $PWD
echo $FOO
echo __PLACEHOLDER__

```

`# <PSH_EXECUTE_THROUGH_CMD>` will advice PSH to execute the script through your current OS.

> Notice: PSH is written for security and predictability first, so it will warn you if you forget to add `set -euo pipefail` to the beginning of your script.  

#### Internals

* If and only if a placeholder is present PSH will internally create a hidden file in the same directory and mark it executable, please make shure that your environment allows that.
* Future versions of PSH will change this to requiring a special shebang line for PSH-Scripts, please be aware of that (Something like `#!/usr/bin/env psh`).  

## Executing it

The general format is `./psh.phar <application-options> <script-names> <script-options>`. The only currently supported application option is `--no-header`, script names are a comma separated list of actions (or one) and script options are key value pairs to overwrite placeholders. Let's look at some examples:


Executing the phar will print a listing overview of all available commands

```sh
> ./psh.phar

###################
Available commands:

	- build
	- unit

2 script(s) available
```

The first argument is always the script name. This for example will execute the unit script:

```sh
> ./psh.phar unit

###################
Starting Execution of 'unit' ('actions/unit.sh')


(1/3) Starting
> bin/php-cs-fixer fix
	You are running php-cs-fixer with xdebug enabled. This has a major impact on runtime performance.
	
	Loaded config from "/var/www/swag/psh/.php_cs".
	
	[....]
```

You can add more commands to be executed in a chain, by comma separating the script names:

```sh
> ./psh.phar unit,build #executes both scripts in order
```

You can add parameter for replace placeholder in your .sh files like the following examples:

```sh 
    ./psh.phar unit --param someValue  #or
    ./psh.phar unit --param=someValue --otherParam value --onMoreParam=value ...
    ./psh.phar list --add -l
```

in your .sh files write.
```sh 
    ls __ADD__
```

executes:
```sh
    ls -l
```

### Bash Autocompletion

Bash autocompletion is only provided by [PSH-Global](https://github.com/shopwareLabs/psh-global). This will install a global script that fetches 
the psh.phar file in your project and that will install the autocompletion for you. 

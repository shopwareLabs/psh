PSH - PHP shell helper
====================

**Keep using your standard shell scripts**

PSH is intended to be a **simple** and **easy** alternative to other build script solutions.

Introduction
------------

You do not have to learn a new - and in most cases much more verbose - language, but can scale your existing skills 
on the command line.

Key benefits are:

* Share your existing shell scripts with your team members
* Add error handling if single statement in the *sh* scripts fails
* Replace placeholders in *sh* scripts with variables
* Overload variables and scripts in a environment configuration
 
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

### As a PHAR archive (preferred)

Download `psh.phar` to your local environment. 

```sh
wget https://shopwarelabs.github.io/psh/psh.phar # PHP7 Version
# OR wget https://shopwarelabs.github.io/psh/psh56.phar for the PHP5.6 Version
chmod +x psh.phar
```

### Build it yourself

PSH is used to build itself. You need to clone the repository and install the composer dependencies by yourself first.

```sh
git clone https://github.com/shopwareLabs/psh.git
cd psh
composer install # assuming you have composer installed globally

./psh unit # verify your installation by executing the test suite.
./psh build 
```

This will create a release phar in the `build/psh.phar` directory. Although the project itself requires PHP7 a PHP 5.6 
compatible version is currently created with it `build/psh56.phar`. 

Usage
------------

PSH is a CLI application. Before you can use it you need to create a configuration file in your project root named `.psh.yml`.

## Configuration

The minimum required file looks like this:

```yaml
paths:
  - my/sh/scripts

const: []

dynamic: []
```

* `paths` - The locations of your `*.sh` scripts
* `const` - The constant environment values you want PSH to replace in your scripts
* `dynamic` - The dynamic values you want PSH to replace in your scripts

This just lists all `*.sh` scripts in `my/sh/scripts` and allows you to call them by filename.

#### Placeholders

Placeholders in your scripts looks like this:

```sh
ln -s __PATH__
```

The placeholder `__PATH__` now needs to be part of your configuration file as either a constant or a variable.

> Notice: All placeholders must be written in uppercase in scripts. Even if defined otherwise in configuration, replacement only works uppercase. With (sic!) add the end of a placeholder it will be escaped. As an example `__DIR__(sic!)`.  

#### Constants

Constants are the basic solution to placeholder replacements. You define placeholders in your config like this:

```yaml
const:
  PATH: /var/www
```

This will then execute 

```sh
ln -s /var/www
```

#### Variables

With variables you can use the output of one line shell statements in your scripts.
  
```yaml
dynamic:
  PATH: echo $HOME
```

The Variables get executed before the actual statement is executed, but you can imagine the outcome to be equivalent to:

```yaml
ln -s `echo $HOME`
```

#### Templates

If your application depends on files that are not part of your repository because they differ for different systems (Typically `*.dist` files), 
you can use templates to achieve automatic deployment of these files.

```yaml
templates:
  - source: templates/consts.tpl
    destination: app/consts.php
```

This reads the contents of `templates/consts.tpl`, replaces placeholders with constants or variables from your configuration and writes the result to `app/consts.php`.

It is even possible to use placeholders in template destinations:

```yaml
templates:
  - source: templates/consts.tpl
    destination: app/consts-__ENVIRONMENT__.php
```

#### Environments

Environments are used to extend or overwrite your base configuration. You can add more scripts, redefine or add constants or variables. 
A environment called `foo` may look like this:

```yaml
environments:
    foo:
        paths:
            - foo/sh/scripts
        const: 
            TEST: 1
        dynamic: 
            ID: id
```

This environment loads all scripts from `foo/sh/scripts`, adds a constant `TEST` and a variable `ID`. 
If you want to call a script in this environment you have to prefix your call with `foo:`.


#### Headers

Optionally - and just for fun - you can output a ASCII header in front of every PSH execution.

```yaml
header: |
         _
     ___| |__   ___  _ ____      ____ _ _ __ ___
    / __| '_ \ / _ \| '_ \ \ /\ / / _` | '__/ _ \
    \__ \ | | | (_) | |_) \ V  V / (_| | | |  __/
    |___/_| |_|\___/| .__/ \_/\_/ \__,_|_|  \___|
                    |_|
```

## SH-Scripts

Although most of your existing sh scripts should work just fine, you may find some of the following additions useful or necessary.

Keep in mind: **Commands will be validated for successful execution -> All failures will fail the script!**

#### Defining placeholders

In order to ensure that your scripts are reusable you can add placeholders that PSH will replace with configured values. All placeholders
start and end with `__`, and contain only upper case letters, numbers, and single `_` characters.

```sh
__TEST_IT__
```

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

#### Downsides

* `export` statements and internal variables do not work, since the statements do **no longer share a single environment**.
* Statements that change the flow of a script do not work out of the box.

## Executing it

Executing the script will print a listing overview of all available commands

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


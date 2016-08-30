PSH - PHP shell helper
====================

Introduction
------------

A Tool to execute shell scripts through php, including a small templating engine and environment settings. It can be used
as an easy way to create adaptable dev-ops scripts from existing or newly written shell scripts. You do not have to learn
a new - and in most cases much more verbose - language, but can scale your existing skills.

Key Features are:

* Extend sh scripts with variables
* Add error handling if single lines in sh scripts fail
* Overload variables and scripts in a environmant configuratiuon
 
PSH is intended to be a **simple** and **easy** alternative other build script solutions. 

Installation
------------

Although you can use psh as a composer dependency, we recommend to use the *PHAR archive* instead. PSH only communicates through
the shell with your application and therefore does not need any influence on your other project dependencies.

### Through composer

```
composer require shopware/psh --dev
```

### As a PHAR archive

Download `psh.phar` to your local environment. 

```
wget shop.ware/psh.phar
chmod +x psh.phar
```

### PHAR creation

```
./psh build 
```

Psh is used to build itself. This will create a release phar in the `build/psh.phar` directory. Although the project itself requires PHP7 a
 PHP 5.6 compatible version is created with it `build/psh56.phar`. This can be useful if your CI environment is not as recent as your development machines. 

### Testing

```
./psh.phar unit
```

Execute the Unit-Test suite and mutation testing through `humbug`.

Usage
------------

## Writing SH-Scripts

Keep in mind: **Commands will be validated for successful execution -> All failures will fail the script!**

### Including other scripts

Prefixing a line with `INCLUDE: ` will treat the rest of the line as a path to another script to be included and executed here.

```
INCLUDE: my/sub/script.sh
```

If the path is relative. PSH will atempt to load the script relative to the location of the current script or relative to the configuration file.
 
### Open a ssh connection to another machine

Many dev-ops script open a SSH channel to a locally running virtual machine / container or a remote staging / test system. If you do this 
through psh you have to prefix the line with `TTY:` 

```
TTY: vagrant ssh
```

### Ignoring if a statement errored

Contrary to your usual shell scripts, to psh it matters if a sh statement fails or not. If you need it to ignore errors, you have to prefix the line with `I:`

```
I: rm -R sometimes/there
```
## Breaking statements into multiple lines

If a single shell statement is to long for a single line, you can break it in psh and intend it with three spaces in the next line. 
PSH will then concatenate it prior to execution and execute it all in one.

```
bin/phpunit
    --debug
    --verbose
```

#### Downsides

* `export` statements and internal variables do not work, since the statements do no longer share a single environment.
* Statements that change the flow of a script do not work out of the box.

## Configuration

Create a config file named `.psh.yaml` in your base directory. The minimum required file looks like this:

```
paths:
  - my/sh/scripts

const: []

dynamic: []
```

* `paths` - The locations of your `*.sh` scripts
* `const` - The constant environment values you want psh to replace in your scripts
* `dynamic` - The dynamic values you want psh to replace in your scripts

This just lists all `*.sh` scripts in `my/sh/scripts` and allows you to call them by filename.

#### Placeholders

Placeholders in your scripts looks like this:

```
ln -s __PATH__
```

The placeholder `__PATH__` now needs to be part of your configuration file as either a constant or a variable.

> Notice: All placeholders must be written in uppercase. Even if defined otherwise in configuration, replacement only works uppercase.

#### Constants

Constants are the basic solution to placeholder replacement. You define placeholders in your config like this:

```
const:
  PATH: /var/www
```

This will then execute 

```
ln -s /var/www
```

#### Variables

With variables you can use the output of one line shell statements in your scripts.
  
```
dynamic:
  PATH: echo $HOME
```

This is equivalent to:

```
ln -s `echo $HOME`
```

### Templates

If your application depends on files that are not part of your repository because they differ for different systems (Typically `*.dist` files), 
you can use templates to achieve automatic deployment of these files.

```
templates:
  - source: templates/consts.tpl
    destination: app/consts.php
```

This reads the contents of `templates/consts.tpl`, replaces placeholders with constants or variables from your configuration and writes the result to `app/consts.php`.

#### Environments

Environments are used to extend or overwrite your base configuration. You can add more scripts, redefine or add constants or variables. 
A environemnt called `foo` may look like this:

```
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
If you want to call a script in this environment you have to prefix the call with `foo:`.


### Headers

Optionally - and just for fun - you can output a ASCII header in front of every PSH execution.

```
header: |
         _
     ___| |__   ___  _ ____      ____ _ _ __ ___
    / __| '_ \ / _ \| '_ \ \ /\ / / _` | '__/ _ \
    \__ \ | | | (_) | |_) \ V  V / (_| | | |  __/
    |___/_| |_|\___/| .__/ \_/\_/ \__,_|_|  \___|
                    |_|
```

 
 




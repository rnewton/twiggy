[![Build Status](https://travis-ci.org/rnewton/twiggy.svg)](https://travis-ci.org/rnewton/twiggy)

# Twiggy

Twiggy is a simple Database migration and versioning tool. It's currently in early alpha. Don't use this in production, but do feel free to contribute!

## Installation

TODO - not on packagist yet.
Using composer: 

```
composer require ...
```

## Usage

```
Usage:
 help [--xml] [--format="..."] [--raw] [command_name]

Arguments:
 command               The command to execute
 command_name          The command name (default: "help")

Options:
 --xml                 To output help as XML
 --format              To output help in other formats (default: "txt")
 --raw                 To output raw command help
 --help (-h)           Display this help message
 --quiet (-q)          Do not output any message
 --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 --version (-V)        Display this application version
 --ansi                Force ANSI output
 --no-ansi             Disable ANSI output
 --config (-c)         Path to the configuration file
 --db_dsn (-H)         Set the Database DSN
 --db_user (-U)        Set the Database user (Default: None, can be in DSN)
 --db_pass (-P)        Set the Database password (Default: None, can be in DSN)
 --db_options (-O)     Set the Database options separated by spaces (Default: None) (multiple values allowed)
 --directory (-D)      Set the path to the directory where migrations are stored (Default: ./migrations)
 --table (-T)          Set the name of the database table where migrations are stored (Default: migrations)
 --id_format (-I)      Set the regex used for migration ids (Default: /\d{8}_\d{6}/)

 Available commands:
 apply      Apply migration(s)
 create     Create a new migration
 help       Displays help for a command
 list       Lists commands
 ls         List migrations by the given criteria
 mark       Mark a migration as run
 remove     Remove a migration entirely
 rollback   Rollback migration changes
 test       Test a migration
```

## Contributing

1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D
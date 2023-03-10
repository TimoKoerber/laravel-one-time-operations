# Laravel One-Time Operations

Run an operation once after each deployment - just like you do it with migrations!

This package is for you if...

- you often create jobs to use just one single time **after a deployment**
- you sometimes forgot to execute that one specific job and stuff got crazy
- your code gets cluttered with Jobs, that are no longer in use anymore
- you seed or process data in a migration file (which is a big no-no!)

And the best thing: They work as easy as **Laravel migrations**!

## Installation

Require this package with composer:

```shell
composer require timokoerber/laravel-one-time-operations
```

Create the required table in your database:

```shell
php artisan migrate
```

Now you're all set!

## Commands

### Create operation files
```shell
php artisan operations:make <operation_name> // create operation file
```

### Process operations
```shell
php artisan operations:process                   // process operation files
php artisan operations:process --sync            // force syncronously execution
php artisan operations:process --async           // force asyncronously execution
php artisan operations:process --test            // don't flag operations as "processed"
php artisan operations:process <operation_name>  // re-run one specific operation
```

### Show operations
```shell
php artisan operations:show            // show all operations 
php artisan operations:show pending    // show pending operations 
php artisan operations:show processed  // show processed operations 
php artisan operations:show disposed   // show disposed operations 

php artisan operations:show pending processed disposed  // use multiple filters 
```
 
### Delete operation files
```shell
php artisan operations:dispose // delete all operation files (only on local environment)
```

## Tutorials

### Edit config (optional)

By default, the next steps will create the following:
- the table `operations` in your database
- the directory `operations` in your project directory

If you want to use a different settings for _table_ or _directory_ publish the config file:

```shell
php artisan vendor:publish --provider="TimoKoerber\LaravelOneTimeOperations\OneTimeOperationsServiceProvider"
```

This will create the file `config/one-time-operations.php` with the following content.

```php
// config/one-time-operation.php

return [
    'directory' => 'operations',
    'table' => 'operations',
];
```

Make changes as you like.

### Create One-Time Operation files

To create a new operations file execute the following command:

```shell
php artisan operations:make SeedAclData
```

This will create a file like `operations/XXXX_XX_XX_XXXXXX_seed_acl_data.php` with the following content.
(If you ever used Laravel migrations you should be familiar with the convention) 

```php
<?php
// operations/XXXX_XX_XX_XXXXXX_seed_acl_data.php

use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    /**
     * Determine if the operation is being processed asyncronously.
     */
    protected bool $async = true;

    /**
     * Process the operation.
     */
    public function process(): void
    {
        //
    }
};

```

Provide your code in the `process()` method, for example: 


```php
// operations/XXXX_XX_XX_XXXXXX_seed_acl_data.php

public function process(): void
{
    (new AclDataSeeder())->run(); // fill acl tables with required data
}
```

By default, the operation is being processed ***asyncronously*** by dispatching the job `OneTimeOperationProcessJob`. 

You can also execute the code syncronously by setting the `$async` flag to `false`. 
_(this is only recommended for small operations, since the processing of these operations will be part of the deployment process)_

### Processing the operations

Use the following call to process all new operation files.

```shell
php artisan operations:process
```

Your code will be executed and you will find all the processed operations in the `operations` table: 

| id  | name                           | dispatched | processed_at        | 
|-----|--------------------------------|------------|---------------------|
| 1   | XXXX_XX_XX_XXXXXX_seed_acl_data| async      | 2015-10-21 07:28:00 |

After that this specific operation will not be processed anymore.

#### Force syncronously/asyncronously execution

By providing the `--sync` or `--async` option, the `$async` flag in all the files will be ignored and the operation will be executed based on the given flag. 

```shell
php artisan operations:process --sync 
php artisan operations:process --async 
```

#### Re-run an operation manually

If something went wrong, you can process an operation manually by providing the **name** of the operation. 
This will process the operation again, even if it was processed before. 

```shell
php artisan operations:process XXXX_XX_XX_XXXXXX_seed_acl_data
```

#### Testing the operation

You might want to test your code a couple of times before flagging the operation as "processed". Provide the `--test` flag to run the command again and again.

```shell
php artisan operations:process --test
```

### Showing all operations

So you don't have to check the database or the directory for the existing operations, 
you can show a list with `operations:show`. 
Filter the list with the available filters `pending`, `processed` and `disposed`. 

- `pending` - Operations, that have not been processed yet
- `processed` - Operations, that have been processed
- `disposed` - Operations, that have been processed and the files were already deleted (which is okay)

```shell
php artisan operations:show pending           // show only pending operations
php artisan operations:show pending disposed  // show only pending and disposed operations
```

## Testing

```
composer test
```

## License

Copyright © Timo Körber

Laravel JSON Seeder is open-sourced software licensed under the [MIT license](LICENSE).


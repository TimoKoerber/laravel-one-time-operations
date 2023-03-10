![Laravel One-Time Operations](https://user-images.githubusercontent.com/65356688/224431782-550a6147-144d-408e-a412-4bd2b425dc15.jpg)
# Laravel One-Time Operations

Run an operation once after each deployment - just like you do it with migrations!

This package is for you if...

- you often create jobs to use just one single time **after a deployment**
- you sometimes **forgot to execute** that one specific job and stuff got crazy
- your code gets **cluttered with jobs**, that are not being used anymore
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
php artisan operations:process --test            // dont flag operations as processed
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
 
## Tutorials

### Deployment-Process

The *One-Time Operations* work exactly like [Laravel Migrations](https://laravel.com/docs/9.x/migrations). 
Just process the operations *after your code was deployed and the migrations were migrated*. 
You can make it part of your deployment script like this: 

```shell
...
 - php artisan migrate
 - php artisan operations:process
...
```

### Edit config

By default, the following elements will be created in your project:

- the table `operations` in your database
- the directory `operations` in your project root directory

If you want to use a different settings just publish and edit the config file:

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

![Laravel One-Time Operations - Create One-Time Operation files](https://user-images.githubusercontent.com/65356688/224433928-721b1261-b7ad-40c6-a512-d0f5b5fa0cbf.png)

![Laravel One-Time Operations - Create One-Time Operation files](https://user-images.githubusercontent.com/65356688/224433323-96b23e84-e22e-4333-8749-ae61cc866cd1.png)

To create a new operation file execute the following command:

```shell
php artisan operations:make AwesomeOperation
```

This will create a file like `operations/XXXX_XX_XX_XXXXXX_awesome_operation.php` with the following content.

```php
<?php
// operations/XXXX_XX_XX_XXXXXX_awesome_operation.php

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
// operations/XXXX_XX_XX_XXXXXX_awesome_operation.php

public function process(): void
{
    User::where('active', 1)->update(['status' => 'awesome']) // make active users awesome
}
```

By default, the operation is being processed ***asyncronously*** (based on your configuration) by dispatching the job `OneTimeOperationProcessJob`. 

You can also execute the code syncronously by setting the `$async` flag to `false`. 
_(this is only recommended for small operations, since the processing of these operations will be part of the deployment process)_

### Processing the operations

![Laravel One-Time Operations - Processing the operations](https://user-images.githubusercontent.com/65356688/224434129-43082402-6077-4043-8e97-c44786e60a59.png)

Use the following call to process all new operation files.

```shell
php artisan operations:process
```

Your code will be executed, and you will find all the processed operations in the `operations` table: 

| id  | name                                | dispatched | processed_at        | 
|-----|-------------------------------------|------------|---------------------|
| 1   | XXXX_XX_XX_XXXXXX_awesome_operation | async      | 2015-10-21 07:28:00 |

After that, this operation will not be processed anymore.

### Dispatching Jobs syncronously or asyncronously 

By default, all operations are being exectued with the `OneTimeOperationProcessJob` based on your `queue.default` configuration. 
By providing the `--sync` or `--async` option, the `$async` attribute in all the files will be ignored and the operation will be executed based on the given flag. 

```shell
php artisan operations:process --async  // force OneTimeOperationProcessJob::dispatch()
php artisan operations:process --sync   // force OneTimeOperationProcessJob::dispatchSync()  
```

**Hint!** If `operation:process` is part of your deployment process, it is **not recommended** to process the operations syncronously, 
since an error in your operation could make your whole deployment fail. 

### Re-run an operation manually

![Laravel One-Time Operations - Re-run an operation manually](https://user-images.githubusercontent.com/65356688/224440344-3d095730-12c3-4a2c-b4c3-42a8b6d60767.png)

If something went wrong, you can process an operation manually by providing the **name of the operation** as parameter in `operations:process`. 
This will process the operation again, even if it was processed before (confirmation is required). 

```shell
php artisan operations:process XXXX_XX_XX_XXXXXX_awesome_operation
```

### Testing the operation

You might want to test your code a couple of times before flagging the operation as "processed". Provide the `--test` flag to run the command again and again.

```shell
php artisan operations:process --test
```

### Showing all operations

![Laravel One-Time Operations - Showing all operations](https://user-images.githubusercontent.com/65356688/224432952-49009531-8946-4d19-8cee-70ca12605038.png)

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

### Deleting operations

The whole idea of this package is, that you can dispose the operations once they were executed, so your project won't be cluttered with files and code, you won't be using anymore. 

So you just need to **delete the files from your repository**

The deleted operations will be shown as ``DISPOSED`` when you call `operations:show`, so you still have a history on all the processed operations.

## Testing

```
composer test
```

## License

Copyright © Timo Körber

Laravel JSON Seeder is open-sourced software licensed under the [MIT license](LICENSE).


![One-Time Operations for Laravel](https://user-images.githubusercontent.com/65356688/225704995-ec7f54fb-a5b8-4d73-898f-2ebeed9ee733.jpg)
# One-Time Operations for Laravel

Run operations once after deployment - just like you do it with migrations!

-----

**Take your CI/CD to the next Level with One-Time Operations for Laravel**! 🚀

Create specific classes for a one-time usage, that can be executed automatically after each deployment. 
Same as migrations they get processed once and then never again. Perfect for seeding or updating some data instantly after 
some database changes or feature updates.

This package is for you if...

- you regularly need to **update specific data** after you deployed new code
- you often execute jobs just **only one single time** after a deployment
- you sometimes **forget to execute** that one specific job and stuff gets crazy
- your code gets **cluttered with jobs**, that are not being used anymore
- your co-workers always need to be reminded to **execute that one job** after some database changes
- you often seed or process data **in a migration file** (which is a big no-no!)


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
php artisan operations:make <operation_name>                // create new operation file
php artisan operations:make <operation_name> -e|--essential // create cleaner file without any attributes
```

### Process operations
```shell
php artisan operations:process                   // process all new operation files

php artisan operations:process --sync            // force syncronously execution
php artisan operations:process --async           // force asyncronously execution
php artisan operations:process --test            // dont flag operations as processed

php artisan operations:process --queue=<name>    // force queue, that the job will be dispatched to
php artisan operations:process --tag=<tagname>   // only process operations, that have the given tag

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

### CI/CD & Deployment-Process

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
php artisan vendor:publish --provider="TimoKoerber\LaravelOneTimeOperations\Providers\OneTimeOperationsServiceProvider"
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

![One-Time Operations for Laravel - Create One-Time Operation files](https://user-images.githubusercontent.com/65356688/224433928-721b1261-b7ad-40c6-a512-d0f5b5fa0cbf.png)

![One-Time Operations for Laravel - Create One-Time Operation files](https://user-images.githubusercontent.com/65356688/224433323-96b23e84-e22e-4333-8749-ae61cc866cd1.png)

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
     * The queue that the job will be dispatched to.
     */
    protected string $queue = 'default';
    
    /**
     * A tag name, that this operation can be filtered by.
     */
    protected ?string $tag = null;

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
By default, the operation is being dispatched to the `default` queue of your project. Change the `$queue` as you wish.  

You can also execute the code syncronously by setting the `$async` flag to `false`. 
_(this is only recommended for small operations, since the processing of these operations should be part of the deployment process)_

**Hint:** If you use syncronous processing, the `$queue` attribute will be ignored (duh!). 

### Create a cleaner operation file

If you don't need all the available attributes for your operation, you can create a *cleaner* operation file with the `--essential` or `-e` option: 

```shell
php artisan operations:make AwesomeOperation --essential
php artisan operations:make AwesomeOperation -e
```

### Processing the operations

![One-Time Operations for Laravel - Processing the operations](https://user-images.githubusercontent.com/65356688/224434129-43082402-6077-4043-8e97-c44786e60a59.png)

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

For each operation a `OneTimeOperationProcessJob` is being dispatched, 
either with `dispatch()` oder `dispatchSync()` based on the `$async` attribute in the operation file.

By providing the `--sync` or `--async` option with the `operations:process` command, you can force a syncronously/asyncronously execution and ignore the attribute:

```shell
php artisan operations:process --async  // force dispatch()
php artisan operations:process --sync   // force dispatchSync()  
```

**Hint!** If `operation:process` is part of your deployment process, it is **not recommended** to process the operations syncronously, 
since an error in your operation could make your whole deployment fail. 

### Force different queue for all operations 

You can provide the `--queue` option in the artisan call. The given queue will be used for all operations, ignoring the `$queue` attribute in the class.  

```shell
php artisan operations:process --queue=redis  // force redis queue 
```

### Run only operations with a given tag

You can provide the `$tag` attribute in your operation file: 

```php
<?php
// operations/XXXX_XX_XX_XXXXXX_awesome_operation.php

    protected ?string $tag = "awesome";
};
```

That way you can filter operations with this specific tag when processing the operations:

```shell
php artisan operations:process --tag=awesome  // run only operations with "awesome" tag
```

This is quite usefull if, for example, you want to process some of your operations before and some after the migrations: 

```text
 - php artisan operations:process --tag=before-migrations 
 - php artisan migrate
 - php artisan operations:process
```

You can also provide multiple tags:

```shell
php artisan operations:process --tag=awesome --tag=foobar // run only operations with "awesome" or "foobar" tag 
```

*Hint!* `operations:process` (without tags) still processes *all* operations, even if they have a tag.

### Re-run an operation

![One-Time Operations for Laravel - Re-run an operation manually](https://user-images.githubusercontent.com/65356688/224440344-3d095730-12c3-4a2c-b4c3-42a8b6d60767.png)

If something went wrong (or if you just feel like it), you can process an operation again by providing the **name of the operation** as parameter in `operations:process`.

```shell
php artisan operations:process XXXX_XX_XX_XXXXXX_awesome_operation
```

### Testing the operation

You might want to test your code a couple of times before flagging the operation as "processed". Provide the `--test` flag to run the command again and again.

```shell
php artisan operations:process --test
```

### Showing all operations

![One-Time Operations for Laravel - Showing all operations](https://user-images.githubusercontent.com/65356688/224432952-49009531-8946-4d19-8cee-70ca12605038.png)

So you don't have to check the database or the directory for the existing operations, 
you can show a list with `operations:show`. 
Filter the list with the available filters `pending`, `processed` and `disposed`. 

- `pending` - Operations, that have not been processed yet
- `processed` - Operations, that have been processed
- `disposed` - Operations, that have been processed and the files were already deleted

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

Copyright © Timo Körber | [www.timokoerber.com](https://www.timokoerber.com)

"One-Time Operations for Laravel" is open-sourced software licensed under the [MIT license](LICENSE).


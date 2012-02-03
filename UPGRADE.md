Changes in version 2.1
----------------------

 * Assets are no longer dumped to the filesystem when `cache:warmup` is run. 
   This can only be done using the `assetic:dump` command
 * Added support for GSS filter
 * Assetic's routes are now automatically added when `use_controller` is 
   `true`

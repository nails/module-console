# Bootstrapping
> Documentation is a WIP.


The Console tool provides an opportunity for the application to execute code at three points in the console commands lifecycle:

* **preSystem** – executed after settings have loaded, but before Nails instantiates
* **preCommand** – Immediately before the selected command is executed
* **postCommand** – Immediately after the command exists, regardless of exist state


## Class structure

The bootstrapper is nothing more than a class defined as `App\Console\Bootstrap`; this class can contain methods which match the above events. These should be public static, and will not be passed any parameters.

```php
namespace App\Console;

class Bootstrap
{
    public static function preSystem()
    {
    }
    
    public static function preCommand()
    {
    }
    
    public static function postCommand()
    {

    }
}
```


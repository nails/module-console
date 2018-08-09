# Commands
> Documentation is a WIP.

Commands will be detected automatically and should exist within the `App\Console\Command` namespace and extend the `Nails\Console\Command\Base` class.

Each command is simply a Symfony Command, and [should adopt the same structure](https://symfony.com/doc/current/console.html).


```php
namespace App\Console\Command;

use Nails\Console\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
    
class Bark extends Base
{
    protected function configure()
    {
        $this->setName('app:bark');
        $this->setDescription('Barks like a dog');
    }

    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $oOutput->writeln('woof!');
    }
}
```


## Providing comands from module's

The above documentation is specific to the application providing the command; modules should update the namespace accordingly.

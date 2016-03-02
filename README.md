# Console Module for Nails

This is the "Console" module for nails, it provides a centralsied touchpoint for command line based actions.

http://nailsapp.co.uk/modules/console


This tool is simply a wrapper for the excellent [Console Component provided by Symfony](http://symfony.com/doc/current/components/console/introduction.html). It looks for valid Console compatible classes in each installed module's `src/Console/Command` directory and loads them into the main application.

## Defining Console Applications

Your module must be PSR-4 ready in order for the class to be loaded correctly. You should do this in the module's `composer.json` file. In addition you must also specify the module's root namespace in `composer.json`. Example:

    {
        "name": "my-vendor/my-module",
        "homepage": "http://example.com",
        "description": "This is my excellent module for Nails",
        "autoload":
        {
            "psr-4": {"MyVendor\\MyModule\\": "src/"}
        },
        "extra":
        {
            "nails" :
            {
                "moduleName": "mymodule",
                "type": "module",
                "namespace": "MyVendor\\MyModule\\"
            }
        }
    }

Example directory structure:


    myvendor
    |__ mymodule
    |____ src
    |______ Console
    |________ Command
    |__________ MyCommand.php
    |__________ MyOtherCommand.php
    

Example Class (MyCommand.php)


    <?php
    
    namespace MyVendor\MyModule\Console\Command;
    
    class MyCommand
    {
        protected function configure()
        {
            $this->setName('mymodule-mycommand');
            $this->setDescription('Does something exciting');
        }
    
        // --------------------------------------------------------------------------

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $output->writeln('Did something!');
        }
    }


Note the definition of the command, try and make it something which won't likely clash with another installed module. Also note the namespace - the console wrapper will automatically append `\Console\Command` when loading the class.


## Roadmap

- [ ] Prevent or detect command collisions

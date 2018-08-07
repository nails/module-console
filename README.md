# Console Module for Nails

This is the "Console" module for nails, it provides a centralsied touchpoint for command line based actions.

http://nailsapp.co.uk/modules/console

This tool is simply a wrapper for the excellent [Console Component provided by Symfony](http://symfony.com/doc/current/components/console/introduction.html). It looks for valid Console compatible classes in each installed module's `src/Console/Command` directory and loads them into the main application.

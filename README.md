# Console Module for Nails

![license](https://img.shields.io/badge/license-MIT-green.svg)
[![CircleCI branch](https://img.shields.io/circleci/project/github/nails/module-console.svg)](https://circleci.com/gh/nails/module-console)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nails/module-console/badges/quality-score.png)](https://scrutinizer-ci.com/g/nails/module-console)
[![Join the chat on Slack!](https://now-examples-slackin-rayibnpwqe.now.sh/badge.svg)](https://nails-app.slack.com/shared_invite/MTg1NDcyNjI0ODcxLTE0OTUwMzA1NTYtYTZhZjc5YjExMQ)

This is the "Console" module for nails, it provides a centralsied touchpoint for command line based actions.

http://nailsapp.co.uk/modules/console

This tool is simply a wrapper for the excellent [Console Component provided by Symfony](http://symfony.com/doc/current/components/console/introduction.html). It looks for valid Console compatible classes in each installed module's `src/Console/Command` directory and loads them into the main application.

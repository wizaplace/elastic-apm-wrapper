# Elastic APM Wrapper

PHP Wrapper to send Logs to Elastic APM

## Installation

The recommended way to install the wrapper is through Composer.

First, in the require section of your composer.json file, you need to authorize the unstable version of the Agent:
`"philkra/elastic-apm-php-agent": "dev-master"`.

Then, run the following composer command:

```composer require wizaplace/elastic-apm-wrapper```

## Configuration

Communication with Elastic APM is managed by elastic-apm-php-agent (\PhilKra\Agent).

In order to instantiate the service using this agent, you have to provide several parameters :
- `appName` (e.g. 'Wizaplace')
- `appVersion` (e.g. '1.0.0')
- `environment` (e.g. 'Development', 'Production')
- `serverUrl` APM Server Endpoint
- `secretToken` Secret token for your Elastic APM Server

## Usage

### Transaction
Transactions are the highest level of work you’re measuring within a service, like a request to your server.
The main idea is that you monitor one transaction at a time: your PHP script being executed.


To start a transaction, you need to use the ```startTransaction()``` method within AgentService.php.
It should be stopped (using ```stopTransaction()```) at the very end of the application you're monitoring.
Ideally, you wil start the transaction at the beginning of each entry point of your application
and stop it at the end of each.

### Spans
Spans can be seen as 'sub-transactions', meaning they are used to help to watch parts of your application.

A new span can be started as one or several spans are already started. It is important to stop manually all the spans you started otherwise they will be closed automatically meaning you won't really get accurate data.

Within one transaction there can be 0-* spans captured.

### Apm Handler
A custom Monolog handler (`ApmHandler.php`) has been included to this package, enabling you to send data to the APM automatically when Monolog is called.
This configuration is optional.



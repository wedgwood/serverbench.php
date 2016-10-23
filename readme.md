# serverbench.php
---

### Requirements

- posix
- php-pcntl (or php-cli)
- [proctitle](http://pecl.php.net/package/proctitle) (optional)
- [libzmq](https://github.com/zeromq/libzmq)
- [php-zmq](https://github.com/mkoppanen/php-zmq)

### example

- [use as lib](../tree/develop/example/use-as-lib/)

```php
<?php
require 'libserverbench.phar';

$server = new \ServerBench\App\Server\Server('tcp://127.0.0.1:12345', function ($msg) {
	return $msg;
});

$daemon = false;
$server->run($daemon);
```

- [use as server](../tree/develop/example/use-as-server/)

```bash
#cli utils

#start
php serverbench.phar --pidfile=./pid --dir=./ --app=app.php -c app.ini --daemon

#stop
php serverbench.phar --stop --pidfile=./pid

#reload
php serverbench.phar --reload --pidfile=./pid

#status
php serverbench.phar --status --pidfile=./pid
```

```php
<?php
// app entrance
class App
{
	public function init()
	{
		// connect database or anything else to ready
	}

	public function fini()
	{
		// do something to clean up
	}

	public function process($msg)
	{
		// process msg from client
		// here is an echo server
		return $msg;
	}
}

return new App();
```

- [benchmark](../tree/develop/example/benchmark/)

```bash
# php benchmark.php -C {address to connect} -c {clients/concurrency}  -T {time to testing} -L {msg's length}
php benchmark.php -C tcp://127.0.0.1:12345 -c 300 -T 15 -L 100
```




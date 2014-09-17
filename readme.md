Requirements
===

- posix
- pcntl
- [proctitle](http://pecl.php.net/package/proctitle)
- [libzmq](https://github.com/zeromq/libzmq)
- [php-zmq](https://github.com/mkoppanen/php-zmq)


Tip
===

```
// 1.create app
{serverbench.php}/src/ServerBench/App/Bin/createapp {YourAppName} [restly]

// 2.modify lib path in bootstrap.php
- require __DIR__ . '/../../vendor/autoload.php';
+ require '{ServerbenchDir}/vendor/autoload.php';

// 3.modify lib path in client/client.php
- require __DIR__ . '/../../../vendor/autoload.php';
+ require '{ServerBenchDir}/vendor/autoload.php';

// 4.have your fun
```

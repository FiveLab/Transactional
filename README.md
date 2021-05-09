Transactional
=============

[![Build Status](https://github.com/FiveLab/Transactional/workflows/Testing/badge.svg?branch=master)](https://github.com/FiveLab/Transactional/actions)

With use this package, you can run you code in transactional layer.

Installation
------------

Add **FiveLab/Transactional** in your `composer.json`:

```json
{
    "require": {
        "fivelab/transactional": "~2.0"
    }
}
```

Now tell composer to download the library by running the command:

```shell script
php composer.phar update fivelab/transactional
```

Development
-----------

For easy develop, you can use our `Dockerfile`.

```shell script
docker build -t transactional .
docker run -it -v $(pwd):/code transactional bash
```

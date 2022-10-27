# extas-repo-mongo

![PHP Composer](https://github.com/jeyroik/extas-repositories-mongo/workflows/PHP%20Composer/badge.svg?branch=master)
![codecov.io](https://codecov.io/gh/jeyroik/extas-repositories-mongo/coverage.svg?branch=master)
<a href="https://codeclimate.com/github/jeyroik/extas-repositories-mongo/maintainability"><img src="https://api.codeclimate.com/v1/badges/3c44f0be053be4e5d39b/maintainability" /></a>
[![Latest Stable Version](https://poser.pugx.org/jeyroik/extas-repositories-mongo/v)](//packagist.org/packages/jeyroik/extas-jsonrpc)
[![Total Downloads](https://poser.pugx.org/jeyroik/extas-repositories-mongo/downloads)](//packagist.org/packages/jeyroik/extas-jsonrpc)
[![Dependents](https://poser.pugx.org/jeyroik/extas-repositories-mongo/dependents)](//packagist.org/packages/jeyroik/extas-jsonrpc)


Extas compatable Mongo repository package.

# usage

// extas.app.storage.json
```json
{
    "drivers": [
        {
            "class": "\\extas\\components\\repositories\\drivers\\DriverMongo",
            "options": {
                "dsn": "mongodb://127.0.0.1:27017",
                "db": "tests"
            },
            "tables": [...]
        }
    ]
}
```
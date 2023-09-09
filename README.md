<!-- # Eloquentity -->
<p align="center" style="margin-bottom: -48px;">
    <img width="360px" src="images/logo.png">
</p>

Eloquentity allows you to map eloquent models to entities, any changes made to those entities can be persisted (including relationships), and you can even persist new entities.

> This is still under development

## Install

```sh
composer require dam-bal/eloquentity
```

## Release History

* 0.1.0
    * Initial release
* 0.2.0
    * Id attribute removed, property of id (primary key) needs to match primary key on Model but in camel case format.
    * id (primary key) is not required on entity class
* 0.3.0
    * collection improvements, any iterable can be used
* 0.4.0
    * fix around id property, id property is not requrired on entity class
* 0.5.0
    * general improvements / fixes

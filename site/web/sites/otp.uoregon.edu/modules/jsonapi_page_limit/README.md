# JSON:API Page Limit

## Table of contents

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers

## Introduction
Customize the page limit for JSON API collection responses for specific paths.

Drupal has a maximum limit of 50 items returned per JSON:API collection page.

I.e. if you were to use the following url: \
`http://www.example.com/jsonapi/node/article?page[limit]=51` \
The amount of content you would get back would still be 50.

This module allows you to increase that limit.

Note that by doing so, you should be aware of the potential performance implications (as this is the reason behind the
enforced limit of 50 in Drupal core).

### Alternative module: jsonapi_defaults
Similar functionality is provided by the jsonapi_defaults module (part of [jsonapi_extras](https://www.drupal.org/project/jsonapi_extras)).
Users should consider using jsonapi_defaults first, as it is much more actively maintained.

The key differences between the two modules, is that with jsonapi_defaults, the increased limit becomes the default.
This means the new amount will be returned unless something else is specified as part of the jsonapi url. 

Whereas with jsonapi_page_limit, after increasing the limit for a resource, the default will still be 50 unless you 
specifically request more in the url.

## Requirements
Only the jsonapi module (part of Drupal core) is required.

## Installation

- Install as you would normally install a contributed Drupal module. For further
  information, see _[Installing Drupal Modules] []_.

[Installing Drupal Modules]: https://www.drupal.org/docs/extending-drupal/installing-drupal-modules

Note that this module will not do anything by itself until the configuration has been added.

## Configuration
Define a set of paths and values as a service parameter in a custom services.yml file.
Note that wildcard parameters are allowed.
E.g.
```
parameters:
  jsonapi_page_limit.size_max:
    /jsonapi/node/page: 100
    /jsonapi/taxonomy/*: 75
```

## Maintainers

### Current maintainers

- [Leon Kessler](https://www.drupal.org/u/leon-kessler)
- [Moshe Weitzman](https://www.drupal.org/u/moshe-weitzman)
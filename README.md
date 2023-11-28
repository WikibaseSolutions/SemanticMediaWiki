> Installing this branch is currently quite complicated, because of some upstream dependency issues. Below is a step-by-step guide to install it.

1. Clone this repository into `extensions/SemanticMediaWiki`;
2. Check out `elasticsearch-810` using `git checkout elasticsearch-810`;
3. In your `composer.local.json`, add the following snippet under `repositories`:
  ```
  "semantic-media-wiki": {
     "type": "path",
     "url": "./extensions/SemanticMediaWiki"
  },
  ```
4. Change or add `"mediawiki/semantic-media-wiki": "@dev"` in your dependencies in your `composer.local.json`;
5. Change or add `"elasticsearch/elasticsearch": "~8.10.0"` in your dependencies in your `composer.local.json`;
6. Change the version of `psr/http-message` in the root `composer.json` (NOT `composer.local.json`) from `1.0.1` to `1.1.0`;
7. Run `composer update`;
8. Run `php maintenance/update.php` (if you get an exception like "No PSR-17 URL factory found", try running `composer require php-http/curl-client guzzlehttp/psr7 php-http/message`);
9. Run `php extensions/SemanticMediaWiki/maintenance/rebuildElasticIndex.php` (if you use WikiSearch, you can also skip this step and follow the steps below FIRST, otherwise you run `rebuildElasticIndex.php` twice);

Your wiki should now be working again. Try to change some properties, and see if they are updated. If you are using `WikiSearch`, you need to perform the following additional steps:

1. Change your version of WikiSearch to `dev-es-810-mw-139`, which works with MediaWiki 1.39 and ElasticSearch 8.10;
2. Update the data standard of WikiSearch if you have a custom one to the new version (a template is provided in `WikiSearch/data_templates/smw-wikisearch-data-standard-template.json`);
3. Run `rebuildElasticIndex.php` again.

# Semantic MediaWiki

[![CI](https://github.com/SemanticMediaWiki/SemanticMediaWiki/actions/workflows/main.yml/badge.svg)](https://github.com/SemanticMediaWiki/SemanticMediaWiki/actions/workflows/main.yml)
[![Latest Stable Version](https://poser.pugx.org/mediawiki/semantic-media-wiki/version.png)](https://packagist.org/packages/mediawiki/semantic-media-wiki)
[![Packagist download count](https://poser.pugx.org/mediawiki/semantic-media-wiki/d/total.png)](https://packagist.org/packages/mediawiki/semantic-media-wiki)

**Semantic MediaWiki** (a.k.a. SMW) is a free, open-source extension to [MediaWiki](https://www.semantic-mediawiki.org/wiki/MediaWiki) – the wiki software that
powers Wikipedia – that lets you store and query data within the wiki's pages.

Semantic MediaWiki is also a full-fledged framework, in conjunction with
many spinoff extensions, that can turn a wiki into a powerful and flexible
knowledge management system. All data created within SMW can easily be
published via the [Semantic Web](https://www.semantic-mediawiki.org/wiki/Semantic_Web),
allowing other systems to use this data seamlessly.

For a better understanding of how Semantic MediaWiki works, have a look at [deployed in 5 min](https://vimeo.com/82255034)
and the [Sesame](https://vimeo.com/126392433), [Fuseki ](https://vimeo.com/118614078) triplestore video, or
browse the [wiki](https://www.semantic-mediawiki.org) for a more comprehensive introduction.

## Requirements

Semantic MediaWiki requires MediaWiki and its dependencies, such as PHP.

Supported MediaWiki, PHP and database versions depend on the version of Semantic MediaWiki.
See the [compatibility matrix](docs/COMPATIBILITY.md) for details.

## Installation

The recommended way to install Semantic MediaWiki is by using [Composer][composer]. See the detailed
[installation guide](docs/INSTALL.md) as well as the information on [compatibility](docs/COMPATIBILITY.md).

## Documentation

Most of the documentation can be found on the [Semantic MediaWiki wiki](https://www.semantic-mediawiki.org).
A small core of documentation also comes bundled with the software itself. This documentation is minimalistic
and less explanatory than what can be found on the SMW wiki. It is however always kept up to date, and applies
to the version of the code it comes bundled with. The most important files are linked below.

* [User documentation](docs/README.md)
* [Technical documentation](docs/technical/README.md)
* [Hacking Semantic MediaWiki](docs/architecture/README.md)

## Support

[![Chatroom](https://www.semantic-mediawiki.org/w/thumb.php?f=Comment-alt-solid.svg&width=35)](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_chatroom)
[![Twitter](https://www.semantic-mediawiki.org/w/thumb.php?f=Twitter-square.svg&width=35)](https://twitter.com/#!/semanticmw)
[![Facebook](https://www.semantic-mediawiki.org/w/thumb.php?f=Facebook-square.svg&width=35)](https://www.facebook.com/pages/Semantic-MediaWiki/160459700707245)
[![LinkedIn](https://www.semantic-mediawiki.org/w/thumb.php?f=LinkedIn-square.svg&width=35)]([https://twitter.com/#!/semanticmw](https://www.linkedin.com/groups/2482811/))
[![YouTube](https://www.semantic-mediawiki.org/w/thumb.php?f=Youtube-square.svg&width=35)](https://www.youtube.com/c/semanticmediawiki)
[![Mailing lists](https://www.semantic-mediawiki.org/w/thumb.php?f=Envelope-square.svg&width=35)](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_mailing_lists)

Primary support channels:

* [User mailing list](https://sourceforge.net/projects/semediawiki/lists/semediawiki-user) - for user questions
* [SMW chat room](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_chatroom) - for questions and developer discussions
* [Issue tracker](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues) - for bug reports

## Contributing

Many people have contributed to SMW. A list of people who have made contributions in the past can
be found [here][contributors] or on the [wiki for Semantic MediaWiki](https://www.semantic-mediawiki.org/wiki/Help:SMW_Project#Contributors).
The overview on [how to contribute](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/CONTRIBUTING.md)
provides information on the different ways available to do so.

If you want to contribute work to the project please subscribe to the developers mailing list and
have a look at the contribution guidelines.

* [File an issue](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues)
* [Submit a pull request](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pulls)
* Ask a question on [the mailing list](https://www.semantic-mediawiki.org/wiki/Mailing_list)

## Tests

This extension provides unit and integration tests and are normally run by a [continuous integration platform][travis]
but can also be executed locally using the shortcut command `composer phpunit` from the extension base directory. A more
comprehensive introduction can be found under the [test section](/tests/README.md#running-tests).

## License

[GNU General Public License, version 2 or later][gpl-licence]. The COPYING file explains SMW's copyright and license.

[contributors]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/graphs/contributors
[travis]: https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki
[mw-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[gpl-licence]: https://www.gnu.org/copyleft/gpl.html
[composer]: https://getcomposer.org/
[smw-installation]: https://www.semantic-mediawiki.org/wiki/Help:Installation

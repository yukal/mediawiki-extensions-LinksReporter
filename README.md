# mediawiki-extensions-LinksReporter
The Links Reporter Extension for the MediaWiki engine

### Requires
- MediaWiki >= 1.29.0
- PHP >= 7.0

### Install
- Download extension
- Unpack extension: `tar xzf extension_name.tar.gz`
- Rename unpacked extension directory to LinksReporter
- Copy LinksReporter to MediaWiki extensions
- Set permissions of LinksReporter directory to a web user
- Activate extension in LocalSettings.php

To activate this extension, add the following into your LocalSettings.php file:
wfLoadExtension( 'LinksReporter' );

### Usage
Open the link: https://your-domain/Special:LinksReporter

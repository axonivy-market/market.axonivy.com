# dev.axonivy.com

## Setup
	docker-compose up -d
	docker-compose exec web composer install

## Execute tests
	docker-compose exec web ./vendor/bin/phpunit

## Ressources
* Slim Project Bootstrap <https://github.com/kalvn/Slim-Framework-Skeleton>
* SlimFramework <http://www.slimframework.com>
* Template <https://templated.co/introspect>
* JS-Framework <https://github.com/ajlkn/skel>

## Search
The files `src/app/search/_cse_annotations.xml` and `src/app/search/_cse_context.xml` are not referenced on the webpage.

But they are needed to add _Annotation_ and _Facet_  to the custom google search.
They could be uploaded to google on <https://cse.google.com/> with user info@ivyteam.ch.
Menu entry > Setup > Advanced.

## ToDo
* support /doc/latest/xxx
* ReleaseNotes.html
* Gartner-Image on InternetExplorer & Iphone

### After Release
* Fix Permalinks in Engine Guide
* Move all ReleaseNotes.txt from root to documents folder -> check build
* Update developerAPI url on update.axonivy.com
* PageSpeed
* Remove oboslete code in DocProvider, ReleaseInfo, ReleaseInfoRepository,DocAction

### Ideas
* Public Roadmap
* Blog on Medium
* Features Site
* Community Site

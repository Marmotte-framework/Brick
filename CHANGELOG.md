# Changelog

## v1.2.5

- A Service doesn't need to declare empty constructor

## v1.2.4

*2023-05-03*

- Fix `BrickLoader::loadBricks`: remove root package from packages

## v1.2.3

*2023-04-30*

- Remove duplicates when getting installed packages
- Exclude tests files in `.gitattributes`

## v1.2.2

*2023-04-30*

- Exclude tests dir from classmap

## v1.2.1

*2023-04-28*

- CacheManager create cache dir only if prod mode
- Fix `ServiceConfig::fromArray` called with null when loading Services

## v1.2.0

*2023-04-22*

- Fix code quality : rm use of singletons
- Name of cache files are now encoded with base64

## v1.1.0

*2023-04-18*

- Introduce class `ServiceConfig` for Services configuration
- Use this class in Services loading

## v1.0.1

*2023-04-02*

- Change package type of Bricks to `marmotte-brick`

## v1.0.0

*2023-03-25*

- Add base of Bricks

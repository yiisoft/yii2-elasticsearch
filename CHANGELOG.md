Yii Framework 2 Elasticsearch extension Change Log
==================================================

2.1.5 under development
-----------------------

- Bug #344: Disabled JSON pretty print for ElasticSearch bulk API (rhertogh)
- Bug #350: Remove deprecated code, set $pagination->totalCount (lav45)


2.1.4 May 22, 2023
------------------

- Bug #330: Fix `curlOptions` merging (yuniorsk)
- Bug #332: Added `[\ReturnTypeWillChange]` attribute for BatchQueryResult methods to be compatible with Iterator interface (warton)
- Enh #329: Added `curlOptions` attribute for advanced configuration of curl session (yuniorsk)


2.1.3 August 07, 2022
-----------------------

- Enh #311: Added support for runtime mappings in Elasticsearch 7.11+ (mabentley85)
- Enh #323: Updated API calls for compatibility with Elastcisearch 8+ (tehmaestro)
- Enh #323: Improved github testing workflow (terabytesoftw)
- Enh #318: Added "match" and "match_phrase" queries to query builder (Barton0403)


2.1.2 August 09, 2021
---------------------

- Enh #18817: Use `random_int()` when choosing connection (samdark)


2.1.1 May 06, 2021
------------------

- Bug #297: Fix `Query::count()` when index contains more than 10,000 documents (rhertogh)
- Bug #298: Fix ElasticSearch performance when passing `false` to `Query::source()` (rhertogh)


2.1.0 July 21, 2020
-------------------

- Bug #161: Changed yii\base\Object to yii\base\BaseObject (sashsvamir)
- Bug #171: Allow to have both `query()` and `where()` in a query (beowulfenator)
- Bug #176: Allow very long scroll id by passing scroll id in request body (russianlagman)
- Bug #180: Fixed `count()` compatibility with PHP 7.2 to not call it on scalar values (cebe)
- Bug #191: Fixed error when calling `column('_id')` in `ActiveQuery` (pvassiliou)
- Bug #216: Updated `suggest()` command to support Elasticsearch 6.5+ (beowulfenator)
- Bug #239: Make sure that `ElasticsearchTarget` consistently logs message as text (beowufenator)
- Bug: (CVE-2018-8074): Fixed possibility of manipulated condition when unfiltered input is passed to `ActiveRecord::findOne()` or `findAll()` (cebe)
- Enh #112: Added support for Elasticsearch 5.0. Minimum requirement is also now Elasticsearch 5.0 (holycheater, beowulfenator, i-lie)
- Enh #136: Added docs on error handling in queries (beowulfenator)
- Enh #156: Added suggester support to `ActiveDataProvider` (Julian-B90)
- Enh #222: Added collapse support (walkskyer)
- Enh #272: Add Elasticsearch 7 compatibility (beowulfenator)
- Chg #269: Replace InvalidParamException with InvalidArgumentException (Julian-B90)
- Chg: Removed `Command::getIndexStatus()` and added `getIndexStats()` and `getIndexRecoveryStats()` to reflect changes in Elasticsearch 5.0 (cebe)
- Chg: Search queries that result in a 404 error due to missing indices are now no longer silently interpreted as empty result, but will throw an exception (cebe)


2.0.7 June 01, 2020
-------------------

- Bug #199: Fixed `ActiveRecord::insert()` check if insert was indeed successful (rhertogh)
- Bug #248: Fix 'run query' in debugger tool (tunecino)
- Bug #257: ActiveRecord::get() for non-existent ID now works in PHP 7.4 (trifonovivan)
- Enh #56: Added docs regarding updates to attributes with "object" mapping (beowulfenator)


2.0.6 May 27, 2020
------------------

- Bug #180: Fixed `count()` compatibility with PHP 7.2 to not call it on scalar values (cebe)
- Bug #227: Fixed `Bad Request (#400): Unable to verify your data submission.` in debug details panel 'run query' (rhertogh)
- Enh #117: Add support for `QueryInterface::emulateExecution()` (cebe)


2.0.5 March 20, 2018
--------------------

- Bug #120: Fix debug panel markup to be compatible with Yii 2.0.10 (drdim)
- Bug #125: Fixed `ActiveDataProvider::refresh()` to also reset `$queryResults` data (sizeg)
- Bug #134: Fix infinite query loop "ActiveDataProvider" when the index does not exist (eolitich)
- Bug #149: Changed `yii\base\Object` to `yii\base\BaseObject` (dmirogin)
- Bug: (CVE-2018-8074): Fixed possibility of manipulated condition when unfiltered input is passed to `ActiveRecord::findOne()` or `findAll()` (cebe)
- Bug: Updated debug panel classes to be consistent with yii 2.0.7 (beowulfenator)
- Bug: Added accessor method for the default Elasticsearch primary key (kyle-mccarthy)
- Enh #15: Special data provider `yii\elasticsearch\ActiveDataProvider` created (klimov-paul)
- Enh #43: Elasticsearch log target (trntv, beowulfenator)
- Enh #47: Added support for post_filter option in search queries (mxkh)
- Enh #60: Minor updates to guide (devypt, beowulfenator)
- Enh #82: Support HTTPS protocol (dor-denis, beowulfenator)
- Enh #83: Support for "gt", ">", "gte", ">=", "lt", "<", "lte", "<=" operators in query (i-lie, beowulfenator)
- Enh #119: Added support for explanation on query (kyle-mccarthy)
- Enh #150: Explicitily send `Content-Type` header in HTTP requests to Elasticsearch (lubobill1990)
- Enh: Bulk API implemented and used in AR (tibee, beowulfenator)
- Enh: Deserialization of raw response when text/plain is supported (Tezd)
- Enh: Added ability to work with aliases through Command class (Tezd)


2.0.4 March 17, 2016
--------------------

- Bug #8: Fixed issue with running out of sockets when running a large number of requests by reusing curl handles (cebe)
- Bug #13: Fixed wrong API call for getting all types or searching all types, `_all` works only for indexes (cebe)
- Bug #19: `DeleteAll` now deletes all entries, not first 10 (beowulfenator)
- Bug #48: `UpdateAll` now updates all entries, not first 10 (beowulfenator)
- Bug #65: Fixed warning `array to string conversion` when parsing error response (rhertogh, silverfire)
- Bug #73: Fixed debug panel exception when no data was recorded for Elasticsearch panel (jafaripur)
- Enh #2: Added `min_score` option to query (knut)
- Enh #28: AWS Elasticsearch service compatibility (andrey-bahrachev)
- Enh #33: Implemented `Command::updateSettings()` and `Command::updateAnalyzers()` (githubjeka)
- Enh #50: Implemented HTTP auth (silverfire)
- Enh #62: Added support for scroll API in `batch()` and `each()` (beowulfenator, 13leaf)
- Enh #70: `Query` and `ActiveQuery` now have `$options` attribute that is passed to commands generated by queries (beowulfenator)
- Enh: Unified model creation from result set in `Query` and `ActiveQuery` with `populate()` (beowulfenator)


2.0.3 March 01, 2015
--------------------

- no changes in this release.


2.0.2 January 11, 2015
----------------------

- Enh: Added `ActiveFixture` class for testing fixture support for Elasticsearch (cebe, viilveer)


2.0.1 December 07, 2014
-----------------------

- Bug #5662: Elasticsearch AR updateCounters() now uses explicitly `groovy` script for updating making it compatible with ES >1.3.0 (cebe)
- Bug #6065: `ActiveRecord::unlink()` was failing in some situations when working with relations via array valued attributes (cebe)
- Enh #5758: Allow passing custom options to `ActiveRecord::update()` and `::delete()` including support for routing needed for updating records with parent relation (cebe)
- Enh: Add support for optimistic locking (cebe)


2.0.0 October 12, 2014
----------------------

- Enh #3381: Added ActiveRecord::arrayAttributes() to define attributes that should be treated as array when retrieved via `fields` (cebe)


2.0.0-rc September 27, 2014
---------------------------

- Bug #3587: Fixed an issue with storing empty records (cebe)
- Bug #4187: Elasticsearch dynamic scripting is disabled in 1.2.0, so do not use it in query builder (cebe)
- Enh #3527: Added `highlight` property to Query and ActiveRecord. (Borales)
- Enh #4048: Added `init` event to `ActiveQuery` classes (qiangxue)
- Enh #4086: changedAttributes of afterSave Event now contain old values (dizews)
- Enh: Make error messages more readable in HTML output (cebe)
- Enh: Added support for query stats (cebe)
- Enh: Added support for query suggesters (cebe, tvdavid)
- Enh: Added support for delete by query (cebe, tvdavid)
- Chg #4451: Removed support for facets and replaced them with aggregations (cebe, tadaszelvys)
- Chg: asArray in ActiveQuery is now equal to using the normal Query. This means, that the output structure has changed and `with` is supported anymore. (cebe)
- Chg: Deletion of a record is now also considered successful if the record did not exist. (cebe)
- Chg: Requirement changes: Yii now requires Elasticsearch version 1.0 or higher (cebe)


2.0.0-beta April 13, 2014
-------------------------

- Bug #1993: afterFind event in AR is now called after relations have been populated (cebe, creocoder)
- Bug #2324: Fixed QueryBuilder bug when building a query with "query" option (mintao)
- Enh #1313: made index and type available in `ActiveRecord::instantiate()` to allow creating records based on Elasticsearch type when doing cross index/type search (cebe)
- Enh #1382: Added a debug toolbar panel for Elasticsearch (cebe)
- Enh #1765: Added support for primary key path mapping, pk can now be part of the attributes when mapping is defined (cebe)
- Enh #2002: Added filterWhere() method to yii\elasticsearch\Query to allow easy addition of search filter conditions by ignoring empty search fields (samdark, cebe)
- Enh #2892: ActiveRecord dirty attributes are now reset after call to `afterSave()` so information about changed attributes is available in `afterSave`-event (cebe)
- Chg #1765: Changed handling of ActiveRecord primary keys, removed getId(), use getPrimaryKey() instead (cebe)
- Chg #2281: Renamed `ActiveRecord::create()` to `populateRecord()` and changed signature. This method will not call instantiate() anymore (cebe)
- Chg #2146: Removed `ActiveRelation` class and moved the functionality to `ActiveQuery`.
             All relational queries are now directly served by `ActiveQuery` allowing to use
             custom scopes in relations (cebe)


2.0.0-alpha, December 1, 2013
-----------------------------

- Initial release.


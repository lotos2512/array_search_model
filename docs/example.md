
## Example usage

```php
<?php

use lotos2512\array_search_model\ArraySearchModel;
use lotos2512\array_search_model\ArrayDataProvider;

class TickersArraySearchModel extends ArraySearchModel
{
    const SORT_LEADERS = 'leaders';
    const SORT_TRADING_VOLUME = 'value';
    const SORT_SURGE_TRADING = 'burst';
    const SORT_FORECAST = 'forecast';
    const SORT_UNDERESTIMATED = 'underestimated';
    const SORT_YIELD = 'yield';

    const DEFAULT_LIMIT = 30;
    const DEFAULT_OFFSET = 0;

    /***
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public static function search(array $params) : array
    {
        $params = self::prepareParams($params);
        $query = self::find();
        foreach ($params['where'] as $where) {
            $query->where($where);
        }
        $query->limit($params['limit']);
        $query->offset($params['offset']);
        $query->orderBy(self::sortTickers($params['sort'],$params['order']));
        return $query->all();
    }

    public function getDataProvider() : ArrayDataProvider
    {
        return new TickersArrayDataProvider();
    }
    /**
     * Сортировка тикеров
     * @param string $sort поле сортировки
     * @param string $order asc | desc
     * @return array
     */
    public static function sortTickers(string $sort, string $order) : callable
    {
        switch ($sort) {
            case self::SORT_LEADERS:
                return function ($tickers) use ($order) {
                    uasort($tickers, function ($a, $b) use ($order) {
                        $resultA = (float) $a['exchange_price_percent'];
                        $resultB = (float) $b['exchange_price_percent'];
                        if ($order == self::ORDER_ASK) {
                            $positionFirst = $resultA;
                            $positionWho = $resultB;
                        } else {
                            $positionFirst = $resultB;
                            $positionWho = $resultA;
                        }
                        return $positionFirst <=> $positionWho;
                    });
                    return $tickers;
                };
            case self::SORT_TRADING_VOLUME :
                return function ($tickers) use ($order) {
                    uasort($tickers, function ($a, $b) {
                        return (float) $b['deviation_average_trading_volume'] <=> (float) $a['deviation_average_trading_volume'];
                    });
                    return $tickers;
                };
            case self::SORT_FORECAST :
                return function ($tickers) {
                    uasort($tickers, function ($a, $b) {
                        return (float) @$b['forecasts_ideas'][0]['potential'] <=> (float) @$a['forecasts_ideas'][0]['potential'];
                    });
                    return $tickers;
                };
            case self::SORT_YIELD :
                return function ($tickers) {
                    uasort($tickers, function ($a, $b) {
                        return (float) $b['yield'] + (bool) $b['maturity_date'] <=> (float) $a['yield'] + (bool) $a['maturity_date'];
                    });
                    return $tickers;
                };
            default :
                return function ($tickers) {
                    uasort($tickers, function ($a, $b) {
                        return (float) $b['exchange_price_percent'] <=> (float) $a['exchange_price_percent'];
                    });
                    return $tickers;
                };
        }
    }


    protected static function prepareParams(array $params = []) : array
    {
        $result['sort'] = $params['sort'] ?? self::ORDER_DESK;
        $result['order'] = $params['order'] ?? self::ORDER_DESK;
        $result['limit'] = $params['limit'] ?? self::DEFAULT_LIMIT;
        $result['offset'] = $params['offset'] ?? self::DEFAULT_OFFSET;
        $result['where'][] = ['===', 'for_sale', true];

        if (isset($params['search'])) {
            trim($params['search']);
            if ($params['search'] !== "") {
                $result['where'][] = [
                    'regex',
                    function ($ticker) {
                        return [
                            $ticker['title'],
                            $ticker['beauty_title'],
                            @$ticker['company']['title'],
                            $ticker['beauty_company_name'],
                        ];
                    },
                    $params['search'],
                ];
            }
        }
        if (isset($params['type'])) {
            trim($params['type']);
            if ($params['type'] !== "") {
                $result['where'][] = [
                    '===',
                    'type',
                    $params['type']
                ];
            }
        }
        if (isset($params['sort'])) {
            trim($params['sort']);
            if ($params['sort'] !== "") {
                $where = self::getFilterByFort($params['sort']);
                if ($where !== null) {
                    $result['where'][] = $where;
                }
            }
        }
        return $result;
    }

    protected static function getFilterByFort(string $sort) : ? array
    {
        switch ($sort) {
            case  self::SORT_FORECAST:
                return [
                    '>',
                    function ($ticker) {
                        return @$ticker['forecasts_ideas'][0]['potential'];
                    },
                    0,
                ];
            case  self::SORT_UNDERESTIMATED:
                return [
                    '===',
                    function ($ticker) {
                        return @$ticker['company']['estimation'];
                    },
                    -1,
                ];
            case  self::SORT_YIELD:
                return [
                    '>',
                    'yield',
                    0,
                ];
            default :
                return null;
        }
    }
}

class TickersArrayDataProvider extends ArrayDataProvider
{
    public function getData() : array
    {
        return array (
                   0 =>
                       array (
                           'id' => '5a5cd9a9308a9fdfc65f8c25',
                           'title' => 'RUAL',
                           'fronturl' => 'http://domen.ru/ticker/101194',
                           'typeName' => 'Акция',
                           'emitbase_id' => 101194,
                           'bks_id' => 10299,
                           'bks_small_title' => NULL,
                           'bks_small_id' => NULL,
                           'type' => 'share',
                           'beauty_title' => NULL,
                           'beauty_company_name' => NULL,
                           'logo_link' => NULL,
                           'for_sale' => true,
                           'delay' => false,
                           'forecasts_ideas' =>
                               array (
                               ),
                           'company' =>
                               array (
                                   'id' => '5a5cd99c308a9fdeacd866ed',
                                   'title' => 'Русал',
                                   'emitbase_id' => 282,
                                   'logo_link' => 'http://domen.ru/images/97/40/b4725e9892466105a1bc393079bf5e31.png',
                                   'estimation' => 0,
                               ),
                           'share_type' => 'common',
                           'price' => 23.4200000000000017053025658242404460906982421875,
                           'exchange_price_percent' => 7.82688766114180456412441344582475721836090087890625,
                           'trading_mode' => true,
                           'currency' => 'RUB',
                           'trading_start_date' => NULL,
                           'trading_stop_date' => NULL,
                           'surge_trading' => true,
                           'deviation_average_trading_volume' => 145.2523333710732913459651172161102294921875,
                       ),
                   1 =>
                       array (
                           'id' => '5a5cd9a9308a9fdfc65f8c47',
                           'title' => 'RSTI',
                           'fronturl' => 'http://domen.ru/ticker/59433',
                           'typeName' => 'Акция',
                           'emitbase_id' => 59433,
                           'bks_id' => 4529,
                           'bks_small_title' => NULL,
                           'bks_small_id' => NULL,
                           'type' => 'share',
                           'beauty_title' => NULL,
                           'beauty_company_name' => NULL,
                           'logo_link' => NULL,
                           'for_sale' => true,
                           'delay' => false,
                           'forecasts_ideas' =>
                               array (
                                   0 =>
                                       array (
                                           'id' => '5acdd01549e065373d83e96a',
                                           'status' => 1,
                                           'is_changed' => false,
                                           'type' => 'forecast_idea',
                                           'fronturl' => 'http://domen.ru/news/forecast_idea/5acdd01549e065373d83e96a',
                                           'show_in_browser' => false,
                                           'publish_date_t' => 1513112400,
                                           'publish_date' => 'Tue, 12 Dec 2017 21:00:00 +0000',
                                           'modif_date_t' => 1513112400,
                                           'modif_date' => 'Wed, 11 Apr 2018 14:57:44 +0000',
                                           'create_date' => 'Tue, 12 Dec 2017 21:00:00 +0000',
                                           'meta_keywords' => '',
                                           'display_meta_keywords' => '',
                                           'meta_title' => '',
                                           'display_meta_title' => '',
                                           'short_url' => 'http://domen.ru/4geSwcm402u',
                                           'short_id' => '4geSwcm402u',
                                           'title' => '',
                                           'title_real' => '',
                                           'title_on_main' => '',
                                           'anons' => '',
                                           'body' => '',
                                           'to_news' => false,
                                           'video_ads_off' => false,
                                           'has_photo' => false,
                                           'type_name' => 'Прогноз/Инвест.идея',
                                           'bks_material' => false,
                                           'image' => NULL,
                                           'image_new_main' => NULL,
                                           'expiration_date' => '2018-12-13T00:00:00+00:00',
                                           'share_price' => 1.3453146000000002491248096703202463686466217041015625,
                                           'potential' => 68.1853481685210880414160783402621746063232421875,
                                       ),
                               ),
                           'company' =>
                               array (
                                   'id' => '5a5cd99b308a9fdeacd86521',
                                   'title' => 'Группа компаний Россети',
                                   'emitbase_id' => 20,
                                   'logo_link' => 'http://domen.ru/images/84/93/0e8af1a45230d301138b3c46824e7a69.jpg',
                                   'estimation' => 0,
                               ),
                           'share_type' => 'common',
                           'price' => 0.79990000000000005542233338928781449794769287109375,
                           'exchange_price_percent' => 2.485586162716207692113812299794517457485198974609375,
                           'trading_mode' => true,
                           'currency' => 'RUB',
                           'trading_start_date' => NULL,
                           'trading_stop_date' => NULL,
                           'surge_trading' => false,
                           'deviation_average_trading_volume' => 58.7802482046579797270169365219771862030029296875,
                       ),
                   2 =>
                       array (
                           'id' => '5a5cd9a8308a9fdfc65f8c13',
                           'title' => 'MFON',
                           'fronturl' => 'http://domen.ru/ticker/59330',
                           'typeName' => 'Акция',
                           'emitbase_id' => 59330,
                           'bks_id' => 4771,
                           'bks_small_title' => NULL,
                           'bks_small_id' => NULL,
                           'type' => 'share',
                           'beauty_title' => NULL,
                           'beauty_company_name' => NULL,
                           'logo_link' => NULL,
                           'for_sale' => true,
                           'delay' => false,
                           'forecasts_ideas' =>
                               array (
                                   0 =>
                                       array (
                                           'id' => '5acdd01549e065373d83eaca',
                                           'status' => 1,
                                           'is_changed' => false,
                                           'type' => 'forecast_idea',
                                           'fronturl' => 'http://domen.ru/news/forecast_idea/5acdd01549e065373d83eaca',
                                           'show_in_browser' => false,
                                           'publish_date_t' => 1521406800,
                                           'publish_date' => 'Sun, 18 Mar 2018 21:00:00 +0000',
                                           'modif_date_t' => 1521406800,
                                           'modif_date' => 'Wed, 11 Apr 2018 14:56:38 +0000',
                                           'create_date' => 'Sun, 18 Mar 2018 21:00:00 +0000',
                                           'meta_keywords' => '',
                                           'display_meta_keywords' => '',
                                           'meta_title' => '',
                                           'display_meta_title' => '',
                                           'short_url' => 'http://domen.ru/9qt2ZS3fKdZ',
                                           'short_id' => '9qt2ZS3fKdZ',
                                           'title' => '',
                                           'title_real' => '',
                                           'title_on_main' => '',
                                           'anons' => '',
                                           'body' => '',
                                           'to_news' => false,
                                           'video_ads_off' => false,
                                           'has_photo' => false,
                                           'type_name' => 'Прогноз/Инвест.идея',
                                           'bks_material' => false,
                                           'image' => NULL,
                                           'image_new_main' => NULL,
                                           'expiration_date' => '2019-03-19T00:00:00+00:00',
                                           'share_price' => 523.3914419999999836363713257014751434326171875,
                                           'potential' => 5.99259659781287457036569321644492447376251220703125,
                                       ),
                               ),
                           'company' =>
                               array (
                                   'id' => '5a5cd99b308a9fdeacd865fc',
                                   'title' => 'МегаФон',
                                   'emitbase_id' => 290,
                                   'logo_link' => 'http://domen.ru/images/94/57/639e981d58ee58d1c6ac31a948949c19.png',
                                   'estimation' => 0,
                               ),
                           'share_type' => 'common',
                           'price' => 493.80000000000001136868377216160297393798828125,
                           'exchange_price_percent' => 2.1937086092715230023486583377234637737274169921875,
                           'trading_mode' => true,
                           'currency' => 'RUB',
                           'trading_start_date' => NULL,
                           'trading_stop_date' => NULL,
                           'surge_trading' => false,
                           'deviation_average_trading_volume' => 43.25583867798303572271834127604961395263671875,
                       ),
                  );
    }
}

```
## Get data
```php
    $data = TickersArraySearchModel::search($_GET);
```

```php

     $data = TickersArraySearchModel::find()
            ->where(['===', 'type', TickerFormatter::TYPE_SHARE])
            ->where(['===', 'for_sale', true])
            ->orderBy(
                TickersArraySearchModel::sortTickers(
                    TickersArraySearchModel::SORT_LEADERS,
                    TickersArraySearchModel::ORDER_DESK
                )
            )
            ->limit(5)
            ->all()
```

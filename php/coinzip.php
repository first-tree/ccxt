<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class coinzip extends Exchange {
  
    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'coinzip',
            'name' => 'Coinzip',
            'countries' => array ( 'PHP', 'KR', 'JP', 'SG', 'AU' ),
            'rateLimit' => 400,
            'version' => 'v2',
            'has' => array (
                'CORS' =>  false,
                'fetchBalance' =>  true,
                'fetchCurrencies' =>  false,
                'fetchMarkets' =>  true,
                'createOrder' =>  true,
                'cancelOrder' =>  true,
                'fetchTicker' =>  true,
                'fetchOHLCV' =>  true,
                'fetchTicker' =>  true,
                'fetchTickers' =>  true,
                'fetchMyTrades' =>  true,
                'fetchTrades' =>  true,
                'fetchOrder' =>  true,
                'fetchOrders' =>  true,
                'fetchOpenOrders' =>  true,
                'fetchClosedOrders' =>  true,
                'deposit' =>  false,
                'withdraw' =>  false,
            ),
            'timeframes' => array (
                '1m' => '1',
                '5m' => '5',
                '15m' => '15',
                '30m' => '30',
                '1h' => '60',
                '2h' => '120',
                '4h' => '240',
                '12h' => '720',
                '1d' => '1440',
                '3d' => '4320',
                '1w' => '10080',
            ),
            'urls' => array (
                'logo' =>  'https://user-images.githubusercontent.com/coinzip.logo.jpg',
                'api' =>  'https://coinzip.co',
                'www' =>  'https://coinzip.co',
                'documents' =>  'https://coinzip.co/documents/api_v2',
            ),
            'requiredCredentials' => array (
                'apiKey' =>  true,
                'secret' =>  true,
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'markets',
                        'tickers',
                        'tickers/{market}',
                        'order_book',
                        'depth',
                        'trades',
                        'k',
                        'k_with_pending_trades',
                        'timestamp',
                    ),
                ),
                'private' => array (
                    'get' => array (
                        'members/me',
                        'deposits',
                        'deposit/id',
                        'orders',
                        'order',
                        'orders/summary',
                        'trades/my',
                    ),
                    'post' => array (
                        'orders',
                        'orders/multi',
                        'orders/clear',
                        'order/delete',
                    ),
                ),
            ),
            'exceptions' => array (
                '2002' => '\\ccxt\\InsufficientFunds',
                '2003' => '\\ccxt\\OrderNotFound',
            ),
        ));
    }

    public function fetch_markets ($params = array ()) {
        $markets = $this->publicGetMarkets ();
        $result = array ();
        for ($p = 0; $p < count ($markets); $p++) {
            $market = $markets[$p];
            $id = $market['id'];
            $symbol = $market['name'];
            $baseId = $this->safe_string($market, 'base_unit');
            $quoteId = $this->safe_string($market, 'quote_unit');
            if (($baseId === null) || ($quoteId === null)) {
                $ids = explode ('/', $symbol);
                $baseId = strtolower ($ids[0]);
                $quoteId = strtolower ($ids[1]);
            }
            $base = strtoupper ($baseId);
            $quote = strtoupper ($quoteId);
            $base = $this->common_currency_code($base);
            $quote = $this->common_currency_code($quote);
            // todo => find out their undocumented $precision and limits
            $precision = array (
                'amount' => 8,
                'price' => 8,
            );
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'precision' => $precision,
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $this->load_markets();
        $tickers = $this->publicGetTickers ($params);
        $ids = is_array ($tickers) ? array_keys ($tickers) : array ();
        $result = array ();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $market = null;
            $symbol = $id;
            if (is_array ($this->markets_by_id) && array_key_exists ($id, $this->markets_by_id)) {
                $market = $this->markets_by_id[$id];
                $symbol = $market['symbol'];
            } else {
                $base = mb_substr ($id, 0, 3);
                $quote = mb_substr ($id, 3, 6);
                $base = strtoupper ($base);
                $quote = strtoupper ($quote);
                $base = $this->common_currency_code($base);
                $quote = $this->common_currency_code($quote);
                $symbol = $base . '/' . $quote;
            }
            $ticker = $tickers[$id];
            $result[$symbol] = $this->parse_ticker($ticker, $market);
        }
        return $result;
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $response = $this->publicGetTickersMarket (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_ticker($response, $market);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market' => $market['id'],
        );
        if ($limit !== null)
            $request['limit'] = $limit; // default = 300
        $orderbook = $this->publicGetDepth (array_merge ($request, $params));
        $timestamp = $orderbook['timestamp'] * 1000;
        return $this->parse_order_book($orderbook, $timestamp);
    }

    public function fetch_ohlcv ($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        if ($limit === null)
            $limit = 500; // default is 30
        $request = array (
            'market' => $market['id'],
            'period' => $this->timeframes[$timeframe],
            'limit' => $limit,
        );
        if ($since !== null)
            $request['timestamp'] = $since;
        $response = $this->publicGetK (array_merge ($request, $params));
        return $this->parse_ohlcvs($response, $market, $timeframe, $since, $limit);
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $response = $this->publicGetTrades (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_trades($response, $market, $since, $limit);
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $ticker['at'] * 1000;
        $ticker = $ticker['ticker'];
        $symbol = null;
        if ($market)
            $symbol = $market['symbol'];
        $last = $this->safe_float($ticker, 'last');
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high'),
            'low' => $this->safe_float($ticker, 'low'),
            'bid' => $this->safe_float($ticker, 'buy'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'sell'),
            'askVolume' => null,
            'vwap' => null,
            'open' => $this->safe_float($ticker, 'open'),
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->safe_float($ticker, 'vol'),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->parse8601 ($trade['created_at']);
        return array (
            'id' => (string) $trade['id'],
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $market['symbol'],
            'type' => null,
            'side' => null,
            'price' => $this->safe_float($trade, 'price'),
            'amount' => $this->safe_float($trade, 'volume'),
            'cost' => $this->safe_float($trade, 'funds'),
            'info' => $trade,
        );
    }

    public function parse_ohlcv ($ohlcv, $market = null, $timeframe = '1m', $since = null, $limit = null) {
        return [
            $ohlcv[0] * 1000,
            $ohlcv[1],
            $ohlcv[2],
            $ohlcv[3],
            $ohlcv[4],
            $ohlcv[5],
        ];
    }

    public function parse_order ($order, $market = null) {
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        } else {
            $marketId = $order['market'];
            $symbol = $this->markets_by_id[$marketId]['symbol'];
        }
        $timestamp = $this->parse8601 ($order['created_at']);
        $state = $order['state'];
        $status = null;
        if ($state === 'done') {
            $status = 'closed';
        } else if ($state === 'wait') {
            $status = 'open';
        } else if ($state === 'cancel') {
            $status = 'canceled';
        }
        return array (
            'id' => (string) $order['id'],
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => null,
            'status' => $status,
            'symbol' => $symbol,
            'type' => $order['ord_type'],
            'side' => $order['side'],
            'price' => $this->safe_float($order, 'price'),
            'amount' => $this->safe_float($order, 'volume'),
            'filled' => $this->safe_float($order, 'executed_volume'),
            'remaining' => $this->safe_float($order, 'remaining_volume'),
            'trades' => null,
            'fee' => null,
            'info' => $order,
        );
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function encode_params ($params) {
        if (is_array ($params) && array_key_exists ('orders', $params)) {
            $orders = $params['orders'];
            $query = $this->urlencode ($this->keysort ($this->omit ($params, 'orders')));
            for ($i = 0; $i < count ($orders); $i++) {
                $order = $orders[$i];
                $keys = is_array ($order) ? array_keys ($order) : array ();
                for ($k = 0; $k < count ($keys); $k++) {
                    $key = $keys[$k];
                    $value = $order[$key];
                    $query .= '&$orders%5B%5D%5B' . $key . '%5D=' . (string) $value;
                }
            }
            return $query;
        }
        return $this->urlencode ($this->keysort ($params));
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $request = '/api/' . $this->version . '/' . $this->implode_params($path, $params);
        if (is_array ($this->urls) && array_key_exists ('extension', $this->urls))
            $request .= $this->urls['extension'];
        $query = $this->omit ($params, $this->extract_params($path));
        $url = $this->urls['api'] . $request;
        if ($api === 'public') {
            if ($query) {
                $url .= '?' . $this->urlencode ($query);
            }
        } else {
            $this->check_required_credentials();
            $nonce = (string) $this->nonce ();
            $query = $this->encode_params (array_merge (array (
                'access_key' => $this->apiKey,
                'tonce' => $nonce,
            ), $params));
            $auth = $method . '|' . $request . '|' . $query;
            $signed = $this->hmac ($this->encode ($auth), $this->encode ($this->secret));
            $suffix = $query . '&signature=' . $signed;
            if ($method === 'GET') {
                $url .= '?' . $suffix;
            } else {
                $body = $suffix;
                $headers = array ( 'Content-Type' => 'application/x-www-form-urlencoded' );
            }
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body, $response) {
        if ($code === 400) {
            $error = $this->safe_value($response, 'error');
            $errorCode = $this->safe_string($error, 'code');
            $feedback = $this->id . ' ' . $this->json ($response);
            $exceptions = $this->exceptions;
            if (is_array ($exceptions) && array_key_exists ($errorCode, $exceptions)) {
                throw new $exceptions[$errorCode] ($feedback);
            }
            // fallback to default $error handler
        }
    }
}

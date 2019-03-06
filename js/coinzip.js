'use strict';

//  ---------------------------------------------------------------------------

const Exchange = require ('./base/Exchange');
const { InsufficientFunds, OrderNotFound, ArgumentsRequired } = require ('./base/errors');

//  ---------------------------------------------------------------------------

module.exports = class coinzip extends Exchange {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'coinzip',
            'name': 'Coinzip',
            'countries': ['PH', 'KR', 'JP', 'SG', 'AU'],
            'rateLimit': 400,
            'version': 'v2',
            'has': {
                'CORS': false,
                'fetchBalance': true,
                'fetchCurrencies': false,
                'fetchMarkets': true,
                'createOrder': true,
                'cancelOrder': true,
                'fetchTicker': true,
                'fetchOHLCV': true,
                'fetchTicker': true,
                'fetchTickers': true,
                'fetchMyTrades': true,
                'fetchTrades': true,
                'fetchOrder': true,
                'fetchOrders': true,
                'fetchOpenOrders': true,
                'fetchClosedOrders': true,
                'deposit': false,
                'withdraw': false
            },
            'timeframes': {
                '1m': '1',
                '5m': '5',
                '15m': '15',
                '30m': '30',
                '1h': '60',
                '2h': '120',
                '4h': '240',
                '12h': '720',
                '1d': '1440',
                '3d': '4320',
                '1w': '10080',
            },
            'urls': {
                'logo': 'https://user-images.githubusercontent.com/786083/53869301-ecfc2200-4032-11e9-80bc-42eda484076f.png',
                'api': 'https://staging.coinzip.co',
                'www': 'https://staging.coinzip.co',
                'documents': 'https://staging.coinzip.co/documents/api_v2'
            },
            'requiredCredentials': {
                'apiKey': true,
                'secret': true
            },
            'api': {
                'public': {
                    'get': [
                        'markets',
                        'tickers',
                        'tickers/{market}',
                        'order_book',
                        'depth',
                        'trades',
                        'k',
                        'k_with_pending_trades',
                        'timestamp'
                    ]
                },
                'private': {
                    'get': [
                        'members/me',
                        'deposits',
                        'deposit/id',
                        'orders',
                        'order',
                        'orders/summary',
                        'trades/my',
                    ],
                    'post': [
                        'orders',
                        'orders/multi',
                        'orders/clear',
                        'order/delete'
                    ]
                }
            },
            'exceptions': {
                '2002': InsufficientFunds,
                '2003': OrderNotFound,
            },
        });
    }

    async fetchTickers (params = {}) {
        await this.loadMarkets ();
        const tickers = await this.publicGetTickers (params);
        return Object.keys(tickers).reduce((obj, k) => {
            const { at, ticker } = tickers[k];
            const timestamp = at * 1000;
            const { symbol } = Object
                .keys (this.markets)
                .map (m => this.markets[m])
                .find (m => m.id === k.toUpperCase ());

            obj[symbol] = {
                'symbol': symbol,
                'timestamp': timestamp,
                'datetime': this.iso8601 (timestamp),
                'high': this.safeFloat (ticker, 'high'),
                'low': this.safeFloat (ticker, 'low'),
                'bid': this.safeFloat (ticker, 'buy'),
                'bidVolume': undefined,
                'ask': this.safeFloat (ticker, 'sell'),
                'askVolume': undefined,
                'vwap': undefined,
                'open':  this.safeFloat (ticker, 'open'),
                'close': ticker.last,
                'last': ticker.last,
                'previousClose': undefined,
                'change': this.safeFloat (ticker, 'change'),
                'percentage': undefined,
                'average': undefined,
                'baseVolume': this.safeFloat (ticker, 'vol'),
                'quoteVolume': undefined,
                'info': ticker,
            }

            return obj;
        }, {});
    }

    async fetchTicker (symbol, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchTicker requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const tickerObj = await this.publicGetTickersMarket ({ market });
        const { at, ticker } = tickerObj;
        const timestamp = at * 1000;

        return  {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'high': this.safeFloat (ticker, 'high'),
            'low': this.safeFloat (ticker, 'low'),
            'bid': this.safeFloat (ticker, 'buy'),
            'bidVolume': undefined,
            'ask': this.safeFloat (ticker, 'sell'),
            'askVolume': undefined,
            'vwap': undefined,
            'open':  this.safeFloat (ticker, 'open'),
            'close': ticker.last,
            'last': ticker.last,
            'previousClose': undefined,
            'change': undefined,
            'percentage': undefined,
            'average': undefined,
            'baseVolume': this.safeFloat (ticker, 'vol'),
            'quoteVolume': undefined,
            'info': ticker,
        }
    }

    async fetchMarkets (params = {}) {
        const markets = await this.publicGetMarkets (params);

        return markets.map(m => ({
            'id': m.id.toUpperCase (),
            'symbol': m.name,
            'base': m.base_unit.toUpperCase (),
            'quote': m.quote_unit.toUpperCase (),
            'baseId': m.base_unit,
            'quoteId': m.quote_unit,
            'info': m,
            'precision': {
                'amount': 8,
                'price': 8,
            },
            'limits': {
                'amount': {
                    'min': undefined,
                    'max': undefined,
                },
                'price': {
                    'min': undefined,
                    'max': undefined,
                },
                'cost': {
                    'min': undefined,
                    'max': undefined,
                },
            }
        }));
    }

    async fetchOrderBook (symbol, limit = 30, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchOrderBook requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const orderbook = await this.publicGetOrderBook (this.extend ({
            'market': market,
            'asks_limit': limit,
            'bids_limit': limit
        }, params));

        return this.parseOrderBook (orderbook, undefined, 'bids', 'asks', 'price', 'volume');
    }

    async fetchOHLCV (symbol, timeframe = '1m', since = undefined, limit = 500, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchOHLCV requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const period = this.timeframes[timeframe];
        const timestamp = since;
        const query = this.extend ({ market, period, timestamp, limit}, params);
        const response =  await this.publicGetK(query);

        return this.parseOHLCVs (response, market, period, timestamp, limit);
    }

    async fetchTrades (symbol, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchTrades requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const trades = await this.publicGetTrades (this.extend ({
            'market': market,
            'timestamp': since,
            'limit': limit
        }, params));

        return this.parseTrades (trades, symbol, since, limit);
    }

    async fetchMyTrades (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchMyTrades requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const response = await this.privateGetTradesMy (this.extend ({
            'market': market,
            'timestamp': since,
            'limit': limit
		}, params));

        return this.parseTrades (response, market, since, limit);
    }

    async fetchBalance (params = {}) {
        const { accounts: balances } = await this.privateGetMembersMe ();
        const mappedBalances = balances.reduce((obj, { balance, locked, currency }) => {
            const free = parseFloat (balance);
            const used = parseFloat (locked);

            obj[currency.toUpperCase()] = {
                free,
                used,
                total: this.sum (free, used)
            };

            return obj;
        }, {});
        const result = {
            info: balances,
            ...mappedBalances
        };

        return this.parseBalance (result);
    }

    async fetchOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        const response = await this.privateGetOrder (this.extend ({
            'id': parseInt (id),
        }, params));
        return this.parseOrder (response);
    }

    async fetchOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchOrders requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const response = await this.privateGetOrders (this.extend ({
            'market': market,
            'timestamp': since,
            'limit': limit,
			'state': 'all'
        }, params));


        return this.parseOrders (response, market, since, limit);
    }

    async fetchOpenOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchOrders requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const response = await this.privateGetOrders (this.extend ({
            'market': market,
            'timestamp': since,
            'limit': limit,
			'state': 'wait'
        }, params));

        return this.parseOrders (response, market, since, limit);
    }

    async fetchClosedOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' fetchOrders requires a symbol argument');

        const market = symbol.replace ('/', '').toLowerCase ();
        const response = await this.privateGetOrders (this.extend ({
            'market': market,
            'timestamp': since,
            'limit': limit,
			'state': 'done'
        }, params));

        return this.parseOrders (response, market, since, limit);
    }

    async createOrder (symbol, type, side, amount, price = undefined, params = {}) {
        if (symbol === undefined)
            throw new ArgumentsRequired (this.id + ' createOrder requires a symbol argument');

        await this.loadMarkets ();
        const order = {
            market: this.marketId (symbol).toLowerCase (),
            volume: amount.toString (),
            ord_type: type,
            side,
            ...((type === 'limit') && { price: price.toString () })
        }
        const response = await this.privatePostOrders (this.extend (order, params));
        const market = this.markets_by_id[response['market']];
        return this.parseOrder (response, market);
    }

    async cancelOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        const result = await this.privatePostOrderDelete ({ 'id': id });
        const order  = this.parseOrder (result);
        if (order.status === 'closed' || order.status === 'canceled') {
            throw new OrderNotFound (this.id + ' ' + this.json (order));
        }
        return order;
    }

    parseOHLCV (ohlcv, market = undefined, timeframe = '1m', since = undefined, limit = undefined) {
        return [
            ohlcv[0] * 1000,
            ohlcv[1],
            ohlcv[2],
            ohlcv[3],
            ohlcv[4],
            ohlcv[5],
        ];
    }

    parseTrade (trade, market = undefined) {
        const timestamp = this.parse8601 (trade['created_at']);
        return {
            'id': trade.id.toString (),
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'symbol': market,
            'type': undefined,
            'side': trade.side || undefined,
            'price': this.safeFloat (trade, 'price'),
            'amount': this.safeFloat (trade, 'volume'),
            'cost': this.safeFloat (trade, 'funds'),
            'info': trade,
        };
    }


    parseOrder (order, market = undefined) {
        const status = (() => {
            if (order.state === 'done') return 'closed';
            if (order.state === 'wait') return 'open';
            return 'canceled';
        })();
        const symbol = (() => {
            if (market !== undefined) return market.symbol;
            const marketId = order.market.toUpperCase ();
            return this.markets_by_id[marketId]['symbol'];
        })();
        const timestamp = this.parse8601 (order['created_at']);
        return {
            'id': order.id.toString (),
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'lastTradeTimestamp': undefined,
            'status': status,
            'symbol': symbol,
            'type': order.ord_type,
            'side': order.side,
            'price': this.safeFloat (order, 'price'),
            'amount': this.safeFloat (order, 'volume'),
            'filled': this.safeFloat (order, 'executed_volume'),
            'remaining': this.safeFloat (order, 'remaining_volume'),
            'trades': undefined,
            'fee': undefined,
            'info': order,
        };
    }

    sign (path, api = 'public', method = 'GET', params = {}, headers = undefined, body = undefined) {
        let request = '/api/' + this.version + '/' + this.implodeParams (path, params);
        if ('extension' in this.urls)
            request += this.urls['extension'];
        let query = this.omit (params, this.extractParams (path));
        let url = this.urls['api'] + request;
        if (api === 'public') {
            if (Object.keys (query).length) {
                url += '?' + this.urlencode (query);
            }
        } else {
            this.checkRequiredCredentials ();
            let nonce = this.now ();
            let query = this.encodeParams (this.extend ({
                'access_key': this.apiKey,
                'tonce': nonce,
            }, params));
            let auth = method + '|' + request + '|' + query;
            let signed = this.hmac (this.encode (auth), this.encode (this.secret));
            let suffix = query + '&signature=' + signed;
            if (method === 'GET') {
                url += '?' + suffix;
            } else {
                body = suffix;
                headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
            }
        }
        return { 'url': url, 'method': method, 'body': body, 'headers': headers };
    }

    encodeParams (params) {
        if ('orders' in params) {
            let orders = params['orders'];
            let query = this.urlencode (this.keysort (this.omit (params, 'orders')));
            for (let i = 0; i < orders.length; i++) {
                let order = orders[i];
                let keys = Object.keys (order);
                for (let k = 0; k < keys.length; k++) {
                    let key = keys[k];
                    let value = order[key];
                    query += '&orders%5B%5D%5B' + key + '%5D=' + value.toString ();
                }
            }
            return query;
        }
        return this.urlencode (this.keysort (params));
    }
};


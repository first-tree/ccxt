# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxt.async_support.base.exchange import Exchange
from ccxt.base.errors import InsufficientFunds
from ccxt.base.errors import OrderNotFound


class coinzip (Exchange):

    def describe(self):
        return self.deep_extend(super(coinzip, self).describe(), {
            'id': 'coinzip',
            'name': 'Coinzip',
            'countries': ['PH', 'KR', 'JP', 'SG', 'AU'],
            'rateLimit': 400,
            'version': 'v2',
            'has': {
                'CORS': False,
                'fetchBalance': True,
                'fetchCurrencies': False,
                'fetchMarkets': True,
                'createOrder': True,
                'cancelOrder': True,
                'fetchOHLCV': True,
                'fetchTicker': True,
                'fetchTickers': True,
                'fetchMyTrades': True,
                'fetchTrades': True,
                'fetchOrder': True,
                'fetchOrders': True,
                'fetchOpenOrders': True,
                'fetchClosedOrders': True,
                'deposit': False,
                'withdraw': False,
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
                'api': 'https://www.coinzip.co',
                'www': 'https://www.coinzip.co',
                'documents': 'https://www.coinzip.co/documents/api_v2',
            },
            'requiredCredentials': {
                'apiKey': True,
                'secret': True,
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
                        'timestamp',
                    ],
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
                        'order/delete',
                    ],
                },
            },
            'exceptions': {
                '2002': InsufficientFunds,
                '2003': OrderNotFound,
            },
        })

    async def fetch_markets(self, params={}):
        markets = await self.publicGetMarkets()
        result = []
        for p in range(0, len(markets)):
            market = markets[p]
            id = market['id']
            symbol = market['name']
            baseId = self.safe_string(market, 'base_unit')
            quoteId = self.safe_string(market, 'quote_unit')
            if (baseId is None) or (quoteId is None):
                ids = symbol.split('/')
                baseId = ids[0].lower()
                quoteId = ids[1].lower()
            base = baseId.upper()
            quote = quoteId.upper()
            base = self.common_currency_code(base)
            quote = self.common_currency_code(quote)
            # todo: find out their undocumented precision and limits
            precision = {
                'amount': 8,
                'price': 8,
            }
            result.append({
                'id': id,
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'baseId': baseId,
                'quoteId': quoteId,
                'precision': precision,
                'info': market,
            })
        return result

    async def fetch_balance(self, params={}):
        await self.load_markets()
        response = await self.privateGetMembersMe()
        balances = response['accounts']
        result = {'info': balances}
        for b in range(0, len(balances)):
            balance = balances[b]
            currency = balance['currency']
            uppercase = currency.upper()
            account = {
                'free': float(balance['balance']),
                'used': float(balance['locked']),
                'total': 0.0,
            }
            account['total'] = self.sum(account['free'], account['used'])
            result[uppercase] = account
        return self.parse_balance(result)

    async def fetch_order_book(self, symbol, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        request = {
            'market': market['id'],
        }
        if limit is not None:
            request['limit'] = limit  # default = 300
        orderbook = await self.publicGetDepth(self.extend(request, params))
        timestamp = orderbook['timestamp'] * 1000
        return self.parse_order_book(orderbook, timestamp)

    def parse_ticker(self, ticker, market=None):
        timestamp = ticker['at'] * 1000
        ticker = ticker['ticker']
        symbol = None
        if market:
            symbol = market['symbol']
        last = self.safe_float(ticker, 'last')
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'high': self.safe_float(ticker, 'high'),
            'low': self.safe_float(ticker, 'low'),
            'bid': self.safe_float(ticker, 'buy'),
            'bidVolume': None,
            'ask': self.safe_float(ticker, 'sell'),
            'askVolume': None,
            'vwap': None,
            'open': self.safe_float(ticker, 'open'),
            'close': last,
            'last': last,
            'previousClose': None,
            'change': None,
            'percentage': None,
            'average': None,
            'baseVolume': self.safe_float(ticker, 'vol'),
            'quoteVolume': None,
            'info': ticker,
        }

    async def fetch_tickers(self, symbols=None, params={}):
        await self.load_markets()
        tickers = await self.publicGetTickers(params)
        ids = list(tickers.keys())
        result = {}
        for i in range(0, len(ids)):
            id = ids[i]
            market = None
            symbol = id
            if id in self.markets_by_id:
                market = self.markets_by_id[id]
                symbol = market['symbol']
            else:
                base = id[0:3]
                quote = id[3:6]
                base = base.upper()
                quote = quote.upper()
                base = self.common_currency_code(base)
                quote = self.common_currency_code(quote)
                symbol = base + '/' + quote
            ticker = tickers[id]
            result[symbol] = self.parse_ticker(ticker, market)
        return result

    async def fetch_ticker(self, symbol, params={}):
        await self.load_markets()
        market = self.market(symbol)
        response = await self.publicGetTickersMarket(self.extend({
            'market': market['id'],
        }, params))
        return self.parse_ticker(response, market)

    def parse_trade(self, trade, market=None):
        timestamp = self.parse8601(trade['created_at'])
        return {
            'id': str(trade['id']),
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'symbol': market['symbol'],
            'type': None,
            'side': None,
            'price': self.safe_float(trade, 'price'),
            'amount': self.safe_float(trade, 'volume'),
            'cost': self.safe_float(trade, 'funds'),
            'info': trade,
        }

    async def fetch_trades(self, symbol, since=None, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        response = await self.publicGetTrades(self.extend({
            'market': market['id'],
        }, params))
        return self.parse_trades(response, market, since, limit)

    def parse_ohlcv(self, ohlcv, market=None, timeframe='1m', since=None, limit=None):
        return [
            ohlcv[0] * 1000,
            ohlcv[1],
            ohlcv[2],
            ohlcv[3],
            ohlcv[4],
            ohlcv[5],
        ]

    async def fetch_ohlcv(self, symbol, timeframe='1m', since=None, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        if limit is None:
            limit = 500  # default is 30
        request = {
            'market': market['id'],
            'period': self.timeframes[timeframe],
            'limit': limit,
        }
        if since is not None:
            request['timestamp'] = since
        response = await self.publicGetK(self.extend(request, params))
        return self.parse_ohlcvs(response, market, timeframe, since, limit)

    def parse_order(self, order, market=None):
        symbol = None
        if market is not None:
            symbol = market['symbol']
        else:
            marketId = order['market']
            symbol = self.markets_by_id[marketId]['symbol']
        timestamp = self.parse8601(order['created_at'])
        state = order['state']
        status = None
        if state == 'done':
            status = 'closed'
        elif state == 'wait':
            status = 'open'
        elif state == 'cancel':
            status = 'canceled'
        return {
            'id': str(order['id']),
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'lastTradeTimestamp': None,
            'status': status,
            'symbol': symbol,
            'type': order['ord_type'],
            'side': order['side'],
            'price': self.safe_float(order, 'price'),
            'amount': self.safe_float(order, 'volume'),
            'filled': self.safe_float(order, 'executed_volume'),
            'remaining': self.safe_float(order, 'remaining_volume'),
            'trades': None,
            'fee': None,
            'info': order,
        }

    async def fetch_order(self, id, symbol=None, params={}):
        await self.load_markets()
        response = await self.privateGetOrder(self.extend({
            'id': int(id),
        }, params))
        return self.parse_order(response)

    async def fetch_orders(self, symbol=None, since=None, limit=None, params={}):
        await self.load_markets()
        response = await self.privateGetOrders(self.extend({
            'market': self.market_id(symbol),
            'timestamp': since,
            'limit': limit,
            'state': 'all',
        }, params))
        return self.parse_orders(response, symbol, since, limit)

    async def fetch_open_orders(self, symbol=None, since=None, limit=None, params={}):
        await self.load_markets()
        response = await self.privateGetOrders(self.extend({
            'market': self.market_id(symbol),
            'timestamp': since,
            'limit': limit,
            'state': 'wait',
        }, params))
        return self.parse_orders(response, symbol, since, limit)

    async def fetch_closed_orders(self, symbol=None, since=None, limit=None, params={}):
        await self.load_markets()
        response = await self.privateGetOrders(self.extend({
            'market': self.market_id(symbol),
            'timestamp': since,
            'limit': limit,
            'state': 'done',
        }, params))
        return self.parse_orders(response, symbol, since, limit)

    async def create_order(self, symbol, type, side, amount, price=None, params={}):
        await self.load_markets()
        order = {
            'market': self.market_id(symbol),
            'side': side,
            'volume': str(amount),
            'ord_type': type,
        }
        if type == 'limit':
            order['price'] = str(price)
        response = await self.privatePostOrders(self.extend(order, params))
        market = self.markets_by_id[response['market']]
        return self.parse_order(response, market)

    async def cancel_order(self, id, symbol=None, params={}):
        await self.load_markets()
        result = await self.privatePostOrderDelete({'id': id})
        order = self.parse_order(result)
        status = order['status']
        if status == 'closed' or status == 'canceled':
            raise OrderNotFound(self.id + ' ' + self.json(order))
        return order

    def sign(self, path, api='public', method='GET', params={}, headers=None, body=None):
        request = '/api/' + self.version + '/' + self.implode_params(path, params)
        if 'extension' in self.urls:
            request += self.urls['extension']
        query = self.omit(params, self.extract_params(path))
        url = self.urls['api'] + request
        if api == 'public':
            if query:
                url += '?' + self.urlencode(query)
        else:
            self.check_required_credentials()
            nonce = self.now()
            query = self.encode_params(self.extend({
                'access_key': self.apiKey,
                'tonce': nonce,
            }, params))
            auth = method + '|' + request + '|' + query
            signed = self.hmac(self.encode(auth), self.encode(self.secret))
            suffix = query + '&signature=' + signed
            if method == 'GET':
                url += '?' + suffix
            else:
                body = suffix
                headers = {'Content-Type': 'application/x-www-form-urlencoded'}
        return {'url': url, 'method': method, 'body': body, 'headers': headers}

    def encode_params(self, params):
        if 'orders' in params:
            orders = params['orders']
            query = self.urlencode(self.keysort(self.omit(params, 'orders')))
            for i in range(0, len(orders)):
                order = orders[i]
                keys = list(order.keys())
                for k in range(0, len(keys)):
                    key = keys[k]
                    value = order[key]
                    query += '&orders%5B%5D%5B' + key + '%5D=' + str(value)
            return query
        return self.urlencode(self.keysort(params))

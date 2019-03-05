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
                // 'api' =>  'https://coinzip.co',
                'api' =>  'http://localhost:3000',
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
}

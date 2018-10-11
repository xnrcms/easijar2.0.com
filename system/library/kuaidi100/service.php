<?php

namespace kuaidi100;

/**
 * Logistics service implemented with Kuaidi100's api
 */
class Service
{
    /**
     * 公司编号
     * @var string
     */
    private $name;

    /**
     * 授权码
     * @var string
     */
    private $key;

    /**
     * more options
     * @var array
     */
    private $options;

    /**
     * http client
     */
    private $client;

    /**
     * @param string $name 快递100分配的公司编号(不是快递公司编号)
     * @param string $key 授权码
     * @param array $options some more configurations:
     *                         - notification_url  url to receive notification
     *                         - salt              salt for response signature verification
     */
    public function __construct($name, $key, $options = [], $client = null)
    {
        $this->name = $name;
        $this->key = $key;
        $this->options = $options;

        $this->client = $client ?: $this->createDefaultHttpClient();
    }

    private function createDefaultHttpClient(array $config = [])
    {
        return new Client($config);
    }

    /**
     * (sync) query logistics inforamtion
     * @param string $company
     * @param $waybillNo
     * @param $from
     * @param $to
     * @return object
     * @throws \Exception
     */
    public function query($company, $waybillNo, $from = '', $to = '')
    {
        $this->validateWaybillNo($waybillNo);

        // send request
        $response = $this->client->request('http://poll.kuaidi100.com/poll/query.do',
            $this->buildRequestForQuery($company, $waybillNo, $from, $to));
        if (!$response) {
            throw new \Exception('快递100服务异常');
        }

        return $response;
    }

    private function validateWaybillNo($waybillNo)
    {
        // waybill number can not be neither blank nor larger than 32 in length
        if (empty($waybillNo) || strlen($waybillNo) > 32) {
            throw new \InvalidArgumentException('错误的运单号');
        }
    }

    /*
     * validate params for tracking
     */

    private function buildRequestForQuery($company, $waybillNo, $from, $to)
    {
        $param = ['com' => $company, 'num' => $waybillNo, 'from' => $from, 'to' => $to];
        $params = [];
        $params['sign'] = strtoupper(md5(json_encode($param) . $this->key . $this->name));
        $params['customer'] = $this->name;
        $params['param'] = json_encode($param);

        return $params;
    }

    /*
     * create default http client
     *
     * @param array $config        Client configuration settings. See \GuzzleHttp\Client::__construct()
     * @return \GuzzleHttp\Client
     */

    private function parseForLogistics($logistic)
    {
        return (object)[
            'state' => $logistic->state, /* 当前签收状态，0在途中、1已揽收、2疑难、3已签收、4退签、5同城派送中、6退回、7转单等 */
            'signed' => $logistic->ischeck == '1',  /* 是否签收 0未签收、1已签收 */
            'waybillNo' => $logistic->nu,      /* 运单号 */
            'company' => $logistic->com,     /* 快递公司编码 */
            'items' => empty($logistic->data) ? null : array_map(function ($item) {
                return (object)[
                    'time' => $item->ftime,
                    'state' => $item->status,
                    'desc' => $item->context
                ];
            }, $logistic->data)
        ];
    }
}
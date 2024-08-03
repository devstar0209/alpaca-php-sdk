<?php namespace Alpaca\Api;

use Alpaca\Alpaca;

class Positions
{

    /**
     * Response array
     *
     * @var \Alpaca\Alpaca
     */
    private $alp;

    /**
     *  __construct 
     *
     */
    public function __construct(Alpaca $alp) {
        $this->alp = $alp;
    }

    /**
     * get()
     *
     * @return array
     */
    public function get($stock) {
        return $this->alp->request('position',['stock'=>$stock])->contents();
    }

    /**
     * getAll()
     *
     * @return array
     */
    public function getAll() {
        return $this->alp->request('positions',[])->contents();
    }

    /**
     * close()
     *
     * @return array
     */
    public function close($stock) {
        return $this->alp->request('position',['stock'=>$stock],'DELETE')->contents();
    }

    /**
     * closeAll()
     *
     * @return array
     */
    public function closeAll() {
        return $this->alp->request('positions',[],'DELETE')->contents();
    }
}

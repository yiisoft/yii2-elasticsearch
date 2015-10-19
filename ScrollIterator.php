<?php
namespace yii\elasticsearch;

/**
 * Scroll Iterator use elasticsearch scroll API for iterate large dataset
 * @author wangfeng
 * @since 2.0
 */
class ScrollIterator implements \Iterator
{
    /** @var  ActiveQuery */
    protected $activeQuery;

    /** @var Command  */
    protected  $command;

    protected $lastScrollId;

    protected $bufferModels = [];

    protected $readResult;

    protected $total;

    protected $cursor = 0;

    protected $key;

    protected $scrollOptions = [
        'scroll' => '1m',
        'size' => 50,
    ];

    public function __construct($command, $activeQuery, $scrollOptions = [])
    {
        $this->command = $command;
        $this->activeQuery = $activeQuery;
        $this->scrollOptions = array_merge($this->scrollOptions, $scrollOptions);
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->readResult;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        if(empty($this->bufferModels)){
            $res = $this->command->scroll($this->lastScrollId, $this->scrollOptions);
            $this->total = $res['hits']['total'];
            $this->lastScrollId = $res['_scroll_id'];
            $this->bufferModels = $this->createBufferModels($res);
        }
        $this->cursor++;
        foreach($this->bufferModels as $k=>$model){
            $this->key=$k;
            $this->readResult = $model;
            break;
        }
        array_shift($this->bufferModels);
    }

    public function createBufferModels($result)
    {
        return $this->activeQuery->createAllModels($result);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        if($this->activeQuery->limit !== NULL && $this->cursor > $this->activeQuery->limit){
            return false;
        }
        return $this->cursor <= $this->total;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        if(isset($this->lastScrollId)){
            $this->command->clearScroll($this->lastScrollId);
        }
        if(!$this->activeQuery->orderBy){
            $this->scrollOptions['search_type'] = 'scan';
        }
        $res=$this->command->search($this->scrollOptions);
        $this->total = $res['hits']['total'];
        $this->lastScrollId = $res['_scroll_id'];
        $this->bufferModels = $this->createBufferModels($res);
        $this->next();
    }
}

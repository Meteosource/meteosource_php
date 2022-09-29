<?php

namespace Meteosource;

use DateTime;
use DateTimeZone;
/**
 * Abstract class that represents data in any section of the response
 */

abstract class Data implements \ArrayAccess, \Iterator, \Countable
{
	protected string $timezone;
	protected string $type;
	protected int $position = 0;
	protected $data = null;
	protected $datesStr = null;
	protected $datesDateTime = null;

	public function __get($name)
	{
		return $this->data->$name;
	}

 	public function offsetSet($offset, $value)
 	{
 		$this->data[$offset] = $value;
    }

    public function offsetExists($offset)
    {	
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
    	$this->data[$offset] = null;
   	}

    public function offsetGet($offset)
    {
        if (is_int($offset)) {
            if(isset($this->data[$offset]))
        	   return $this->data[$offset];
            throw new \OutOfBoundsException;
        }
        if (is_string($offset)) {
            $index = array_search($offset, $this->datesStr);
            if($index)
        	   return $this->data[$index];
            throw new \OutOfBoundsException;
        }
        if ($offset instanceof DateTime) {
            if($offset->getTimezone() && $offset->getTimezone()->getName() != $this->timezone) {
                $offset->setTimezone(new DateTimeZone($this->timezone));
            }
            $index = array_search($offset, $this->datesDateTime);
            if($index)
        	   return $this->data[$index];
            throw new \OutOfBoundsException;
        }
    }

    public function rewind(): void {
        $this->position = 0;
    }

    public function current() {
        return $this->data[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next(): void {
        ++$this->position;
    }

    public function valid(): bool {
        return isset($this->data[$this->position]);
    }

    public function count(): ?int {
        return count($this->data);
    }
}

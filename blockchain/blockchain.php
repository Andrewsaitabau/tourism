<?php
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;

    public function __construct($index, $timestamp, $data, $previousHash = '') {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
    }

    public function calculateHash() {
        return hash('sha256', $this->index . $this->timestamp . json_encode($this->data) . $this->previousHash);
    }
}

class Blockchain {
    public $chain = [];

    public function __construct() {
        if (empty($this->chain)) {
            $this->chain[] = $this->createGenesisBlock();
        }
    }

    private function createGenesisBlock() {
        return new Block(0, date('Y-m-d H:i:s'), "Genesis Block", "0");
    }

    public function getLatestBlock() {
        return end($this->chain);
    }

    public function addBlock($data) {
        $previousBlock = $this->getLatestBlock();
        $newIndex = $previousBlock->index + 1;
        $newTimestamp = date('Y-m-d H:i:s');
        $newBlock = new Block($newIndex, $newTimestamp, $data, $previousBlock->hash);
        $this->chain[] = $newBlock;
    }

    public function getChain() {
        return $this->chain;
    }
}
?>

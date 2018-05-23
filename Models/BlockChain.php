<?php
/**
 * Created by PhpStorm.
 * User: matthewbowen
 * Date: 5/9/18
 * Time: 6:05 PM
 */

class BlockChain
{
    public $chain;
    public $current_transactions;
    public $nodes;

    public function __construct() {
        $this->chain = array();
        $this->current_transactions = array();
        $this->nodes = array();

        $this->new_block(100, 1);
    }

    public function register_node($address) {
        $this->nodes[] = $address;
        $this->nodes = array_unique($this->nodes);
    }

    public function valid_chain($chain) {
        $last_block = $chain[0];
        $current_index = 1;

        while ($current_index < count($chain)) {
            $block = $chain[$current_index];

            $last_block_hash = $this->hash($last_block);

            if($block->previous_hash != $last_block_hash) {
                return false;
            }

            if (!$this->valid_proof($last_block->proof, $block->proof, $last_block_hash)) {
                return false;
            }

            echo("<script>console.log('$last_block . $block . $last_block_hash');</script>");

            $last_block = $block;
            $current_index += 1;
        }

        return true;
    }

    public function resolve_conflicts() {
        $neighbors = $this->nodes;
        $new_chain = null;

        $max_length = count($this->chain);

        foreach($neighbors as $node) {
            try {
                $curl = curl_init("http://$node/chain");

                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 120,
                    CURLOPT_CONNECTTIMEOUT => 120,
                    CURLOPT_USERAGENT => 'EmployCoin',
                    CURLOPT_HEADER => false
                ));

                $response = json_decode(curl_exec($curl));

                curl_close($curl);

                if($response->status_code == 200) {
                    $length = $response->length;
                    $chain = $response->chain;

                    if($length > $max_length && $this->valid_chain($chain)) {
                        $max_length = $length;
                        $new_chain  = $chain;
                    }
                }
            } catch (Exception $ex) {
                $ex->getMessage();
            }
        }

        if($new_chain != null) {
            $this->chain = $new_chain;
            return true;
        }

        return false;
    }

    public function new_block($proof, $previous_hash = null) {
        $block = array(
            'index' => count($this->chain) + 1,
            'timestamp' => time(),
            'transactions' => $this->current_transactions,
            'proof' => $proof,
            'previous_hash' => $previous_hash != null ? $previous_hash : $this->hash($this->last_block())
        );

        $this->current_transactions = array();

        $this->chain[] = $block;
        return $block;
    }

    public function new_transaction($sender, $recipient, $amount) {
        $this->current_transactions[] = array(
            'sender' => $sender,
            'recipient' => $recipient,
            'amount' => $amount
        );

        return $this->last_block()['index'] + 1;
    }

    public function proof_of_work($last_block) {
        $last_proof = $last_block->proof;
        $last_hash = $this->hash($last_block);

        $proof = 0;

        while (!$this->valid_proof($last_proof, $proof, $last_hash)) {
            $proof += 1;
        }

        return $proof;
    }

    public function valid_proof($last_proof, $proof, $last_hash) {
        $guess = utf8_encode("$last_proof$proof$last_hash");
        $guess_hash = hash('sha256', $guess);
        return substr($guess_hash, 0, 4) == "0000";
    }

    public function hash($block) {
        $block_string = json_encode($block);
        return hash('sha256', $block_string);
    }

    public function last_block() {
        return end($this->chain);
    }
}
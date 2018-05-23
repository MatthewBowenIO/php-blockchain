<?php

require_once('router.php');

class API
{
    private $_URI;
    private $_method;
    private $_rawInput;
    private $_blockchain;
    private $_uuid;
    private $_httpHost;

    function __construct($inputs) {
        $this->_URI =       $this->_checkKey('URI', $inputs);
        $this->_rawInput =  $this->_checkKey('raw_input', $inputs);
        $this->_method =    $this->_checkKey('method', $inputs);
        $this->_httpHost =  $this->_checkKey('httpHost', $inputs);

        $this->_blockchain = new BlockChain();

        try {
            if(!is_dir("wallets/")) {
                mkdir("wallets/");
            }

            if(!is_dir("wallets/$this->_httpHost._wallet/")) {
                mkdir("wallets/$this->_httpHost._wallet/");
            }

            if(file_exists("wallets/$this->_httpHost._wallet/$this->_httpHost._blockchain.txt")) {
                $this->_blockchain->chain = json_decode(file_get_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._blockchain.txt"), true);
            }

            $this->_blockchain->nodes = json_decode(file_get_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._nodes.txt"), true);
            $this->_blockchain->current_transactions = json_decode(file_get_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._transactions.txt"), true);
            $this->_uuid = json_decode(file_get_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._uuid.txt"), false);
        } catch (Exception $ex) {
            $ex->getMessage();
        }

        if($this->_uuid == null) {
            $this->_uuid = uniqid();
            file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._uuid.txt", json_encode($this->_uuid));
        }
    }

    private function _checkKey($key, $array) {
        return array_key_exists($key, $array) ? $array[$key] : NULL;
    }

    public function run() {
        $router = new Router();

        $router->addRoute('GET', '/mine', function() {
            $last_block = $this->_blockchain->last_block();
            $proof = $this->_blockchain->proof_of_work($last_block);

            $this->_blockchain->new_transaction(0, $this->_uuid, 1);

            $previous_hash = $this->_blockchain->hash($last_block);
            $block = $this->_blockchain->new_block($proof, $previous_hash);

            $response = array(
                'status_code' => 200,
                'message' => 'New Block Created',
                'index' => $block['index'],
                'transactions' => $block['transactions'],
                'proof' => $block['proof'],
                'previous_hash' => $block['previous_hash']
            );

            file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._blockchain.txt", json_encode($this->_blockchain->chain));
            file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._transactions.txt", json_encode($this->_blockchain->current_transactions));

            echo json_encode($response);
        });

        $router->addRoute('POST', '/transactions/new', function() {
            $request = json_decode($this->_rawInput);
            $index = $this->_blockchain->new_transaction($request->sender, $request->recipient, $request->amount);

            file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._transactions.txt", json_encode($this->_blockchain->current_transactions));

            $response = ['message' => "Transaction will be added to Block $index"];

            echo json_encode($response);
        });

        $router->addRoute('GET', '/chain', function() {
            $response = array(
                'status_code' => 200,
                'chain' => $this->_blockchain->chain,
                'length' => count($this->_blockchain->chain)
            );

            echo json_encode($response);
        });

        $router->addRoute('GET', '/nodes', function() {
            $response = array(
                'status_code' => 200,
                'nodes' => $this->_blockchain->nodes,
                'length' => count($this->_blockchain->nodes)
            );

            echo json_encode($response);
        });

        $router->addRoute('POST', '/nodes/register', function() {
            $request = json_decode($this->_rawInput);

            $nodes = $request->nodes;
            if($nodes == null) {
                echo json_encode(['message' => 'Error: Please supply a valid list of nodes']);
                return;
            }

            foreach($nodes as $node) {
                $this->_blockchain->register_node($node);
            }

            file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._nodes.txt", json_encode($this->_blockchain->nodes));

            $response = array(
                'status_code' => 200,
                'message' => 'New nodes have been added',
                'total_nodes' => count($this->_blockchain->nodes),
                'nodes' => $this->_blockchain->nodes
            );

            echo json_encode($response);
        });

        $router->addRoute('GET', '/nodes/resolve', function() {
            $replaced = $this->_blockchain->resolve_conflicts();

            if($replaced) {
                $response = array(
                    'message' => 'Our chain was replaced',
                    'new_chain' => $this->_blockchain->chain
                );

                file_put_contents("wallets/$this->_httpHost._wallet/$this->_httpHost._blockchain.txt", json_encode($this->_blockchain->chain));
            } else {
                $response = array(
                    'status_code' => 200,
                    'message' => 'Our chain is authorized',
                    'chain' => $this->_blockchain->chain
                );
            }

            echo json_encode($response);
        });

        $router->run($this->_method, $this->_URI);
    }
}

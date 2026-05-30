<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

#[AsCommand(
    name: 'app:websocket-start',
    description: 'Starts the WebSocket server using Workerman',
)]
class WebSocketServerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to listen on', 8080)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host to bind to', '0.0.0.0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $port = (int) $input->getOption('port');
        $host = $input->getOption('host');

        $io->success(sprintf('Starting WebSocket server on %s:%d', $host, $port));

        // Create a Worker with WebSocket protocol
        $wsWorker = new Worker("websocket://$host:$port");

        // 4 processes
        $wsWorker->count = 4;

        // Emitted when new connection come
        $wsWorker->onConnect = function (TcpConnection $connection) {
            echo "New connection\n";
        };

        // Emitted when data received
        $wsWorker->onMessage = function (TcpConnection $connection, $data) use ($wsWorker) {
            // Broadcast to all clients
            foreach ($wsWorker->connections as $clientConnection) {
                $clientConnection->send($data);
            }
        };

        // Emitted when connection closed
        $wsWorker->onClose = function (TcpConnection $connection) {
            echo "Connection closed\n";
        };

        // Run all workers
        Worker::runAll();

        return Command::SUCCESS;
    }
}

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
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to listen on', 8082)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host to bind to', '0.0.0.0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $port = (int) $input->getOption('port');
        $host = $input->getOption('host');

        $io->success(sprintf('Starting WebSocket server on %s:%d', $host, $port));

        $wsWorker = new Worker("websocket://$host:$port");
        $wsWorker->count = 1; // 1 process for simplicity in Railway

        $wsWorker->onMessage = function (TcpConnection $connection, $data) use ($wsWorker) {
            $payload = json_decode($data, true);
            
            // Only broadcast valid order events
            if (isset($payload['type']) && $payload['type'] === 'new_order') {
                foreach ($wsWorker->connections as $client) {
                    $client->send($data);
                }
            }
        };

        Worker::runAll();
        return Command::SUCCESS;
    }
}

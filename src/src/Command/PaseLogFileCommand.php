<?php


namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeoIp2\Database\Reader;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use UAParser\Parser;

class PaseLogFileCommand extends Command
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:parselog';

    protected function configure(){
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription("Parsear Log File")

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command parse the a log file with geocode")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = Parser::create();

        try {
            $access_log_file = realpath(".") ."/storage/gobankingrates.com.access.log";
            $geolite_db      = realpath(".") ."/storage/GeoLite2-City.mmdb";
            $output_file     = realpath(".") ."/storage/file.csv";

            $output->writeln("<info>Reading log access ". $access_log_file ."</info>");
            $iterable_lines = $this->readFile($access_log_file);

            $output->writeln("<info>Reading DB GeoLite2-City ". $geolite_db ."</info>");
            $geolite_reader = new Reader($geolite_db);

            $output->writeln("<info>Creating file.csv</info>");
            if ( file_exists($output_file) ){
                $date = (new \DateTime())->format('Ymdhms');
                rename($output_file, "storage/file_{$date}.csv");
            }
            $fp = fopen($output_file, 'w');
            fputcsv($fp, ['log', 'ip', 'country', 'city', 'device', 'browser']);

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action (write "y" to confirm)?', false);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            $start_time = microtime(true);
            foreach ($iterable_lines as $line) {
                if (empty($line)){ break; }
                $output->writeln("<info>$line</info>");

                //ip remote_log_name user_id [date timezone] "request_method path request_version" estatus length "referrer" "user_agent"
                preg_match('/(.*?) (.*?) (.*?) \[(.*?)(?= ) (.*?)\] \"(.*?) (.*?)(HTTP\/.*)?\" (.*?) (.*?) \"(.*?)\" \"(.*?)\"/', $line, $matches);
                $ip = $matches[1];
                $user_agent = $matches[12];

                $record = $this->getCountryAndCity($geolite_reader, $ip);
                $result = $parser->parse($user_agent);

                $output->writeln( $record->country . ',' . $record->city );
                $output->writeln( $result->ua->family );
                $output->writeln( $result->os->family );
                $output->writeln( $this->deviceType($result->device->family, $result->os->family) );

                fputcsv($fp, [
                    'log'       => $line,
                    'ip'        => $ip,
                    'country'   => $record->country,
                    'city'      => $record->city,
                    'device'    => $this->deviceType($result->device->family, $result->os->family),
                    'browser'   => $result->ua->family,
                ]);
            }
            fclose($fp);

            // End clock time in seconds
            $end_time = microtime(true);
            $execution_time = round(($end_time - $start_time) / 60, PHP_ROUND_HALF_DOWN);

            $output->writeln("<info>Done in ".$execution_time." min!! Your File is in ". $output_file ."</info>");

            return Command::SUCCESS;
        }catch (\Exception $e){
            $output->writeln("<error>{ $e }</error>");

            return Command::FAILURE;
        }
    }

    /**
     * Read access log file
     * @param String $file_name
     * @return \Generator|\NoRewindIterator
     * @throws \Exception
     */
    public function readFile(String $file_name){
        if (!file_exists($file_name)) {
            throw new \Exception("File not found");
        }

        $file = new \SplFileObject($file_name, 'r');
        $count = 0;
        while (!$file->eof()) {
            yield $file->fgets();
            $count++;
        }

        return new \NoRewindIterator($count);
    }

    /**
     * Translate useragent to device type (Mobile, Desktop, Tablet, Other)
     * @param String $device_family
     * @param String $os
     * @return String
     */
    public function deviceType(String $device_family, String $os) : String {
        $device = 'Other';

        if ($os === 'Windows' || $os === 'Linux' || $device_family === 'Mac'){
            $device = 'Desktop';
        }elseif ($device_family === 'iPad' || $device_family === 'Tablet'){
            $device = 'Tablet';
        }elseif ($device_family === 'iPhone' || $os === 'Android'){
            $device = 'Mobile';
        }

        return $device;
    }

    /**
     * Resolve Country and State from IP address
     * @param Reader $reader
     * @param String $ip
     * @return Object
     */
    public function getCountryAndCity(Reader $reader, String $ip) : Object {
        try {
            $record  = $reader->city($ip);
            return (object)[
                'country' => $record->country->name ? $record->country->name : 'Not Found',
                'city'    => $record->city->name ? $record->city->name : 'Not Found',
            ];
        }catch (\Exception $e){
            return (object)[
                'country' => 'Not Found',
                'city'    => 'Not Found',
            ];
        }
    }
}
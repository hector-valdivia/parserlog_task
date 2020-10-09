<?php


namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeoIp2\Database\Reader;
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
            $output->writeln("<info>Reading log access ". realpath(".") ."/storage/gobankingrates.com.access.log</info>");
            $iterable_lines = $this->readFile('storage/gobankingrates.com.access.log');

            $output->writeln("<info>Reading DB GeoLite2-City ". realpath(".") ."/storage/GeoLite2-City.mmdb</info>");
            $geolite_reader = new Reader('storage/GeoLite2-City.mmdb');

            $output->writeln("<info>Creating file.csv</info>");
            if ( file_exists('storage/file.csv') ){
                $date = (new \DateTime())->format('Ymdhms');
                rename("storage/file.csv", "storage/file_{$date}.csv");
            }
            $fp = fopen('storage/file.csv', 'w');
            fputcsv($fp, ['log', 'ip', 'country', 'city', 'device', 'browser']);

            $output->writeln("<info>Start............................................................</info>");

            foreach ($iterable_lines as $line) {
                if (empty($line)){ break; }
                $output->writeln("<info>$line</info>");
                preg_match('/(.*?) (.*?) (.*?) \[(.*?)(?= ) (.*?)\] \"(.*?) (.*?)(HTTP\/.*)?\" (.*?) (.*?) \"(.*?)\" \"(.*?)\"/', $line, $matches);
                list($same_line, $ip, $remote_log_name, $user_id, $date, $timezone, $request_method, $path, $request_version, $estatus, $length, $referrer, $user_agent) = $matches;

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

            $output->writeln("<info>Done!! Your File is in ". realpath(".") ."/storage/file.csv</info>");

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

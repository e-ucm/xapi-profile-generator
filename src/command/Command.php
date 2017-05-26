<?php
namespace es\eucm\xapi\command;

use es\eucm\xapi\Profile2Html;

class Command
{
    static public function main()
    {
        $command = new self;
        $command->run($_SERVER['argv']);
    }

    private function run(array $argv)
    {
        if (count($argv) < 2) {
            fwrite(STDERR, "path to xapi profile (.jsonld) expected.\n");
            exit(1);
        }
        $profileFile=$argv[1];
        if (! is_readable($profileFile)) {
            fwrite(STDERR, sprintf("'%s' does not exists or is not readable.\n", $profileFile));
            exit(1);
        }

        if (! is_file($profileFile)) {
            fwrite(STDERR, sprintf("'%s' does not exists or is not a file.\n", $profileFile));
            exit(1);
        }

        $generator = new Profile2Html();
        echo $generator->generate($profileFile);
    }
}

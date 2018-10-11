<?php
/**
 * ReplCommand.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-01-04 11:57
 * @modified   2018-01-04 11:57
 */

namespace Console;

use Psy\Configuration;
use Psy\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class repl extends Command
{
    protected function configure()
    {
        $this->setName('repl')
            ->setDescription('Displays REPL runtime for OpenCart');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = 'Run OpenCartREPL(Powered by OpenCart.Cn) NOW ...';
        $output->writeln("<comment>" . $message . "</comment>");
        $config = new Configuration();
        $config->setDefaultIncludes();
        $shell = new Shell($config);
        $shell->run();
    }
}

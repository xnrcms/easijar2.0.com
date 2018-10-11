<?php
/**
 * OrderCommand
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     TL <mengwb@opencart.cn>
 * @created    2018-01-23 11:32
 * @modified   2018-01-23 11:32
 */

namespace Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class order extends Command
{
    protected $message;

    protected function configure()
    {
        $this->setName('order')
            ->setDescription('Cancel or complete order automatically');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        model('account/order')->paymentTimeout();
        model('account/order')->autoCompleteOrder();
        $output->writeln("<comment>Sucess!</comment>");
    }
}
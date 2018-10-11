<?php
/**
 * ProductCommand.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-01-04 11:32
 * @modified   2018-01-04 11:32
 */

namespace Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class product extends Command
{
    protected $message;

    protected function configure()
    {
        $this->setName('product')
        ->setDescription('Just show a demo how to access one product data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $product = model('catalog/product')->getProduct(28);
        d($product);
        $output->writeln("<comment>" . $this->message . "</comment>");
    }
}
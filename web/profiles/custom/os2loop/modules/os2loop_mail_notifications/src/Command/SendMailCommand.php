<?php

declare(strict_types=1);

namespace Drupal\os2loop_mail_notifications\Command;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2loop_mail_notifications:mail:send',
  description: 'Send a mail',
  aliases: ['example'],
)]
final class SendMailCommand extends Command {

  public function __construct(
    private readonly MailManagerInterface $mailManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->addArgument('to', InputArgument::REQUIRED, 'The mail recipient')
      ->addOption('module', NULL, InputOption::VALUE_REQUIRED, 'The module', 'os2loop_flag_content')
      ->addOption('key', NULL, InputOption::VALUE_REQUIRED, 'The key', 'flag_content')
      ->addOption('langcode', NULL, InputOption::VALUE_REQUIRED, 'The langcode', 'en')
      ->addOption('params', NULL, InputOption::VALUE_REQUIRED, 'The params (whatever that may be) as a Yaml object', '{}')
      ->setHelp(<<<HELP
Examples:

drush %command.name% test@example.com --params '
message: My test message
'
HELP);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $to = $input->getArgument('to');
    $module = $input->getOption('module');
    $key = $input->getOption('key');
    $langcode = $input->getOption('langcode');
    try {
      $params = Yaml::parse($input->getOption('params'));
    }
    catch (\Exception $e) {
      throw new InvalidArgumentException(dt('Invalid params: %message', ['%message' => $e->getMessage()]));
    }

    // @todo Add some useful stuff here.
    $defaultParams = match ($module) {
      'os2loop_flag_content' => [
        'reason' => 'reason',
        'message' => __FILE__,
        'node' => Node::create([
          'type' => 'test',
          'nid' => 0,
          'title' => __FILE__,
        ]),
      ],
      default => throw new InvalidArgumentException(dt('Unknown module: %module', ['%module' => $module])),
    };

    $params = NestedArray::mergeDeep($defaultParams, (array) $params);

    $send = TRUE;
    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    $success = $result['result'] ?? FALSE;
    if ($success) {
      $io->success(dt('Mail successfully sent to %to', ['%to' => $to]));
    }
    else {
      $io->error(dt('Error sending mail to %to', ['%to' => $to]));
    }

    // Show the message data.
    $io->writeln(Yaml::dump($result));

    return $success ? self::SUCCESS : self::FAILURE;
  }

}

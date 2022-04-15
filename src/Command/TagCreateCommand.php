<?php

namespace eduMedia\TagBundle\Command;

use eduMedia\TagBundle\Service\TagService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'edumedia:tag:create',
    description: 'Create tag',
)]
class TagCreateCommand extends Command
{

    public function __construct(private TagService $tagService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Tag name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        dump($name);
        $tag = $this->tagService->loadOrCreateTag($name);
        $io->success(sprintf("Tag `%s` has ID %s", $name, $tag->getId()));

        return Command::SUCCESS;
    }
}

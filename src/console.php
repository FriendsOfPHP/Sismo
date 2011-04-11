<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sismo\Sismo;
use Sismo\BuildException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('Sismo', Sismo::VERSION);
$console
    ->register('output')
    ->setDefinition(array(
        new InputArgument('slug', InputArgument::REQUIRED, 'Project slug'),
    ))
    ->setDescription('Displays the latest output for a project')
    ->setHelp(<<<EOF
The <info>output</info> command displays the latest output for a project:

    <info>./sismo output twig</info>
EOF
    )
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $sismo = $app['sismo'];
        $slug = $input->getArgument('slug');
        if (!$sismo->hasProject($slug)) {
            $output->writeln(sprintf('<error>Project "%s" does not exist.</error>', $slug));

            return 1;
        }

        $project = $sismo->getProject($slug);

        if (!$project->getLatestCommit()) {
            $output->writeln(sprintf('<error>Project "%s" has never been built yet.</error>', $slug));

            return 2;
        }

        $output->write($project->getLatestCommit()->getOutput());
    })
;

$console
    ->register('build')
    ->setDefinition(array(
        new InputArgument('slug', InputArgument::OPTIONAL, 'Project slug'),
        new InputArgument('sha', InputArgument::OPTIONAL, 'Commit sha'),
        new InputOption('force', '', InputOption::VALUE_NONE, 'Force the build'),
        new InputOption('local', '', InputOption::VALUE_NONE, 'Disable remote sync'),
        new InputOption('silent', '', InputOption::VALUE_NONE, 'Disable notifications'),
        new InputOption('timeout', '', InputOption::VALUE_REQUIRED, 'Time limit'),
        new InputOption('data-path', '', InputOption::VALUE_REQUIRED, 'The data path'),
        new InputOption('config-file', '', InputOption::VALUE_REQUIRED, 'The config file'),
    ))
    ->setDescription('Build projects')
    ->setHelp(<<<EOF
Without any arguments, the <info>build</info> command builds the latest commit
of all configured projects one after the other:

    <info>./sismo build</info>

The command loads project configurations from
<comment>~/.sismo/config.php</comment>. Change it with the
<info>--config-file</info> option:

    <info>./sismo build --config-file=/path/to/config.php</info>

Data (repository, DB, ...) are stored in <comment>~/.sismo/data/</comment>.
The <info>--data-path</info> option allows you to change the default:

    <info>./sismo build --data-path=/path/to/data</info>

Pass the project slug to build a specific project:

    <info>./sismo build twig</info>

Force a specific commit to be built by passing the SHA:

    <info>./sismo build twig a1ef34</info>

Use <comment>--force</comment> to force the built even if it has already been
built previously:

    <info>./sismo build twig a1ef34 --force</info>

Disable notifications with <comment>--silent</comment>:

    <info>./sismo build twig a1ef34 --silent</info>

Disable repository synchonization with <comment>--local</comment>:

    <info>./sismo build twig a1ef34 --local</info>

Limit the time (in seconds) spent by the command building projects by using
the <comment>--timeout</comment> option:

    <info>./sismo build twig --timeout 3600</info>

When you use this command as a cron job, <comment>--timeout</comment> can avoid
the command to be run concurrently. Be warned that this is a rough estimate as
the time is only checked between two builds. When a build is started, it won't
be stopped if the time limit is over.

Use the <comment>--verbose</comment> option to debug builds in case of a
problem.
EOF
    )
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        if ($input->getOption('data-path')) {
            $app['data.path'] = $input->getOption('data-path');
        }
        if ($input->getOption('config-file')) {
            $app['config.file'] = $input->getOption('config-file');
        }
        $sismo = $app['sismo'];

        if ($slug = $input->getArgument('slug')) {
            if (!$sismo->hasProject($slug)) {
                $output->writeln(sprintf('<error>Project "%s" does not exist.</error>', $slug));

                return 1;
            }

            $projects = array($sismo->getProject($slug));
        } else {
            $projects = $sismo->getProjects();
        }

        $start = time();
        $startedOut = false;
        $startedErr = false;
        $callback = null;
        if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
            $callback = function ($type, $buffer) use ($output, &$startedOut, &$startedErr) {
                if ('err' === $type) {
                    if (!$startedErr) {
                        $output->write("\nERR| ");
                        $startedErr = true;
                        $startedOut = false;
                    }

                    $output->write(str_replace("\n", "\nERR| ", $buffer));
                } else {
                    if (!$startedOut) {
                        $output->write("\nOUT| ");
                        $startedOut = true;
                        $startedErr = false;
                    }

                    $output->write(str_replace("\n", "\nOUT| ", $buffer));
                }
            };
        }

        $flags = 0;
        if ($input->getOption('force')) {
            $flags = $flags | Sismo::FORCE_BUILD;
        }
        if ($input->getOption('local')) {
            $flags = $flags | Sismo::LOCAL_BUILD;
        }
        if ($input->getOption('silent')) {
            $flags = $flags | Sismo::SILENT_BUILD;
        }

        foreach ($projects as $project) {
            // out of time?
            if ($input->getOption('timeout') && time() - $start > $input->getOption('timeout')) {
                break;
            }

            try {
                $output->writeln(sprintf('<info>Building Project "%s" (into "%s")</info>', $project, substr(md5($project->getRepository()), 0, 6)));
                $sismo->build($project, $input->getArgument('sha'), $flags, $callback);

                $output->writeln('');
            } catch (BuildException $e) {
                $output->writeln("\n".sprintf('<error>%s</error>', $e->getMessage()));

                return 1;
            }
        }
    })
;

return $console;

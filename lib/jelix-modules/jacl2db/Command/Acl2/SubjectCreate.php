<?php
/**
 * @author      Laurent Jouanneau
 * @contributor Loic Mathaud
 *
 * @copyright   2007-2016 Laurent Jouanneau, 2008 Loic Mathaud
 *
 * @see        http://www.jelix.org
 * @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Jelix\Acl2Db\Command\Acl2;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubjectCreate extends \Jelix\Scripts\ModuleCommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('acl2:role-create')
            ->setDescription('Create a role')
            ->setHelp('')
            ->addArgument(
                'role',
                InputArgument::REQUIRED,
                'the role id to create'
            )
            ->addArgument(
                'labelkey',
                InputArgument::REQUIRED,
                'the selector of the label of the role'
            )
            ->addArgument(
                'rolegroup',
                InputArgument::OPTIONAL,
                'the id of the role group'
            )
            ->addArgument(
                'rolelabel',
                InputArgument::OPTIONAL,
                'The label of the role if the given selector does not exists'
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = $input->getArgument('role');
        $labelkey = $input->getArgument('labelkey');
        $subjectGroup = $input->getArgument('rolegroup');
        $subjectlabel = $input->getArgument('rolelabel');

        $cnx = \jDb::getConnection('jacl2_profile');
        $sql = 'SELECT id_aclsbj FROM '.$cnx->prefixTable('jacl2_subject')
            .' WHERE id_aclsbj='.$cnx->quote($subject);
        $rs = $cnx->query($sql);
        if ($rs->fetch()) {
            throw new \Exception('This role already exists');
        }

        $sql = 'INSERT into '.$cnx->prefixTable('jacl2_subject').
            ' (id_aclsbj, label_key, id_aclsbjgrp) VALUES (';
        $sql .= $cnx->quote($subject).',';
        $sql .= $cnx->quote($labelkey);
        if ($subjectGroup && $subjectGroup != 'null') {
            $sql .= ','.$cnx->quote($subjectGroup);
        } else {
            $sql .= ', NULL';
        }
        $sql .= ')';
        $cnx->exec($sql);

        if ($output->isVerbose()) {
            $output->writeln('Rights: role '.$subject.' is created');
        }

        if ($subjectlabel &&
            preg_match('/^([a-zA-Z0-9_\\.]+)~([a-zA-Z0-9_]+)\\.([a-zA-Z0-9_\\.]+)$/', $labelkey, $m)) {
            $localestring = "\n".$m[3].'='.$subjectlabel;
            $path = \jApp::getModulePath($m[1]);
            $file = $path.'locales/'.\jApp::config()->locale.'/'.$m[2].'.'.
                    \jApp::config()->charset.'.properties';
            if (file_exists($file)) {
                $localestring = file_get_contents($file).$localestring;
            }
            file_put_contents($file, $localestring);
            if ($output->isVerbose()) {
                $output->writeln('locale string '.$m[3].' is created into '.$file);
            }
        }
    }
}

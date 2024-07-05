<?php

namespace tonisormisson\version;


class Version
{
    /** @var integer $commitsCount $latest commit */
    public $commitsCount;

    /** @var string $commit $latest commit */
    public $commit;

    /** @var string $tag current tag */
    public $branch;

    /** @var string $tag current tag */
    public $tag;

    /** @var string $path */
    public $path;

    /** @var string $author */
    public $author = "";

    /** @var string $subject */
    public $subject = "";

    /** @var string $time */
    public $time = "";

    /**
     * @method __construct
     */
    public function __construct($path = null)
    {
        $this->path = $path;
        $this->load();
    }


    private function load()
    {
        $branch = $this->getCommandResult("git branch");
        $this->branch = !empty($branch) ? $branch : '';

        $tag = $this->getCommandResult("git describe --tags");
        $this->tag = isset($tag) ? $tag : $this->branch ;

        if (!empty($this->path)) {
            exec('git rev-list HEAD | wc -l', $gitCommits);
            $this->commitsCount = intval($gitCommits);
        }

        exec('git log -1', $gitHashLong);
        $this->commit = $this->getCommandResult("git log -1");

        if(!empty($this->tag)) {
            $this->author = $this->getCommandResult("git show {$this->tag} --no-patch --format=format:'%an'");
            $this->time = $this->getCommandResult("git show {$this->tag} --no-patch --format=format:'%ad'");
            $this->subject = $this->getCommandResult("git show {$this->tag} --no-patch --format=format:'%s'");
        }

    }

    /**
     * @return bool|string
     */
    private function getCommandResult($command)
    {
        $path = $this->path;
        if (!empty($path) && !\is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            return false;
        }

        $pipeConf = [1 => ['pipe', 'w']];
        if (!empty($path)) {
            $pipeConf[2] = ['pipe', 'w'];
        }
        $process = \proc_open(
            $command,
            $pipeConf,
            $pipes,
            $path
        );

        if (!\is_resource($process)) {
            return false;
        }
        $result = \trim(\stream_get_contents($pipes[1]));
        \fclose($pipes[1]);

        if (!empty($path)) {
            \fclose($pipes[2]);
        }
        $returnCode = \proc_close($process);
        if ($returnCode !== 0) {
            return false;
        }
        return $result;
    }



}

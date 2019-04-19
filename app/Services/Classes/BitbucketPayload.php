<?php

namespace App\Services\Classes;

use App\Services\Interfaces\PayloadInterface;

class BitbucketPayload implements PayloadInterface
{
    /**
     * @var string
     */
    protected $repo = '';
    /**
     * @var string
     */
    protected $repoUrl = '';
    /**
     * @var string
     */
    protected $branch = '';
    /**
     * @var string
     */
    protected $author = '';
    /**
     * @var array
     */
    protected $commits = [];

    /**
     * @return string
     */
    public function getRepo(): string
    {
        return $this->repo;
    }

    /**
     * @param string $repo
     * @return BitbucketPayload
     */
    public function setRepo(string $repo): PayloadInterface
    {
        $this->repo = $repo;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepoUrl(): string
    {
        return $this->repoUrl;
    }

    /**
     * @param string $repoUrl
     * @return BitbucketPayload
     */
    public function setRepoUrl(string $repoUrl): PayloadInterface
    {
        $this->repoUrl = $repoUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return BitbucketPayload
     */
    public function setBranch(string $branch): PayloadInterface
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return BitbucketPayload
     */
    public function setAuthor(string $author): PayloadInterface
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return array
     */
    public function getCommits(): array
    {
        return $this->commits;
    }

    /**
     * @param array $commits
     * @return BitbucketPayload
     */
    public function setCommits(array $commits): PayloadInterface
    {
        $this->commits = $commits;

        return $this;
    }
}
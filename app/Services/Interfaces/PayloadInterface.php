<?php

namespace App\Services\Interfaces;

use App\Services\Classes\BitbucketPayload;

interface PayloadInterface
{
    /**
     * @return string
     */
    public function getRepo(): string;

    /**
     * @param string $repo
     * @return BitbucketPayload
     */
    public function setRepo(string $repo): PayloadInterface;

    /**
     * @return string
     */
    public function getRepoUrl(): string;

    /**
     * @param string $repoUrl
     * @return BitbucketPayload
     */
    public function setRepoUrl(string $repoUrl): PayloadInterface;

    /**
     * @return string
     */
    public function getBranch(): string;

    /**
     * @param string $branch
     * @return BitbucketPayload
     */
    public function setBranch(string $branch): PayloadInterface;

    /**
     * @return string
     */
    public function getAuthor(): string;
    /**
     * @param string $author
     * @return BitbucketPayload
     */
    public function setAuthor(string $author): PayloadInterface;

    /**
     * @return array
     */
    public function getCommits(): array;

    /**
     * @param array $commits
     * @return BitbucketPayload
     */
    public function setCommits(array $commits): PayloadInterface;
}